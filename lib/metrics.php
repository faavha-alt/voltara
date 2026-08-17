<?php
/**
 * lib/metrics.php — Voltara EMS
 * ============================================================
 * Perhitungan turunan (derived metrics) yang SEBELUM refactor ini
 * duplikat di beberapa halaman (masing-masing sebagai kode JS
 * terpisah, kadang dengan hasil yang sedikit berbeda karena
 * formula fallback-nya di-copy-paste manual). Sekarang dihitung
 * SEKALI di sini, dipanggil oleh proxy.php, hasilnya dikirim ke
 * browser sebagai key "computed" di response `ha_states`.
 *
 * Kenapa ini penting (bukan cuma "rapi-rapi"):
 *  - index.php DAN mppt.php sebelumnya menghitung pv_power/charging_power
 *    dengan formula fallback yang identik tapi ditulis 2x terpisah.
 *  - huawei.php DAN jikong.php punya algoritma heatmap 15-sel yang
 *    100% sama persis, di-copy-paste.
 *  - Tahap berikutnya (alert Telegram / cron) BUTUH angka yang identik
 *    dengan yang tampil di dashboard — kalau logic-nya cuma ada di JS
 *    browser, cron PHP tidak bisa mengaksesnya sama sekali.
 *
 * Semua fungsi menerima $states dengan bentuk yang SAMA seperti yang
 * dikirim ke browser oleh proxy.php:
 *   $states['sensor.xxx'] = ['state' => '123.4', 'unit' => 'W']
 * ============================================================
 */

// ── Helpers dasar (setara sv()/ss()/on() di JS) ─────────────────────

function m_sv(array $states, string $key): float {
    if (!isset($states[$key]['state'])) return 0.0;
    $v = floatval($states[$key]['state']);
    return $v;
}

function m_ss(array $states, string $key): string {
    return $states[$key]['state'] ?? '—';
}

function m_on(array $states, string $key): bool {
    return m_ss($states, $key) === 'on';
}

// ── Power flow MPPT (PV & charging power + fallback estimasi) ──────
// Sebelumnya: duplikat identik di index.php dan mppt.php.
function compute_power_flow(array $states): array {
    $batV  = m_sv($states, 'sensor.smartpowermeter_mppt_battery_voltage');
    $chgA  = m_sv($states, 'sensor.smartpowermeter_mppt_charging_current');
    $loadA = m_sv($states, 'sensor.smartpowermeter_mppt_load_current');

    $pvPower   = m_sv($states, 'sensor.mppt_pv_power')       ?: ($batV * $chgA / 0.96);
    $chgPower  = m_sv($states, 'sensor.mppt_charging_power') ?: ($batV * $chgA);
    $loadPower = m_sv($states, 'sensor.mppt_load_power')     ?: ($batV * $loadA);

    $convEff = $pvPower > 10 ? min(100, ($chgPower / $pvPower) * 100) : 0.0;

    return [
        'pv_power'              => round($pvPower, 1),
        'charging_power'        => round($chgPower, 1),
        'load_power'            => round($loadPower, 1),
        'battery_voltage'       => round($batV, 2),
        'charging_current'      => round($chgA, 2),
        'conversion_efficiency' => round($convEff, 1),
    ];
}

// ── Kualitas tegangan AC + fallback estimasi ────────────────────────
// Sebelumnya: hanya ada di pzem.php.
function compute_voltage_quality(array $states): array {
    $v  = m_sv($states, 'sensor.smartpowermeter_voltage');
    $vq = m_sv($states, 'sensor.voltage_quality_score');
    $estimated = false;

    if ($vq <= 0) {
        $estimated = true;
        $dev = abs($v - 220);
        $vq  = $v <= 0 ? 0.0 : max(0, min(100, 100 - $dev * 2.5));
    }

    return [
        'score'     => round($vq, 0),
        'estimated' => $estimated, // true = HA sensor 0/unavailable, dipakai estimasi panel
    ];
}

// ── Gabungan 2 bank baterai (Huawei + Jikong) ───────────────────────
// Sebelumnya: hanya ada di battery.php (renderStates + bankRender).
function compute_battery_combined(array $states): array {
    $banks = [
        'huawei' => [
            'soc' => m_sv($states, 'sensor.huawei_soc'),
            'pwr' => m_sv($states, 'sensor.huawei_power'),
            'rem' => m_sv($states, 'sensor.huawei_kwh_remaining'),
            'soh' => m_sv($states, 'sensor.huawei_health_score'),
            'cap' => m_sv($states, 'sensor.huawei_kwh_design_full'),
        ],
        'jikong' => [
            'soc' => m_sv($states, 'sensor.jikong_soc'),
            'pwr' => m_sv($states, 'sensor.jikong_power'),
            'rem' => m_sv($states, 'sensor.jikong_kwh_remaining'),
            'soh' => m_sv($states, 'sensor.jikong_health_score'),
            'cap' => m_sv($states, 'sensor.jikong_kwh_design_full'),
        ],
    ];

    $active = array_filter($banks, fn($b) => $b['soc'] > 0);
    $count  = count($active);

    $socAvg = $count ? array_sum(array_column($active, 'soc')) / $count : 0.0;
    $totPwr = array_sum(array_column($banks, 'pwr'));
    $totRem = array_sum(array_column($banks, 'rem'));
    $totCap = array_sum(array_column($banks, 'cap'));
    $sohMin = $count ? min(array_column($active, 'soh')) : 0.0;

    return [
        'banks_active'        => $count,
        'soc_avg'             => round($socAvg, 0),
        'total_power'         => round($totPwr, 0),
        'total_remaining_kwh' => round($totRem, 2),
        'total_capacity_kwh'  => round($totCap, 2),
        'soh_min'             => round($sohMin, 0),
        'pct_of_total'        => $totCap > 0 ? round($totRem / $totCap * 100, 0) : null,
    ];
}

// ── Heatmap 15 sel (kesehatan & keseimbangan tiap sel) ──────────────
// Sebelumnya: kode identik 100% di huawei.php dan jikong.php.
// $prefix = 'huawei' atau 'jikong'.
function compute_cell_heatmap(array $states, string $prefix, int $count = 15): array {
    $cells = [];
    for ($i = 1; $i <= $count; $i++) {
        $cells[] = m_sv($states, "sensor.{$prefix}_cell_{$i}_voltage");
    }
    $valid = array_filter($cells, fn($v) => $v > 0);

    if (!count($valid)) {
        return ['valid' => false, 'cells' => []];
    }

    $max = max($valid);
    $min = min($valid);
    $avg = array_sum($valid) / count($valid);

    $classified = [];
    foreach ($cells as $i => $v) {
        $cls = 'n'; // normal/seimbang
        if ($v > 0) {
            $devMv = ($v - $avg) * 1000;
            if (abs($devMv) > 50)   $cls = 'cr'; // kritis: jauh dari rata-rata
            elseif ($devMv > 25)    $cls = 'hi'; // menonjol tinggi
            elseif ($devMv < -25)   $cls = 'lo'; // menonjol rendah
        }
        $classified[] = [
            'cell'    => $i + 1,
            'voltage' => round($v, 3),
            'class'   => $cls,
        ];
    }

    return [
        'valid'    => true,
        'max'      => round($max, 3),
        'min'      => round($min, 3),
        'avg'      => round($avg, 3),
        'delta_mv' => round(($max - $min) * 1000, 0),
        'cells'    => $classified,
    ];
}

// ── Klasifikasi status generik (health bar / badge warna) ───────────
// invert=true artinya makin TINGGI nilainya makin BAIK (mis. health score).
// Dipakai sebelumnya secara terpisah-pisah sebagai inline ternary di
// huawei.php, jikong.php, pzem.php.
function classify_status(float $v, float $warn, float $danger, bool $invert = false): string {
    if ($invert) {
        if ($v >= $danger) return 'ok';
        if ($v >= $warn)   return 'warn';
        return 'bad';
    }
    if ($v >= $danger) return 'bad';
    if ($v >= $warn)   return 'warn';
    return 'ok';
}

// ── Sumber daya aktif (Grid / Baterai / Solar) — dihitung sendiri ──
// Sebelumnya index.php langsung percaya sensor.power_source_status dari
// Home Assistant. Sensor itu dibuat waktu sistem masih 1 bank baterai
// (Huawei saja). Setelah bank Jikong ditambahkan, kalau Jikong yang
// sedang men-discharge (bukan Huawei), sensor HA itu bisa salah lapor
// "Grid" padahal rumah sebetulnya full ditenagai baterai. Di sini kita
// tentukan sumber daya dari angka gabungan 2 bank + grid, bukan dari
// satu sensor HA yang mungkin sudah usang logikanya.
//
// PENTING: angka "Grid PLN" yang ditampilkan di Aliran Energi JUGA tidak
// lagi diambil mentah dari sensor.net_grid_power — sensor itu terbukti
// bisa melapor angka besar (mis. 448 W) padahal baterai saja (965 W)
// sudah lebih dari cukup menutup beban (874 W), yang secara neraca energi
// tidak mungkin ada tarikan grid sebesar itu di saat bersamaan. Sebagai
// gantinya kita HITUNG grid dari neraca: Beban − PV − Discharge Baterai.
// Kalau PV + baterai sudah menutup beban, hasilnya otomatis 0.
function compute_power_source(array $states, array $batteryCombined): array {
    $gridPowerRaw = m_sv($states, 'sensor.net_grid_power');  // sensor HA asli, disimpan utk debug
    $gridActive   = m_on($states, 'binary_sensor.grid_active');
    $battPower    = $batteryCombined['total_power'] ?? 0.0;  // + = charging, - = discharging
    $pvPower      = m_sv($states, 'sensor.mppt_pv_power');
    $acLoad       = m_sv($states, 'sensor.smartpowermeter_power');
    $haReported   = m_ss($states, 'sensor.power_source_status');

    // Neraca energi: berapa W beban yang TIDAK tertutup PV & discharge
    // baterai — sisanya itulah yang mestinya dari grid. Tidak boleh negatif
    // (kalau PV+baterai melebihi beban, itu surplus/charging, bukan grid).
    $battDischarge = $battPower < 0 ? abs($battPower) : 0.0;
    $gridPower     = max(0.0, $acLoad - $pvPower - $battDischarge);

    $GRID_THRESHOLD = 30;   // watt — di bawah ini dianggap "tidak menarik grid"
    $BATT_THRESHOLD = 20;   // watt — di atas ini dianggap "sedang discharge nyata"

    if ($battDischarge > $BATT_THRESHOLD && $gridPower < $GRID_THRESHOLD) {
        // Baterai (gabungan bank manapun) sedang discharge signifikan,
        // dan grid (hasil neraca) tidak sedang ditarik → rumah ditenagai baterai.
        $label = 'Baterai (2 Bank)';
        $source = 'battery';
    } elseif ($gridPower > $GRID_THRESHOLD) {
        $label = 'Grid PLN';
        $source = 'grid';
    } elseif ($pvPower > $BATT_THRESHOLD && $battDischarge <= $BATT_THRESHOLD) {
        $label = 'Solar (Langsung)';
        $source = 'solar';
    } elseif ($battDischarge <= $BATT_THRESHOLD && $gridPower < $GRID_THRESHOLD) {
        $label = 'Idle / Standby';
        $source = 'idle';
    } else {
        // Kondisi ambigu — fallback ke sensor HA asli daripada menebak
        $label = $haReported !== '—' ? $haReported : 'Tidak diketahui';
        $source = 'ha_reported';
    }

    return [
        'label'          => $label,
        'source'         => $source,
        'grid_power'     => round($gridPower, 0),     // hasil neraca (dipakai UI)
        'grid_power_raw' => round($gridPowerRaw, 0),  // sensor HA asli (debug/perbandingan)
        'battery_power'  => round($battPower, 0),
        'pv_power'       => round($pvPower, 0),
        'grid_active'    => $gridActive,
        'ha_reported'    => $haReported, // simpan buat perbandingan/debug
    ];
}

// ── Biaya PLN & Hemat harian — dihitung ulang, bukan dari sensor HA ──
// sensor.pln_cost_today & sensor.solar_value_today di Home Assistant
// ternyata menghitung dari TOTAL konsumsi (energy_today_kwh), bukan dari
// energi yang benar-benar ditarik dari grid — jadi tetap "mengenakan
// biaya" walau rumah sedang 100% mandiri (full solar/baterai). Sensor
// autarky_today terbukti sudah benar (100% saat memang tidak narik PLN
// sama sekali), jadi kita pakai itu sebagai dasar yang bisa dipercaya:
//
//   Energi dari Grid = energy_today_kwh x (1 - autarky% )
//   Energi Mandiri    = energy_today_kwh x autarky%
//
// Berlaku karena sistem ini HANYA punya 3 mode sumber beban: Solar saja,
// Solar+Baterai, atau Grid saja — baterai tidak pernah di-charge dari
// grid (dikonfirmasi: inverter diset tidak grid-charging), jadi tidak
// ada energi grid yang "tersembunyi" masuk ke baterai lebih dulu.
function compute_cost_savings(array $states, float $pricePerKwh = 1444.70): array {
    $energyToday = m_sv($states, 'sensor.energy_today_kwh');
    $autarky     = max(0, min(100, m_sv($states, 'sensor.autarky_today')));
    $autarkyFrac = $autarky / 100;

    $gridKwh = $energyToday * (1 - $autarkyFrac);
    $selfKwh = $energyToday * $autarkyFrac;

    return [
        'grid_kwh_today'  => round($gridKwh, 2),
        'self_kwh_today'  => round($selfKwh, 2),
        'pln_cost_today'  => round($gridKwh * $pricePerKwh, 0),
        'savings_today'   => round($selfKwh * $pricePerKwh, 0),
        'price_per_kwh'   => $pricePerKwh,
        'autarky_pct'     => round($autarky, 1),
    ];
}

// ── Ringkasan hemat & autarki (index.php hero + KPI) ────────────────
function compute_savings_summary(array $states): array {
    return [
        'autarky_today'        => m_sv($states, 'sensor.autarky_today'),
        'solar_value_today'    => m_sv($states, 'sensor.solar_value_today'),
        'solar_value_month'    => m_sv($states, 'sensor.solar_value_month'),
        'pln_cost_today'       => m_sv($states, 'sensor.pln_cost_today'),
        'energy_today_kwh'     => m_sv($states, 'sensor.energy_today_kwh'),
        'energy_remaining_kwh' => m_sv($states, 'sensor.huawei_energy_remaining'),
        'co2_avoided_today'    => m_sv($states, 'sensor.co2_avoided_today'),
        'co2_avoided_month'    => m_sv($states, 'sensor.co2_avoided_month'),
    ];
}

// ── Perkiraan produksi PV dari radiasi matahari (Open-Meteo) ────────
// Rumus estimasi standar industri surya:
//   Daya (W) = Kapasitas Panel (Wp) x (GHI / 1000) x Performance Ratio
// GHI (Global Horizontal Irradiance, W/m2) adalah intensitas radiasi
// matahari yang sampai ke permukaan tanah — 1000 W/m2 adalah kondisi
// standar test (STC) tempat rating Wp panel diukur. Performance Ratio
// (~0.75-0.82) menutupi rugi-rugi nyata: suhu panel yang panas,
// efisiensi inverter, kotoran/debu di panel, kabel, dll.
function compute_pv_forecast(array $openMeteoData, float $capacityWp, float $performanceRatio = 0.78): array {
    $times      = $openMeteoData['hourly']['time'] ?? [];
    $radiation  = $openMeteoData['hourly']['shortwave_radiation'] ?? [];
    $cloudCover = $openMeteoData['hourly']['cloud_cover'] ?? [];
    $weatherCode= $openMeteoData['hourly']['weather_code'] ?? [];

    $hours = [];
    $totalWh = 0.0;
    foreach ($times as $i => $t) {
        $ghi = floatval($radiation[$i] ?? 0);
        $predictedW = $capacityWp * ($ghi / 1000) * $performanceRatio;
        $hours[] = [
            'time'         => $t,
            'ghi'          => round($ghi, 0),
            'cloud_cover'  => $cloudCover[$i] ?? null,
            'weather_code' => $weatherCode[$i] ?? null,
            'predicted_w'  => round($predictedW, 0),
        ];
        $totalWh += $predictedW; // interval 1 jam -> W selama 1 jam = Wh
    }

    return [
        'hours'               => $hours,
        'predicted_today_kwh' => round($totalWh / 1000, 2),
        'capacity_wp'         => $capacityWp,
        'performance_ratio'   => $performanceRatio,
    ];
}

// ── Entry point: panggil semua compute_* dan gabung jadi satu ───────
// object "computed". Dibungkus try/catch PER BAGIAN supaya satu
// fungsi eror (mis. sensor belum ada) tidak menjatuhkan seluruh response.
function build_computed_metrics(array $states): array {
    $safe = function(callable $fn) use ($states) {
        try { return $fn($states); }
        catch (\Throwable $e) { return null; }
    };

    // battery_combined dihitung duluan karena power_source butuh hasilnya
    $batteryCombined = $safe('compute_battery_combined');

    return [
        'power_flow'       => $safe('compute_power_flow'),
        'voltage_quality'  => $safe('compute_voltage_quality'),
        'battery_combined' => $batteryCombined,
        'power_source'     => $safe(fn($s) => compute_power_source($s, $batteryCombined ?? [])),
        'savings'          => $safe('compute_savings_summary'),
        'cost_savings'     => $safe(fn($s) => compute_cost_savings($s, defined('PLN_PRICE_PER_KWH') ? PLN_PRICE_PER_KWH : 1444.70)),
        'cell_heatmap'     => [
            'huawei' => $safe(fn($s) => compute_cell_heatmap($s, 'huawei')),
            'jikong' => $safe(fn($s) => compute_cell_heatmap($s, 'jikong')),
        ],
    ];
}