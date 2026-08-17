<?php
/**
 * Voltara EMS - API Proxy v2.0 (Tanpa Home Assistant)
 * ============================================================
 * Perubahan dari v1:
 *   - ha_states & SSE: ganti HA ? Flask (BMS) + InfluxDB (live sensor)
 *   - Semua influx_* case: TIDAK BERUBAH
 *   - Format response identik ? JS dashboard tidak perlu diubah
 * ============================================================
 */

require __DIR__ . '/_session.php';
require __DIR__ . '/lib/env.php';
require __DIR__ . '/lib/metrics.php';

// -- Konfigurasi (dari .env, lihat .env.example) -----------------
define('FLASK_URL',    getenv('FLASK_URL'));    // LXC BMS Flask API
define('INFLUX_URL',   getenv('INFLUX_URL'));
define('INFLUX_ORG',   getenv('INFLUX_ORG'));
define('INFLUX_TOKEN', getenv('INFLUX_TOKEN'));
define('INFLUX_BUCKET',getenv('INFLUX_BUCKET'));
define('CACHE_TTL',    5);

// PV forecast config
define('PV_LAT',         (float)getenv('PV_LAT'));
define('PV_LON',         (float)getenv('PV_LON'));
define('PV_CAPACITY_WP', (int)getenv('PV_CAPACITY_WP'));
define('PV_LOCATION_LABEL', getenv('PV_LOCATION_LABEL'));
define('PLN_PRICE_PER_KWH', (float)getenv('PLN_PRICE_PER_KWH'));
// ---------------------------------------------------------------

if (!isset($_SESSION['voltara_auth'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
session_write_close();

$type = $_GET['type'] ?? '';

// -- Helper: buat entry state ------------------------------------
function st($value, string $unit = ''): array {
    return ['state' => (string)($value ?? 'unavailable'), 'unit' => $unit];
}

// -- Helper: fetch JSON dari URL ---------------------------------
function fetch_json(string $url, int $timeout = 5): ?array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && $body) ? json_decode($body, true) : null;
}

// -- Fetch live sensor dari InfluxDB (PZEM + MPPT + combined) ---
function fetch_live_influx(): array {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -3m)
  |> filter(fn: (r) => r._measurement == "powermeter"
        or r._measurement == "mppt"
        or r._measurement == "combined"
        or r._measurement == "inverter"
        or r._measurement == "esp32")
  |> last()';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return [];

    $out = [];
    $header = [];
    foreach (explode("\n", $csv) as $line) {
        $line = rtrim($line, "\r");
        if (trim($line) === '') { $header = []; continue; }
        if (str_starts_with(ltrim($line), '#')) continue;
        $cols = str_getcsv($line);
        if (in_array('_value', $cols, true) || in_array('_field', $cols, true)) {
            $header = $cols; continue;
        }
        if (empty($header) || count($cols) !== count($header)) continue;
        $row = array_combine($header, $cols);
        if (!isset($row['_measurement'], $row['_field'])) continue;
        // PENTING: sebelumnya SEMUA nilai dipaksa (float) di sini -- termasuk
        // field teks seperti charging_mode/battery_status/load_status (mis.
        // "Bulk Charging"). (float)"Bulk Charging" di PHP hasilnya 0, jadi
        // field teks itu selalu jadi angka 0 alih-alih teks aslinya -- ini
        // sebab utama "Status Hardware MPPT" & mode charging kosong/aneh.
        // Sekarang: cuma dikonversi ke float kalau memang representasi
        // angka yang valid, selain itu disimpan apa adanya sebagai teks.
        $val = $row['_value'] ?? '';
        $out[$row['_measurement']][$row['_field']] = is_numeric($val) ? (float)$val : $val;
    }
    return $out;
}

// -- Fetch today energy dari InfluxDB --------------------------
function fetch_today_energy(): float {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: today())
  |> filter(fn: (r) => r._measurement == "powermeter" and r._field == "energy")
  |> difference()
  |> filter(fn: (r) => r._value >= 0)
  |> sum()';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return 0.0;
    foreach (explode("\n", $csv) as $line) {
        $cols = str_getcsv(rtrim($line, "\r"));
        if (count($cols) >= 5 && is_numeric($cols[3] ?? '')) {
            return (float)$cols[3];
        }
    }
    return 0.0;
}

// -- Fetch energi solar hari ini dari InfluxDB (integral daya) --
// Beda dgn fetch_today_energy() (PZEM punya counter kWh bawaan yg
// tinggal di-diff), field pv_power MPPT itu DAYA sesaat (W), bukan
// counter energi -- jadi dihitung dari rata-rata daya x durasi tiap
// jam (integral trapesium kasar via aggregateWindow 1h).
function fetch_today_solar_kwh(): float {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: today())
  |> filter(fn: (r) => r._measurement == "mppt" and r._field == "pv_power")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: false)';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return 0.0;

    $rows = parse_influx_csv($csv);
    $totalKwh = 0.0;
    foreach ($rows as $row) {
        $totalKwh += $row['value'] / 1000; // rata2 W selama 1 jam -> kWh
    }
    return $totalKwh;
}

// -- Fetch charge/discharge energi baterai hari ini (kWh) --------
// Sama pola dgn fetch_today_solar_kwh: field "power" bank baterai itu
// daya sesaat (+ = charge, - = discharge), diintegralkan per jam.
function fetch_today_battery_kwh(): array {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: today())
  |> filter(fn: (r) => r._measurement == "bms" and r._field == "power")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: false)';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['charge' => 0.0, 'discharge' => 0.0];

    // parse_influx_csv tidak menyertakan tag "device", jadi di sini kita
    // parse manual supaya tahu bank huawei/jikong-nya (dijumlah keduanya).
    $charge = 0.0; $discharge = 0.0;
    $header = [];
    foreach (explode("\n", $csv) as $line) {
        $line = rtrim($line, "\r");
        if (trim($line) === '') { $header = []; continue; }
        if (str_starts_with(ltrim($line), '#')) continue;
        $cols = str_getcsv($line);
        if (in_array('_value', $cols, true)) { $header = $cols; continue; }
        if (empty($header) || count($cols) !== count($header)) continue;
        $row = array_combine($header, $cols);
        $val = (float)($row['_value'] ?? 0);
        $kwh = $val / 1000; // rata2 W selama 1 jam -> kWh
        if ($val > 0) $charge += $kwh; else $discharge += abs($kwh);
    }
    return ['charge' => $charge, 'discharge' => $discharge];
}

// -- Fetch total CO2 & nilai solar BULAN INI (sum harian) --------
function fetch_month_totals(): array {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -30d)
  |> filter(fn: (r) => r._measurement == "mppt" and r._field == "pv_power")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: true)
  |> fill(value: 0.0)
  |> map(fn: (r) => ({r with _value: r._value / 1000.0}))
  |> aggregateWindow(every: 1d, fn: sum, createEmpty: false)';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return ['co2_kg' => 0.0, 'solar_value_rp' => 0.0];

    $rows = parse_influx_csv($csv);
    $currentMonth = date('Y-m');
    $totalSolarKwh = 0.0;
    foreach ($rows as $row) {
        if (substr($row['time'], 0, 7) !== $currentMonth) continue; // filter bulan berjalan saja
        $totalSolarKwh += $row['value']; // sudah kWh dari hourly integral
    }
    return [
        'co2_kg'         => round($totalSolarKwh * 0.87, 2),
        'solar_value_rp' => round($totalSolarKwh * PLN_PRICE_PER_KWH, 0),
    ];
}

// -- Fetch rekor puncak (voltage/current/power) dari histori ------
// "All-time" didekati dengan rentang 90 hari (retention InfluxDB biasanya
// tidak selama itu juga, jadi ini praktis = rekor terlama yang tersedia).
function fetch_peak_stats(): array {
    $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -90d)
  |> filter(fn: (r) => r._measurement == "powermeter")
  |> filter(fn: (r) => r._field == "voltage" or r._field == "current" or r._field == "power")
  |> group(columns: ["_field"])
  |> reduce(
        identity: {maxv: 0.0, minv: 999999.0},
        fn: (r, accumulator) => ({
            maxv: if r._value > accumulator.maxv then r._value else accumulator.maxv,
            minv: if r._value < accumulator.minv then r._value else accumulator.minv,
        })
     )';

    $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $flux,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Token ' . INFLUX_TOKEN,
            'Content-Type: application/vnd.flux',
            'Accept: application/csv',
        ],
        CURLOPT_TIMEOUT => 5,
    ]);
    $csv  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $default = ['voltage' => ['max'=>0,'min'=>0], 'current' => ['max'=>0,'min'=>0], 'power' => ['max'=>0,'min'=>0]];
    if ($code !== 200) return $default;

    $header = [];
    foreach (explode("\n", $csv) as $line) {
        $line = rtrim($line, "\r");
        if (trim($line) === '') { $header = []; continue; }
        if (str_starts_with(ltrim($line), '#')) continue;
        $cols = str_getcsv($line);
        if (in_array('_field', $cols, true)) { $header = $cols; continue; }
        if (empty($header) || count($cols) !== count($header)) continue;
        $row = array_combine($header, $cols);
        $field = $row['_field'] ?? null;
        if (!isset($default[$field])) continue;
        $default[$field]['max'] = (float)($row['maxv'] ?? 0);
        $default[$field]['min'] = (float)($row['minv'] ?? 0);
    }
    return $default;
}

// -- Hitung posisi matahari (elevasi & azimuth) dari lat/long -----
// Sebelumnya ini dihitung oleh integrasi sun.sun bawaan Home Assistant.
// Sekarang dihitung sendiri pakai rumus astronomi standar (algoritma
// posisi matahari sederhana ala NOAA) -- tidak butuh sensor/API
// eksternal apapun, cukup lat/long + waktu saat ini.
function compute_sun_position(float $lat, float $lon): array {
    $now = new DateTime('now', new DateTimeZone('UTC'));
    $dayOfYear = (int)$now->format('z') + 1;
    $hourUTC = (float)$now->format('H') + (float)$now->format('i') / 60 + (float)$now->format('s') / 3600;

    // Deklinasi matahari (derajat)
    $declination = 23.45 * sin(deg2rad(360 / 365 * ($dayOfYear - 81)));

    // Equation of time (menit) -- koreksi orbit elips bumi
    $b = deg2rad(360 / 365 * ($dayOfYear - 81));
    $eot = 9.87 * sin(2 * $b) - 7.53 * cos($b) - 1.5 * sin($b);

    // Waktu matahari lokal (jam) berdasarkan longitude (tanpa perlu tahu
    // timezone server -- dihitung murni dari posisi bumi)
    $solarTime = $hourUTC + ($lon / 15) + ($eot / 60);
    $hourAngle = 15 * ($solarTime - 12); // derajat

    $latRad  = deg2rad($lat);
    $decRad  = deg2rad($declination);
    $haRad   = deg2rad($hourAngle);

    $elevation = rad2deg(asin(
        sin($latRad) * sin($decRad) + cos($latRad) * cos($decRad) * cos($haRad)
    ));

    $azimuthCos = (sin($decRad) - sin($latRad) * sin(deg2rad($elevation)))
                / (cos($latRad) * cos(deg2rad($elevation)) + 1e-9);
    $azimuthCos = max(-1, min(1, $azimuthCos)); // jaga floating-point error
    $azimuth = rad2deg(acos($azimuthCos));
    if ($hourAngle > 0) $azimuth = 360 - $azimuth;

    return ['elevation' => round($elevation, 1), 'azimuth' => round($azimuth, 1)];
}

// -- Build state array dari semua sumber ------------------------
function fetch_fresh_states(): array {
    $result = [];

    // -- SOURCE 1: BMS dari Flask ------------------------------
    $bms = fetch_json(FLASK_URL . '/api/all/status');
    if ($bms) {
        $h  = $bms['huawei']['data'] ?? [];
        $j  = $bms['jikong']['data'] ?? [];
        $hok = $bms['huawei']['success'] ?? false;
        $jok = $bms['jikong']['success'] ?? false;

        // -- Huawei --
        $result['sensor.huawei_soc']             = st($h['soc_percent'] ?? 0, '%');
        $result['sensor.huawei_voltage']          = st($h['battery_voltage_v'] ?? 0, 'V');
        $result['sensor.huawei_busbar_voltage']   = st($h['busbar_voltage_v'] ?? $h['battery_voltage_v'] ?? 0, 'V');
        $result['sensor.huawei_battery_voltage']  = st($h['battery_voltage_v'] ?? 0, 'V');
        $result['sensor.huawei_current']          = st($h['current_a'] ?? 0, 'A');
        $result['sensor.huawei_power']            = st($h['power_w'] ?? 0, 'W');
        $result['sensor.huawei_soh']              = st($h['soh_percent'] ?? 100, '%');
        $result['sensor.huawei_kwh_remaining']    = st($h['kwh_soh_remaining'] ?? $h['kwh_remaining'] ?? 0, 'kWh');
        $result['sensor.huawei_kwh_design_full']  = st($h['kwh_design_full'] ?? 0, 'kWh');
        $result['sensor.huawei_kwh_actual_full']  = st($h['kwh_actual_full'] ?? 0, 'kWh');
        $result['sensor.huawei_cell_temp_max']    = st($h['temp_cell_max'] ?? 0, 'C');
        $result['sensor.huawei_cell_temp_min']    = st($h['temp_cell_min'] ?? 0, 'C');
        $result['sensor.huawei_cell_temp_avg']    = st($h['temp_cell_avg'] ?? 0, 'C');
        $result['sensor.huawei_mosfet_temp']      = st($h['temp_mos'] ?? 0, 'C');
        $result['sensor.huawei_ambient_temp']     = st($h['temp_ambient'] ?? 0, 'C');
        $result['sensor.huawei_temp_probe1']      = st($h['temp_probe1'] ?? 0, 'C');
        $result['sensor.huawei_temp_probe2']      = st($h['temp_probe2'] ?? 0, 'C');
        $result['sensor.huawei_thermal_status']   = st($h['thermal_status'] ?? 'normal', '');
        $result['sensor.huawei_cell_voltage_min'] = st($h['min_cell'] ?? 0, 'V');
        $result['sensor.huawei_cell_voltage_max'] = st($h['max_cell'] ?? 0, 'V');
        $result['sensor.huawei_cell_voltage_avg'] = st($h['avg_cell'] ?? 0, 'V');
        $result['sensor.huawei_cell_delta_mv']    = st($h['cell_diff_mv'] ?? 0, 'mV');
        $result['sensor.huawei_health_score']     = st($h['health_score'] ?? 0, '');
        $result['sensor.huawei_health_label']     = st($h['health_label'] ?? '', '');
        $result['sensor.huawei_state_mode']       = st($h['state_mode'] ?? '', '');
        $result['sensor.huawei_cycle_count']      = st($h['cycle_count'] ?? 0, '');
        $result['sensor.huawei_imbalance_level']  = st($h['imbalance_level'] ?? 'normal', '');
        $result['sensor.huawei_balance_score']    = st($h['cell_health']['imbalance_score'] ?? 0, '');
        $result['sensor.huawei_cell_std_dev']     = st($h['cell_health']['std_dev_mv'] ?? 0, 'mV');
        $result['sensor.huawei_has_alarm']        = st($h['has_alarm'] ? 1 : 0, '');
        $result['sensor.huawei_has_warning']      = st($h['has_warning'] ? 1 : 0, '');
        $result['sensor.huawei_online']           = st($hok ? 1 : 0, '');
        $result['sensor.huawei_energy_remaining'] = st($h['kwh_soh_remaining'] ?? 0, 'kWh');
        $result['sensor.huawei_modbus_read_time'] = st($h['read_time_ms'] ?? 0, 'ms');

        // Cell voltages Huawei (1-15)
        $hCells = $h['cells'] ?? [];
        foreach ($hCells as $i => $v) {
            $result['sensor.huawei_cell_' . ($i+1) . '_voltage'] = st($v, 'V');
        }

        // -- Jikong --
        $result['sensor.jikong_soc']              = st($j['soc_percent'] ?? 0, '%');
        $result['sensor.jikong_voltage']          = st($j['battery_voltage_v'] ?? 0, 'V');
        $result['sensor.jikong_battery_voltage']  = st($j['battery_voltage_v'] ?? 0, 'V');
        $result['sensor.jikong_current']          = st($j['current_a'] ?? 0, 'A');
        $result['sensor.jikong_power']            = st($j['power_w'] ?? 0, 'W');
        $result['sensor.jikong_kwh_remaining']    = st($j['kwh_remaining'] ?? 0, 'kWh');
        $result['sensor.jikong_kwh_design_full']  = st($j['kwh_design_full'] ?? 0, 'kWh');
        $result['sensor.jikong_remaining_ah']     = st($j['remaining_ah'] ?? 0, 'Ah');
        $result['sensor.jikong_cell_temp_max']    = st($j['temp_cell_max'] ?? 0, 'C');
        $result['sensor.jikong_cell_temp_min']    = st($j['temp_cell_min'] ?? 0, 'C');
        $result['sensor.jikong_cell_temp_avg']    = st($j['temp_cell_avg'] ?? 0, 'C');
        $result['sensor.jikong_cmos_temp']        = st($j['temp_mos'] ?? 0, 'C');
        $result['sensor.jikong_temp_t1']          = st($j['temp_t1'] ?? 0, 'C');
        $result['sensor.jikong_temp_t2']          = st($j['temp_t2'] ?? 0, 'C');
        $result['sensor.jikong_thermal_status']   = st($j['thermal_status'] ?? 'normal', '');
        $result['sensor.jikong_cell_voltage_min'] = st($j['min_cell'] ?? 0, 'V');
        $result['sensor.jikong_cell_voltage_max'] = st($j['max_cell'] ?? 0, 'V');
        $result['sensor.jikong_cell_voltage_avg'] = st($j['avg_cell'] ?? 0, 'V');
        $result['sensor.jikong_cell_delta_mv']    = st($j['cell_diff_mv'] ?? 0, 'mV');
        $result['sensor.jikong_health_score']     = st($j['health_score'] ?? 0, '');
        $result['sensor.jikong_health_label']     = st($j['health_label'] ?? '', '');
        $result['sensor.jikong_state_mode']       = st($j['state_mode'] ?? '', '');
        $result['sensor.jikong_cycle_count']      = st($j['cycle_count'] ?? 0, '');
        $result['sensor.jikong_cell_count']       = st($j['cell_count'] ?? 0, '');
        $result['sensor.jikong_imbalance_level']  = st($j['imbalance_level'] ?? 'normal', '');
        $result['sensor.jikong_balance_score']    = st($j['cell_health']['imbalance_score'] ?? 0, '');
        $result['sensor.jikong_cell_std_dev']     = st($j['cell_health']['std_dev_mv'] ?? 0, 'mV');
        $result['sensor.jikong_has_alarm']        = st($j['has_alarm'] ? 1 : 0, '');
        $result['sensor.jikong_has_warning']      = st($j['has_warning'] ? 1 : 0, '');
        $result['sensor.jikong_online']           = st($jok ? 1 : 0, '');
        $result['sensor.jikong_modbus_read_time'] = st($j['read_time_ms'] ?? 0, 'ms');

        // Cell voltages & resistances Jikong (1-15)
        $jCells = $j['cells'] ?? [];
        $jRes   = $j['resistances'] ?? [];
        foreach ($jCells as $i => $v) {
            $result['sensor.jikong_cell_' . ($i+1) . '_voltage']    = st($v, 'V');
            $result['sensor.jikong_cell_' . ($i+1) . '_resistance'] = st($jRes[$i] ?? 0, 'mO');
        }
    }


        // -- Inverter --
        $iv_d  = $bms['inverter']['data'] ?? [];
        $ivok  = $bms['inverter']['success'] ?? false;
        $result['sensor.inverter_online']          = st($ivok ? 1 : 0, '');
        $result['sensor.inverter_ac_in_v']         = st($iv_d['ac_in_v']         ?? 0, 'V');
        $result['sensor.inverter_ac_in_hz']        = st($iv_d['ac_in_hz']        ?? 0, 'Hz');
        $result['sensor.inverter_ac_out_v']        = st($iv_d['ac_out_v']        ?? 0, 'V');
        $result['sensor.inverter_ac_out_hz']       = st($iv_d['ac_out_hz']       ?? 0, 'Hz');
        $result['sensor.inverter_load_pct']        = st($iv_d['load_pct']        ?? 0, '%');
        $result['sensor.inverter_bat_v']           = st($iv_d['bat_v']           ?? 0, 'V');
        $result['sensor.inverter_soc']             = st($iv_d['soc_pct']         ?? 0, '%');
        $result['sensor.inverter_power_source']    = st($iv_d['power_source']    ?? '', '');
        $result['sensor.inverter_working_mode']    = st($iv_d['working_mode']    ?? '', '');
        $result['sensor.inverter_operating_state'] = st($iv_d['operating_state'] ?? '', '');
        $result['sensor.inverter_chg_current_pct'] = st($iv_d['chg_current_pct'] ?? 0, '%');
        $result['sensor.inverter_chg_voltage_v']   = st($iv_d['chg_voltage_v']   ?? 0, 'V');
        $result['sensor.inverter_cutoff_v']        = st($iv_d['cutoff_v']        ?? 0, 'V');
        $result['sensor.inverter_restore_v']       = st($iv_d['restore_v']       ?? 0, 'V');
        $result['sensor.inverter_on_battery']      = st(($iv_d['on_battery'] ?? false) ? 1 : 0, '');

    // -- SOURCE 2: Live sensor dari InfluxDB ------------------
    $live = fetch_live_influx();

    $pm = $live['powermeter'] ?? [];
    $mp = $live['mppt'] ?? [];
    $cb = $live['combined'] ?? [];
    $iv = $live['inverter']  ?? [];
    $e32 = $live['esp32']    ?? [];

    // PZEM
    $result['sensor.smartpowermeter_voltage']     = st($pm['voltage']      ?? 0, 'V');
    $result['sensor.smartpowermeter_current']     = st($pm['current']      ?? 0, 'A');
    $result['sensor.smartpowermeter_power']       = st($pm['power']        ?? 0, 'W');
    $result['sensor.smartpowermeter_energy']      = st($pm['energy']       ?? 0, 'kWh');
    $result['sensor.smartpowermeter_frequency']   = st($pm['frequency']    ?? 0, 'Hz');
    $result['sensor.smartpowermeter_power_factor']= st($pm['power_factor'] ?? 0, '');

    // MPPT raw
    $batV  = $mp['bat_v']  ?? 0;
    $chgA  = $mp['chg_a']  ?? 0;
    $loadA = $mp['load_a'] ?? 0;
    $pvV   = $mp['pv_v']   ?? 0;

    $result['sensor.smartpowermeter_mppt_battery_voltage']  = st($batV,  'V');
    $result['sensor.smartpowermeter_mppt_pv_voltage']       = st($pvV,   'V');
    $result['sensor.smartpowermeter_mppt_charging_current'] = st($chgA,  'A');
    $result['sensor.smartpowermeter_mppt_load_current']     = st($loadA, 'A');
    $result['sensor.smartpowermeter_mppt_temperature']      = st($mp['temp'] ?? 0, 'C');
    $result['sensor.smartpowermeter_mppt_soc']              = st($mp['soc']  ?? 0, '%');

    $result['sensor.smartpowermeter_mppt_charging_mode']    = st($mp['charging_mode']   ?? '', '');
    $result['sensor.smartpowermeter_mppt_battery_status']   = st($mp['battery_status']  ?? '', '');
    $result['sensor.smartpowermeter_mppt_error_code']       = st($mp['load_status']     ?? 'OK', '');

    // MPPT computed power (sama dengan fallback di metrics.php)
    $pvPower   = $mp['pv_power']   ?? ($batV * $chgA  > 0 ? round($batV * $chgA  / 0.96, 1) : 0);
    $chgPower  = $mp['pv_power']   ?? round($batV * $chgA, 1);
    $loadPower = $mp['load_power'] ?? round($batV * $loadA, 1);

    $result['sensor.mppt_pv_power']       = st($pvPower,  'W');
    $result['sensor.mppt_charging_power'] = st($chgPower, 'W');
    $result['sensor.mppt_load_power']     = st($loadPower,'W');

    // Combined
    $result['sensor.battery_charge_power']    = st($cb['solar_power'] ?? 0, 'W');
    $result['sensor.battery_discharge_power'] = st(0, 'W');


    // Inverter dari InfluxDB
    if (!empty($iv)) {
        if (empty($iv_d)) {
            $result['sensor.inverter_ac_in_v']         = st($iv['ac_in_v']         ?? 0, 'V');
            $result['sensor.inverter_ac_out_v']        = st($iv['ac_out_v']        ?? 0, 'V');
            $result['sensor.inverter_load_pct']        = st($iv['load_pct']        ?? 0, '%');
            $result['sensor.inverter_bat_v']           = st($iv['bat_v']           ?? 0, 'V');
            $result['sensor.inverter_soc']             = st($iv['soc_pct']         ?? 0, '%');
            $result['sensor.inverter_working_mode']    = st($iv['working_mode']    ?? '', '');
            $result['sensor.inverter_power_source']    = st($iv['power_source']    ?? '', '');
        }
        $result['sensor.inverter_load_power_w'] = st($iv['load_power_w'] ?? 0, 'W');
    }

    // -- SOURCE 3: Today energy dari InfluxDB -----------------
    $energyToday = fetch_today_energy();
    $result['sensor.energy_today_kwh'] = st(round($energyToday, 3), 'kWh');

    // -- SOURCE 4: Energi solar & baterai hari ini (integral daya) --
    $solarTodayKwh = fetch_today_solar_kwh();
    $battTodayKwh  = fetch_today_battery_kwh();
    $monthTotals   = fetch_month_totals();
    $peakStats     = fetch_peak_stats();

    // -- Derived sensors (sebelumnya dihitung HA template) ----
    $acLoad      = (float)($pm['power'] ?? 0);
    $battPower   = (float)(($cb['total_power'] ?? 0));
    $battDisch   = $battPower < 0 ? abs($battPower) : 0;
    $gridPower   = max(0, $acLoad - $pvPower - $battDisch);

    $result['sensor.net_grid_power']   = st(round($gridPower, 0), 'W');
    $result['sensor.power_balance']    = st(round($pvPower - $acLoad, 0), 'W');
    $result['sensor.mppt_charging_power'] = st($chgPower, 'W');
    $result['sensor.mppt_load_power']     = st($loadPower, 'W');
    $result['sensor.mppt_pv_power']       = st($pvPower, 'W');
    $result['sensor.mppt_solar_daily_kwh']= st(round($solarTodayKwh, 2), 'kWh');
    $result['sensor.mppt_daily_energy']   = st(round($solarTodayKwh, 2), 'kWh');
    // Lifetime (total sepanjang umur sistem) TIDAK ADA sumber datanya --
    // tidak ada counter kumulatif yang disimpan permanen di mana pun
    // (aggregator cuma nulis snapshot per 10 detik, InfluxDB retention
    // terbatas). Kalau perlu, ini bisa dibangun dengan counter terpisah
    // yang disimpan di file/DB kecil, terus ditambah tiap kali aggregator
    // nulis data baru -- belum ada saat ini, sengaja dibiarkan 0 drpd
    // pura-pura ada datanya.
    $result['sensor.mppt_total_energy']   = st(0, 'kWh');

    // Autarky estimasi real-time (bukan daily, tp setidaknya ada nilainya)
    $autarkyRT = $acLoad > 0 ? min(100, round(($pvPower + $battDisch) / $acLoad * 100, 1)) : 0;
    $result['sensor.autarky_today']    = st($autarkyRT, '%');

    // CO2 (estimasi: 0.87 kgCO2/kWh grid)
    $result['sensor.co2_avoided_today'] = st(round($energyToday * ($autarkyRT/100) * 0.87, 3), 'kg');
    $result['sensor.co2_avoided_month'] = st($monthTotals['co2_kg'], 'kg');

    // Biaya estimasi
    $gridKwhToday = $energyToday * (1 - $autarkyRT/100);
    $result['sensor.pln_cost_today']   = st(round($gridKwhToday * PLN_PRICE_PER_KWH, 0), 'Rp');
    $result['sensor.solar_value_today']= st(round($energyToday * ($autarkyRT/100) * PLN_PRICE_PER_KWH, 0), 'Rp');
    $result['sensor.solar_value_month']= st($monthTotals['solar_value_rp'], 'Rp');

    // Grid active
    $result['binary_sensor.grid_active'] = ['state' => $gridPower > 30 ? 'on' : 'off', 'unit' => ''];

    // Power source status (text fallback - metrics.php juga hitung ini)
    $result['sensor.power_source_status'] = st('-', '');

    // Placeholder untuk sensor yg butuh data historis lebih kompleks
    $result['sensor.battery_charge_today_kwh']    = st(round($battTodayKwh['charge'], 2), 'kWh');
    $result['sensor.battery_discharge_today_kwh'] = st(round($battTodayKwh['discharge'], 2), 'kWh');
    $result['sensor.battery_time_to_full']         = st(0, 'min');
    // Estimasi waktu baterai habis/penuh dari laju charge/discharge SAAT
    // INI (bukan riwayat) -- kasar tapi lebih berguna drpd selalu 0.
    $battKwhRemaining = (float)($h['kwh_soh_remaining'] ?? $h['kwh_remaining'] ?? 0) + (float)($j['kwh_remaining'] ?? 0);
    if ($battDisch > 20) {
        $result['sensor.battery_time_to_empty'] = st(round($battKwhRemaining / ($battDisch / 1000), 1), 'h');
        $result['sensor.battery_runtime_estimate'] = st(round($battKwhRemaining / ($battDisch / 1000), 1) . ' jam', '');
    } else {
        $result['sensor.battery_time_to_empty'] = st(0, 'h');
        $result['sensor.battery_runtime_estimate'] = st('-', '');
    }
    $result['sensor.battery_charge_energy']        = st(0, 'kWh');
    $result['sensor.battery_discharge_energy']     = st(0, 'kWh');
    $result['sensor.round_trip_efficiency']        = st(0, '%');
    $result['sensor.battery_round_trip_efficiency']= st(0, '%');

    // "Efisiensi Inverter" (DC In -> AC Out sesaat) DIHAPUS -- itu
    // membandingkan 2 sirkuit yang TIDAK selalu terhubung langsung di
    // sistem ini (MPPT ngecas baterai independen dari jalur yang
    // menyuplai beban -- sama seperti kasus "Grid PLN" yang sudah kita
    // benerin sebelumnya). Diganti 2 metrik yang benar-benar koheren:
    //
    // 1) Efisiensi MPPT (Solar -> Baterai): PV masuk vs daya yang
    //    benar-benar ngecas baterai -- ini 1 sirkuit yang sama (MPPT),
    //    jadi valid dibandingkan sesaat.
    $result['sensor.inverter_efficiency'] = st(
        $pvPower > 10 ? round(min(100, ($chgPower / $pvPower) * 100), 1) : 0, '%'
    );
    // 2) Efisiensi Sistem Harian (PV -> Beban): dibandingkan sbg RASIO
    //    HARIAN (bukan sesaat), karena baterai jadi "buffer" -- PV siang
    //    ini bisa dipakai buat beban nanti malam, jadi tidak masuk akal
    //    dibandingkan detik-ke-detik.
    $result['sensor.system_efficiency_daily'] = st(
        $solarTodayKwh > 0.1 ? round(min(150, ($energyToday / $solarTodayKwh) * 100), 1) : 0, '%'
    );
    $result['sensor.system_losses']                = st(0, 'W');
    // Daya semu (VA) & reaktif (VAr) -- dihitung dari daya aktif (W) &
    // power factor yang sudah ada dari PZEM, pakai relasi segitiga daya
    // standar: S = P / PF, Q = sqrt(S^2 - P^2).
    $pfVal = (float)($pm['power_factor'] ?? 0);
    if ($pfVal > 0.01 && $acLoad > 0) {
        $apparentPower = $acLoad / $pfVal;
        $reactivePower = sqrt(max(0, $apparentPower ** 2 - $acLoad ** 2));
    } else {
        $apparentPower = 0; $reactivePower = 0;
    }
    $result['sensor.apparent_power']               = st(round($apparentPower, 0), 'VA');
    $result['sensor.reactive_power']               = st(round($reactivePower, 0), 'VAr');
    $result['sensor.voltage_quality_score']        = st(0, '');
    $result['sensor.power_health_score']           = st(0, '');

    // Binary sensors dari ESP32 (via InfluxDB esp32 measurement)
    $anyAlarm = (($e32['any_alarm'] ?? 0) > 0);
    $result['binary_sensor.smartpowermeter_status']               = ['state' => 'on',                                       'unit' => ''];
    $result['binary_sensor.smartpowermeter_any_alarm_active']     = ['state' => $anyAlarm ? 'on' : 'off',                   'unit' => ''];
    $result['binary_sensor.smartpowermeter_over_voltage_alarm']   = ['state' => ($e32['alarm_ov'] ?? 0) ? 'on' : 'off',     'unit' => ''];
    $result['binary_sensor.smartpowermeter_under_voltage_alarm']  = ['state' => ($e32['alarm_uv'] ?? 0) ? 'on' : 'off',     'unit' => ''];
    $result['binary_sensor.smartpowermeter_over_power_alarm']     = ['state' => ($e32['alarm_op'] ?? 0) ? 'on' : 'off',     'unit' => ''];
    $result['binary_sensor.smartpowermeter_over_temperature_alarm']= ['state' => 'off',                                     'unit' => ''];
    $result['binary_sensor.smartpowermeter_mppt_online']          = ['state' => ($e32['mppt_online'] ?? (!empty($mp) ? 1 : 0)) ? 'on' : 'off', 'unit' => ''];
    $result['binary_sensor.smartpowermeter_pv_fault_daylight']    = ['state' => ($e32['pv_fault'] ?? 0) ? 'on' : 'off',    'unit' => ''];
    $result['binary_sensor.voltara_system_online']                = ['state' => 'on',                                       'unit' => ''];

    // ESP32 status dari InfluxDB measurement esp32
    $result['sensor.smartpowermeter_uptime_human']       = st($e32['uptime_human']   ?? '-',      '');
    $result['sensor.smartpowermeter_wifi_signal']        = st($e32['wifi_signal']    ?? 0,        'dBm');
    $result['sensor.smartpowermeter_esp_temperature']    = st($e32['esp_temp']       ?? 0,        'C');
    $result['sensor.smartpowermeter_box_temperature']    = st($e32['box_temp']       ?? 0,        'C');
    $result['sensor.smartpowermeter_box_humidity']       = st($e32['box_humidity']   ?? 0,        '%');
    $result['sensor.smartpowermeter_heap_free']          = st($e32['heap_free']      ?? 0,        'B');
    $result['sensor.smartpowermeter_heap_fragmentation'] = st($e32['heap_frag']      ?? 0,        '%');
    $result['sensor.smartpowermeter_loop_time']          = st($e32['loop_time']      ?? 0,        'ms');
    $result['sensor.smartpowermeter_mppt_frame_age']     = st($e32['mppt_frame_age'] ?? 0,        's');
    $result['sensor.smartpowermeter_voltage_status']     = st($e32['voltage_status'] ?? 'NORMAL', '');
    $result['sensor.smartpowermeter_load_status']        = st($e32['load_status']    ?? 'NORMAL', '');
    $result['sensor.smartpowermeter_peak_power']     = st(round($peakStats['power']['max'], 0), 'W');
    $result['sensor.smartpowermeter_peak_voltage']   = st(round($peakStats['voltage']['max'], 1), 'V');
    $result['sensor.smartpowermeter_peak_current']   = st(round($peakStats['current']['max'], 2), 'A');
    $result['sensor.smartpowermeter_lowest_voltage'] = st(round($peakStats['voltage']['min'], 1), 'V');

    // Number setpoints dari ESP32 (via InfluxDB esp32 measurement)
    $result['number.smartpowermeter_over_voltage_setpoint']    = st($e32['setpt_ov'] ?? 245,  'V');
    $result['number.smartpowermeter_under_voltage_setpoint']   = st($e32['setpt_uv'] ?? 190,  'V');
    $result['number.smartpowermeter_over_power_setpoint']      = st($e32['setpt_op'] ?? 2200, 'W');
    $result['number.smartpowermeter_over_temperature_setpoint']= st($e32['setpt_ot'] ?? 70,   'C');

    // Misc
    $result['sensor.smartpowermeter_mppt_float_voltage']          = st($mp['float_v'] ?? 0, 'V');
    $result['sensor.smartpowermeter_mppt_overvoltage_threshold']  = st($mp['ov_thresh'] ?? 0, 'V');
    $result['sensor.smartpowermeter_mppt_return_to_bulk_voltage'] = st($mp['rtb_v'] ?? 0, 'V');
    $result['sensor.smartpowermeter_mppt_discharge_cutoff']       = st($mp['cutoff_v'] ?? 0, 'V');
    $result['sensor.smartpowermeter_mppt_max_charge_current']     = st($mp['max_chg_a'] ?? 0, 'A');
    $result['sensor.smartpowermeter_mppt_battery_status']         = st($mp['battery_status'] ?? 'Normal', '');
    $sunPos = compute_sun_position(PV_LAT, PV_LON);
    $result['sensor.smartpowermeter_sun_elevation']               = st($sunPos['elevation'], 'deg');
    $result['sensor.smartpowermeter_sun_azimuth']                 = st($sunPos['azimuth'], 'deg');

    // Perkiraan daya PV "seharusnya" dari model clear-sky sederhana
    // berbasis elevasi matahari -- dipakai buat hitung Performance Ratio
    // (PV sebenarnya vs PV harusnya kalau langit cerah sempurna). Ini
    // ESTIMASI kasar (tidak memperhitungkan tutupan awan riil), bukan
    // pengukuran presisi.
    $elevRad = deg2rad(max(0, $sunPos['elevation']));
    $ghiClearSky = $sunPos['elevation'] > 0 ? 1000 * sin($elevRad) : 0;
    $expectedPvPower = $ghiClearSky > 0 ? round(PV_CAPACITY_WP * ($ghiClearSky / 1000) * 0.78, 0) : 0;
    $result['sensor.expected_pv_power'] = st($expectedPvPower, 'W');
    $result['sensor.pv_performance_ratio'] = st(
        $expectedPvPower > 10 ? round(min(120, ($pvPower / $expectedPvPower) * 100), 1) : 0, '%'
    );
    $result['sensor.mppt_conversion_efficiency'] = st(
        $pvPower > 10 ? round(min(100, ($chgPower / $pvPower) * 100), 1) : 0, '%'
    );

    // Build computed metrics (sama seperti sebelumnya)
    $result['computed'] = build_computed_metrics($result);

    return $result;
}


switch ($type) {

    // -- ha_states: ganti HA ? Flask + InfluxDB -----------------
    case 'ha_states':
        header('Content-Type: application/json');
        header('Cache-Control: no-cache');

        $cache_file = '/tmp/voltara_ha_states.json';
        $cache_age  = file_exists($cache_file) ? (time() - filemtime($cache_file)) : 999;

        if ($cache_age < CACHE_TTL) {
            echo file_get_contents($cache_file);
            exit;
        }

        $result = fetch_fresh_states();
        $json   = json_encode($result);
        file_put_contents($cache_file, $json);
        echo $json;
        break;

    // -- SSE: ganti HA ? fetch_fresh_states() -------------------
    case 'sse':
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        set_time_limit(0);
        ignore_user_abort(false);
        while (ob_get_level() > 0) { ob_end_flush(); }

        $last_hash   = '';
        $started_at  = time();
        $max_lifetime = 25 * 60;
        $last_fetch   = 0;

        while (true) {
            if (connection_aborted()) break;
            if ((time() - $started_at) > $max_lifetime) {
                echo "event: reconnect\ndata: {}\n\n";
                flush();
                break;
            }

            $cache_file = '/tmp/voltara_ha_states.json';
            $cache_age  = file_exists($cache_file) ? (time() - filemtime($cache_file)) : 999;

            if ($cache_age >= 10) {
                // Fetch baru dari Flask + InfluxDB
                $result = fetch_fresh_states();
                $json   = json_encode($result);
                file_put_contents($cache_file, $json);
            } else {
                $json = file_get_contents($cache_file);
            }

            $hash = md5($json);
            if ($hash !== $last_hash) {
                echo "data: $json\n\n";
                flush();
                $last_hash = $hash;
            } else {
                echo "event: heartbeat\ndata: {}\n\n";
                flush();
            }

            sleep(2);
        }
        exit;

    // -- Weather forecast (tidak berubah) -----------------------
    case 'weather_forecast':
        header('Content-Type: application/json');
        $cache_file = '/tmp/voltara_weather_forecast.json';
        $cache_age  = file_exists($cache_file) ? (time() - filemtime($cache_file)) : 999;
        if ($cache_age < 1800 && file_exists($cache_file)) { echo file_get_contents($cache_file); break; }

        $url = 'https://api.open-meteo.com/v1/forecast'
             . '?latitude=' . PV_LAT . '&longitude=' . PV_LON
             . '&hourly=shortwave_radiation,cloud_cover,weather_code'
             . '&timezone=auto&forecast_days=1';
        $body = @file_get_contents($url);
        if (!$body) { if (file_exists($cache_file)) echo file_get_contents($cache_file); else echo json_encode(['error' => 'fetch failed']); break; }
        $openMeteo = json_decode($body, true) ?? [];
        $result    = compute_pv_forecast($openMeteo, PV_CAPACITY_WP);
        $result['location'] = PV_LOCATION_LABEL;
        $json = json_encode($result);
        file_put_contents($cache_file, $json);
        echo $json;
        break;

    // -- Semua influx_* case: TIDAK BERUBAH ---------------------
    case 'influx_cost_history':
        header('Content-Type: application/json');
        $range = ($_GET['range'] ?? '30d') === '7d' ? '7d' : '30d';
        // Ambil 1 hari ekstra ke belakang supaya hari pertama di rentang
        // tetap bisa dihitung selisihnya (counter energy PZEM itu kumulatif
        // sejak nyala, BUKAN reset harian -- jadi harus dikurangi manual,
        // bukan diambil max() begitu saja seperti sebelumnya yang bikin
        // angkanya terus membesar tiap hari alih-alih konsumsi harian asli).
        $rangeExtended = $range === '7d' ? '8d' : '31d';
        $queries = [
            'energy_cum'  => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$rangeExtended.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="energy") |> aggregateWindow(every:1d,fn:max,createEmpty:false)',
            // Autarki historis: TIDAK ADA sensor grid fisik (CT clamp belum
            // terpasang), jadi diestimasi dari rasio produksi solar harian
            // vs konsumsi harian -- bukan dari SOC baterai (yang sebelumnya
            // dipakai keliru; SOC cuma bilang "seberapa penuh baterai",
            // bukan "berapa persen konsumsi yang mandiri").
            'solar_avg_w' => 'from(bucket:"'.INFLUX_BUCKET.'")
  |> range(start:-'.$range.')
  |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power")
  |> aggregateWindow(every:1h,fn:mean,createEmpty:true)
  |> fill(value:0.0)
  |> map(fn:(r)=>({r with _value:r._value/1000.0}))
  |> aggregateWindow(every:1d,fn:sum,createEmpty:false)',
        ];
        $raw = influx_multi($queries);

        // Hitung delta harian dari counter kumulatif (mirip fetch_today_energy
        // tapi per-hari, bukan cuma hari ini)
        $energyCum = $raw['energy_cum'] ?? [];
        usort($energyCum, fn($a, $b) => strcmp($a['time'], $b['time']));
        $energyDaily = [];
        for ($i = 1; $i < count($energyCum); $i++) {
            $date  = substr($energyCum[$i]['time'], 0, 10);
            $delta = $energyCum[$i]['value'] - $energyCum[$i - 1]['value'];
            // Kalau delta negatif, counter kemungkinan reset (mis. ESP32
            // restart) -- pakai nilai absolut hari itu sbg fallback drpd
            // angka negatif yang jelas salah.
            $energyDaily[$date] = $delta >= 0 ? $delta : $energyCum[$i]['value'];
        }

        $solarArr = $raw['solar_avg_w'] ?? [];
        $solarDailyKwh = [];
        foreach ($solarArr as $row) {
            $date = substr($row['time'], 0, 10);
            $solarDailyKwh[$date] = $row['value']; // sudah dalam kWh (hourly integral)
        }

        $days = []; $totalCost = 0; $totalSavings = 0;
        foreach ($energyDaily as $date => $energy) {
            $solarKwh = $solarDailyKwh[$date] ?? 0;
            // Autarki estimasi: solar / konsumsi, dibatasi maks 100%.
            // ESTIMASI -- akurasi penuh baru bisa didapat setelah CT clamp
            // grid terpasang (lihat diskusi sebelumnya).
            $autarky = $energy > 0 ? max(0, min(100, ($solarKwh / $energy) * 100)) : 0;
            $gridKwh = $energy * (1 - $autarky / 100);
            $selfKwh = $energy * ($autarky / 100);
            $cost    = round($gridKwh * PLN_PRICE_PER_KWH, 0);
            $savings = round($selfKwh * PLN_PRICE_PER_KWH, 0);
            $totalCost += $cost; $totalSavings += $savings;
            $days[] = ['date'=>$date,'energy_kwh'=>round($energy,2),'autarky_pct'=>round($autarky,1),'grid_kwh'=>round($gridKwh,2),'self_kwh'=>round($selfKwh,2),'cost'=>$cost,'savings'=>$savings];
        }
        echo json_encode(['days'=>$days,'total_cost'=>$totalCost,'total_savings'=>$totalSavings,'price_per_kwh'=>PLN_PRICE_PER_KWH,'note'=>'autarky_pct adalah estimasi (belum ada sensor grid fisik)']);
        break;

    case 'influx_environment_history':
        header('Content-Type: application/json');
        $range = ($_GET['range'] ?? '30d') === '7d' ? '7d' : '30d';
        $CO2_KG_PER_KWH = 0.87; // sama dgn faktor emisi yang dipakai di sensor live
        $queries = [
            'solar_avg_w' => 'from(bucket:"'.INFLUX_BUCKET.'")
  |> range(start:-'.$range.')
  |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power")
  |> aggregateWindow(every:1h,fn:mean,createEmpty:true)
  |> fill(value:0.0)
  |> map(fn:(r)=>({r with _value:r._value/1000.0}))
  |> aggregateWindow(every:1d,fn:sum,createEmpty:false)',
        ];
        $raw = influx_multi($queries);
        $solarArr = $raw['solar_avg_w'] ?? [];

        // CO2 dihindari dihitung dari ENERGI solar harian (kWh) x faktor
        // emisi grid, BUKAN dari rata-rata daya mentah (W) seperti
        // sebelumnya -- itu salah satuan (W dikira kg CO2 langsung).
        // Karena sistem ini tidak ekspor ke grid, hampir semua solar yang
        // dihasilkan otomatis menggantikan pemakaian grid (dipakai
        // langsung atau disimpan baterai utk dipakai nanti), jadi solar
        // kWh dipakai penuh sbg dasar perhitungan (bukan cuma estimasi
        // konsumsi langsung).
        $co2Today = [];
        $solarKwhOut = [];
        foreach ($solarArr as $row) {
            $solarKwh = $row['value']; // sudah kWh dari hourly integral
            $co2Today[]   = ['time' => $row['time'], 'value' => round($solarKwh * $CO2_KG_PER_KWH, 3)];
            $solarKwhOut[] = ['time' => $row['time'], 'value' => round($solarKwh, 2)];
        }
        echo json_encode(['co2_today' => $co2Today, 'solar_kwh' => $solarKwhOut]);
        break;

    case 'influx_flow_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'15m','7d'=>'2h','30d'=>'6h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $bucketHours = ['15m'=>0.25,'2h'=>2,'6h'=>6][$every];
        $queries = [
            'pv'     => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'load'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'batt_h' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'batt_j' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="jikong" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        $raw = influx_multi($queries);
        $byTime = [];
        foreach (['pv','load','batt_h','batt_j'] as $key)
            foreach (($raw[$key]??[]) as $row) $byTime[$row['time']][$key] = $row['value'];
        ksort($byTime);
        $points=[]; $nightKwh=0; $dayKwh=0; $gridKwh=0; $nightH=0; $dayH=0;
        foreach ($byTime as $time => $vals) {
            $pv=$vals['pv']??0; $load=$vals['load']??0;
            $batt=($vals['batt_h']??0)+($vals['batt_j']??0);
            $disch=$batt<0?abs($batt):0;
            $grid=max(0,$load-$pv-$disch);
            $points[]=['time'=>$time,'pv'=>round($pv,0),'load'=>round($load,0),'battery'=>round($batt,0),'grid'=>round($grid,0)];
            if ($pv<20) { $nightKwh+=$load*$bucketHours/1000; $nightH+=$bucketHours; }
            else $dayH+=$bucketHours;
            $dayKwh+=$pv*$bucketHours/1000; $gridKwh+=$grid*$bucketHours/1000;
        }
        echo json_encode(['points'=>$points,'night_consumption_kwh'=>round($nightKwh,2),'day_production_kwh'=>round($dayKwh,2),'grid_kwh'=>round($gridKwh,2),'night_hours'=>round($nightH,1),'day_hours'=>round($dayH,1)]);
        break;

    case 'influx_power_24h':
        header('Content-Type: application/json');
        $queries = [
            'pv_in'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power") |> aggregateWindow(every:30m,fn:mean,createEmpty:false)',
            'chg_out' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power") |> aggregateWindow(every:30m,fn:mean,createEmpty:false)',
            'load'    => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="power") |> aggregateWindow(every:30m,fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'5m','7d'=>'30m','30d'=>'3h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $queries = [
            'power'     => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'voltage'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="voltage") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'current'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="current") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'frequency' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="frequency") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_energy_7d':
        header('Content-Type: application/json');
        // FIX: pakai integral per jam (hourly mean W x 1h / 1000 = kWh per jam)
        // dijumlah per hari -> kWh harian yang AKURAT.
        // Sebelumnya: mean W x 24 -> overcounts 2-3x karena malam (0W) tidak masuk mean.
        $flux_solar_7d = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -8d)
  |> filter(fn: (r) => r._measurement == "mppt" and r._field == "pv_power")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: true)
  |> fill(value: 0.0)
  |> map(fn: (r) => ({r with _value: r._value / 1000.0}))
  |> aggregateWindow(every: 1d, fn: sum, createEmpty: false)';
        $queries = ['solar_kwh' => $flux_solar_7d];
        $raw = influx_multi($queries);
        $solarKwh = [];
        foreach (($raw['solar_kwh'] ?? []) as $row) {
            $solarKwh[] = ['time' => $row['time'], 'value' => round($row['value'], 2)];
        }
        echo json_encode(['solar' => $solarKwh]);
        break;

    case 'influx_soc_24h':
        header('Content-Type: application/json');
        $queries = [
            'soc' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="soc") |> aggregateWindow(every:30m,fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_battery_24h':
        header('Content-Type: application/json');
        $queries = [
            'soc'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="soc") |> aggregateWindow(every:15m,fn:mean,createEmpty:false)',
            'power' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="power") |> aggregateWindow(every:15m,fn:mean,createEmpty:false)',
            'temp'  => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="temp_max") |> aggregateWindow(every:15m,fn:mean,createEmpty:false)',
            'delta' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-24h) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="cell_diff_mv") |> aggregateWindow(every:15m,fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_battery_7d':
        header('Content-Type: application/json');
        $queries = [
            'soc_min'    => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-7d) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="soc") |> aggregateWindow(every:1d,fn:min,createEmpty:false)',
            'soc_max'    => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-7d) |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="soc") |> aggregateWindow(every:1d,fn:max,createEmpty:false)',
            'charge_kwh' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-7d) |> filter(fn:(r)=>r["_measurement"]=="combined" and r["_field"]=="total_energy") |> aggregateWindow(every:1d,fn:max,createEmpty:false)',
            'dischg_kwh' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-7d) |> filter(fn:(r)=>r["_measurement"]=="combined" and r["_field"]=="total_energy") |> aggregateWindow(every:1d,fn:min,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_mppt_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'15m','7d'=>'1h','30d'=>'6h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $queries = [
            'pv_in'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'chg_out' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="mppt" and r["_field"]=="pv_power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_battery_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'15m','7d'=>'1h','30d'=>'6h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $queries = [
            'soc'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="soc") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'power' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'temp'  => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="temp_max") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'delta' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="huawei" and r["_field"]=="cell_diff_mv") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    case 'influx_battery_jikong_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'15m','7d'=>'1h','30d'=>'6h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $queries = [
            'soc'   => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="jikong" and r["_field"]=="soc") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'power' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="jikong" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'temp'  => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="jikong" and r["_field"]=="temp_max") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'delta' => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="bms" and r["device"]=="jikong" and r["_field"]=="cell_diff_mv") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;



    case 'influx_daily_consumption':
        // Konsumsi harian dari PZEM power (hourly integral -> kWh/hari)
        header('Content-Type: application/json');
        $days = max(1, min(30, (int)($_GET['days'] ?? 7)));
        $flux = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -' . ($days+1) . 'd)
  |> filter(fn: (r) => r._measurement == "powermeter" and r._field == "power")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: true)
  |> fill(value: 0.0)
  |> map(fn: (r) => ({r with _value: r._value / 1000.0}))
  |> aggregateWindow(every: 1d, fn: sum, createEmpty: false)';

        $ch = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $flux,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Token ' . INFLUX_TOKEN,
                'Content-Type: application/vnd.flux',
                'Accept: application/csv',
            ],
            CURLOPT_TIMEOUT => 8,
        ]);
        $csv  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $rows = $code === 200 ? parse_influx_csv($csv) : [];

        // Today dari fetch_today_energy()
        $todayKwh = fetch_today_energy();

        // Inverter daily load kWh (untuk perbandingan)
        $flux_inv = 'from(bucket:"' . INFLUX_BUCKET . '")
  |> range(start: -' . ($days+1) . 'd)
  |> filter(fn: (r) => r._measurement == "inverter" and r._field == "load_power_w")
  |> aggregateWindow(every: 1h, fn: mean, createEmpty: true)
  |> fill(value: 0.0)
  |> map(fn: (r) => ({r with _value: r._value / 1000.0}))
  |> aggregateWindow(every: 1d, fn: sum, createEmpty: false)';

        $ch2 = curl_init(INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG));
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS     => $flux_inv,
            CURLOPT_HTTPHEADER     => ['Authorization: Token ' . INFLUX_TOKEN, 'Content-Type: application/vnd.flux', 'Accept: application/csv'],
            CURLOPT_TIMEOUT => 8,
        ]);
        $csv2   = curl_exec($ch2);
        $code2  = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        $rows_inv = $code2 === 200 ? parse_influx_csv($csv2) : [];
        $inv_by_date = [];
        foreach ($rows_inv as $r) { $inv_by_date[substr($r['time'],0,10)] = round((float)$r['value'], 2); }

        $result = ['days' => [], 'today_kwh' => round($todayKwh, 2)];
        foreach ($rows as $row) {
            $date = substr($row['time'], 0, 10);
            $result['days'][] = [
                'date'    => $date,
                'kwh'     => round((float)$row['value'], 2),     // PZEM (akurat)
                'inv_kwh' => $inv_by_date[$date] ?? 0,           // Inverter (estimasi)
            ];
        }
        echo json_encode($result);
        break;

    case 'influx_inverter_history':
        header('Content-Type: application/json');
        $windows = ['24h'=>'15m','7d'=>'1h','30d'=>'6h'];
        $range   = $_GET['range'] ?? '24h';
        if (!isset($windows[$range])) $range = '24h';
        $every = $windows[$range];
        $queries = [
            'ac_in_v'       => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="ac_in_v") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'ac_out_v'      => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="ac_out_v") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'load_pct'      => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="load_pct") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'bat_v'         => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="bat_v") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'soc_pct'       => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="soc_pct") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'inv_load_w'    => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="inverter" and r["_field"]=="load_power_w") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
            'pzem_power'    => 'from(bucket:"'.INFLUX_BUCKET.'") |> range(start:-'.$range.') |> filter(fn:(r)=>r["_measurement"]=="powermeter" and r["_field"]=="power") |> aggregateWindow(every:'.$every.',fn:mean,createEmpty:false)',
        ];
        echo json_encode(influx_multi($queries));
        break;

    default:
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Unknown type: ' . $type]);
}

// -- Helper: Influx multi-query paralel -------------------------
function influx_multi(array $queries): array {
    $mh  = curl_multi_init();
    $chs = [];
    $url = INFLUX_URL . '/api/v2/query?org=' . urlencode(INFLUX_ORG);
    $hdrs = [
        'Authorization: Token ' . INFLUX_TOKEN,
        'Content-Type: application/vnd.flux',
        'Accept: application/csv',
    ];
    foreach ($queries as $key => $flux) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$flux,CURLOPT_HTTPHEADER=>$hdrs,CURLOPT_TIMEOUT=>10]);
        $chs[$key] = $ch;
        curl_multi_add_handle($mh, $ch);
    }
    $running = null;
    do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
    $result = [];
    foreach ($chs as $key => $ch) {
        $csv  = curl_multi_getcontent($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        $result[$key] = $code === 200 ? parse_influx_csv($csv) : [];
    }
    curl_multi_close($mh);
    return $result;
}

// -- Helper: parse CSV InfluxDB ---------------------------------
function parse_influx_csv(string $csv): array {
    $rows = []; $header = [];
    foreach (explode("\n", $csv) as $line) {
        $line = rtrim($line, "\r");
        if (trim($line) === '') { $header = []; continue; }
        if (str_starts_with(ltrim($line), '#')) continue;
        $cols = str_getcsv($line);
        if (in_array('_value', $cols, true) || in_array('_time', $cols, true)) { $header = $cols; continue; }
        if (empty($header) || count($cols) !== count($header)) continue;
        $row = array_combine($header, $cols);
        if (!isset($row['_time']) || $row['_time'] === '') continue;
        $rows[] = ['time'=>$row['_time'],'value'=>(float)($row['_value']??0),'entity_id'=>$row['entity_id']??''];
    }
    return $rows;
}