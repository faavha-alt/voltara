<?php
require __DIR__ . '/_session.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }

$page_title  = 'Voltara EMS - AC / PZEM';
$active      = 'pzem';
$tb_h1       = 'Smart Power Meter';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [
    ['id' => 'src-badge', 'class' => 'pill',    'text' => 'Sumber: -'],
    ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => ' Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="hero">
  <div class="hero-top">
    <div class="hero-eyebrow">Pemantauan Jaringan AC</div>
    <div class="hero-title">Kelistrikan Rumah <span id="hero-status" style="color:var(--sky);font-weight:400;font-size:20px;margin-left:8px">-</span></div>
  </div>
  <div class="hero-stats">
    <div class="hs">
      <div class="hs-lbl"><?= icon('bolt', 13) ?> Beban Aktif</div>
      <div><span class="hs-val" id="h-w">-</span><span class="hs-unit"> W</span></div>
      <div class="hs-sub" id="h-w-sub">Kategori: -</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('chart', 13) ?> Konsumsi Hari Ini</div>
      <div><span class="hs-val" id="h-kwh">-</span><span class="hs-unit"> kWh</span></div>
      <div class="hs-sub" id="h-kwh-sub">Total akumulatif: - kWh</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('coin', 13) ?> Biaya Hari Ini</div>
      <div><span class="hs-val" id="h-cost">-</span><span class="hs-unit"> Rp</span></div>
      <div class="hs-sub" id="h-cost-sub">Dari grid PLN</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('shield', 13) ?> Kualitas Tegangan</div>
      <div><span class="hs-val" id="h-vq">-</span><span class="hs-unit"> %</span></div>
      <div class="hs-sub" id="h-vq-sub">-</div>
    </div>
  </div>
</div>

<div class="content">

  <div class="grid4">
    <div class="kpi">
      <div class="kpi-head"><div class="kpi-ico"><?= icon('gauge', 18) ?></div><span class="tag" id="b-v-status">-</span></div>
      <div class="kpi-lbl">Tegangan AC</div>
      <div><span class="kpi-val" id="k-v">-</span><span class="kpi-unit">V</span></div>
      <div class="kpi-sub" id="k-v-sub">Arus: - A</div>
    </div>
    <div class="kpi">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(99,102,241,.1)"><?= icon('gauge', 18) ?></div><span class="tag">Efisiensi</span></div>
      <div class="kpi-lbl">Power Factor</div>
      <div><span class="kpi-val" id="k-pf">-</span></div>
      <div class="kpi-sub" id="k-pf-sub">Efisiensi Daya AC</div>
    </div>
    <div class="kpi">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(245,158,11,.1)"><?= icon('activity', 18) ?></div><span class="tag">Grid</span></div>
      <div class="kpi-lbl">Frekuensi Jaringan</div>
      <div><span class="kpi-val" id="k-hz">-</span><span class="kpi-unit">Hz</span></div>
      <div class="kpi-sub">Standar: 50.0 Hz</div>
    </div>
    <div class="kpi">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(244,63,94,.1)"><?= icon('mountain', 18) ?></div><span class="tag">All-time</span></div>
      <div class="kpi-lbl">Puncak Beban Historis</div>
      <div><span class="kpi-val" id="k-peak-w">-</span><span class="kpi-unit">W</span></div>
      <div class="kpi-sub" id="k-peak-w-sub">Arus Maks: - A</div>
    </div>
  </div>

  <div class="grid2">

    <div class="card">
      <div class="card-hd">
        <div><div class="card-ttl">Profil Pemakaian & Tegangan</div><div class="card-sub">Daya aktif (W) & tegangan (V) dari riwayat InfluxDB</div></div>
        <div class="range-sel" id="range-sel">
          <button class="rb on" data-r="24h">24 Jam</button>
          <button class="rb"    data-r="7d">7 Hari</button>
          <button class="rb"    data-r="30d">30 Hari</button>
        </div>
      </div>
      <div class="card-bd">
        <div class="chart-hero">
          <div><span class="chart-big" id="c-w-now">- W</span>
               <span style="font-size:12px;color:var(--muted);margin-left:8px">Beban Sekarang</span></div>
          <div style="font-size:11px;color:var(--muted)" id="c-points">-</div>
        </div>
        <div id="chart-load"></div>
        <div class="hstats">
          <div><div class="hstat-lbl">Daya Rata-rata</div><div class="hstat-val" id="st-w-avg">- <small>W</small></div></div>
          <div><div class="hstat-lbl">Daya Puncak</div><div class="hstat-val" id="st-w-max">- <small>W</small></div></div>
          <div><div class="hstat-lbl">Tegangan Rata-rata</div><div class="hstat-val" id="st-v-avg">- <small>V</small></div></div>
          <div><div class="hstat-lbl">Rentang Tegangan</div><div class="hstat-val" id="st-v-range">- <small>V</small></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-hd"><div class="card-ttl">Indikator Tegangan AC</div></div>
      <div class="card-bd" style="padding:10px 15px; display:flex; flex-direction:column; align-items:center;">
        <div id="g-voltage"></div>
        <div style="font-size:11px;color:var(--muted);text-align:center;margin-top:-10px">
          <span style="color:var(--rose)">Rendah</span> -
          <span style="color:var(--mint)">Ideal (220V)</span> -
          <span style="color:var(--rose)">Tinggi</span>
        </div>
        <div style="width:100%;margin-top:14px;padding-top:12px;border-top:1px solid var(--rim);font-size:11px;color:var(--muted);text-align:center" id="g-volt-note">
          Ambang alarm: - V / - V
        </div>
        <div style="width:100%;margin-top:14px;padding-top:12px;border-top:1px solid var(--rim)">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--muted);margin-bottom:7px">
            <span>Beban vs Kapasitas Inverter (3000W)</span>
            <span id="inv-load-pct" style="font-weight:700;color:var(--body);font-family:'JetBrains Mono',monospace">- %</span>
          </div>
          <div class="fb-track" style="height:9px;border-radius:6px">
            <div class="fb-fill amber" id="inv-load-bar" style="width:0%;border-radius:6px"></div>
          </div>
          <div style="font-size:10px;color:var(--muted);margin-top:5px;text-align:right" id="inv-load-note">-</div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid3">

    <!-- ALARM -->
    <div class="card">
      <div class="card-hd">
        <div class="card-ttl">Status Alarm</div>
        <span class="tag ok" id="alarm-agg">AMAN</span>
      </div>
      <table class="rtable">
        <tbody>
          <tr><td class="lbl">Over-Voltage<span class="ctx" id="ctx-ov">trip &gt; - V</span></td><td class="val-cell" id="r-al-ov">-</td></tr>
          <tr><td class="lbl">Under-Voltage<span class="ctx" id="ctx-uv">trip &lt; - V</span></td><td class="val-cell" id="r-al-uv">-</td></tr>
          <tr><td class="lbl">Over-Power<span class="ctx" id="ctx-op">trip &gt; - W</span></td><td class="val-cell" id="r-al-op">-</td></tr>
          <tr><td class="lbl">Over-Temperature<span class="ctx" id="ctx-ot">trip &gt; - C</span></td><td class="val-cell" id="r-al-ot">-</td></tr>
        </tbody>
      </table>
    </div>

    <!-- KUALITAS & PUNCAK -->
    <div class="card">
      <div class="card-hd"><div class="card-ttl">Kualitas Daya &amp; Puncak</div></div>
      <table class="rtable">
        <tbody>
          <tr><td class="lbl">Tegangan Tertinggi</td><td class="val-cell" id="r-v-max">- V</td></tr>
          <tr><td class="lbl">Tegangan Terendah</td><td class="val-cell" id="r-v-min">- V</td></tr>
          <tr><td class="lbl">Arus Puncak</td><td class="val-cell" id="r-a-max">- A</td></tr>
          <tr><td class="lbl">Daya Puncak</td><td class="val-cell" id="r-w-max">- W</td></tr>
          <tr><td class="lbl">Daya Semu (S)</td><td class="val-cell" id="r-va">- VA</td></tr>
          <tr><td class="lbl">Daya Reaktif (Q)</td><td class="val-cell" id="r-var">- var</td></tr>
        </tbody>
      </table>
    </div>

    <!-- KESEHATAN PERANGKAT -->
    <div class="card">
      <div class="card-hd">
        <div class="card-ttl">Kesehatan Perangkat</div>
        <span class="tag" id="dev-badge">-</span>
      </div>
      <div class="hg">
        <div class="hg-cell"><div class="hg-lbl"><?= icon('thermo', 13) ?> Suhu Box</div><div class="hg-val" id="d-box-t">- <small>C</small></div></div>
        <div class="hg-cell"><div class="hg-lbl"><?= icon('droplet', 13) ?> Lembap Box</div><div class="hg-val" id="d-box-h">- <small>%</small></div></div>
        <div class="hg-cell"><div class="hg-lbl"><?= icon('thermo', 13) ?> Suhu ESP32</div><div class="hg-val" id="d-esp-t">- <small>C</small></div></div>
        <div class="hg-cell"><div class="hg-lbl"><?= icon('wifi', 13) ?> WiFi</div><div class="hg-val" id="d-wifi">- <small>dBm</small></div></div>
      </div>
      <div style="padding:13px 16px;border-top:1px solid var(--rim);font-size:12px;color:var(--muted);display:flex;justify-content:space-between">
        <span>Uptime</span><span class="mono" style="color:var(--body)" id="d-uptime">-</span>
      </div>
    </div>

  </div>

  <!-- RIWAYAT KONSUMSI -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Riwayat Konsumsi Listrik</div><div class="card-sub">kWh per hari - klik batang atau lihat tabel untuk detail 1 hari</div></div>
      <div class="range-sel" id="range-sel-consumption">
        <button class="rb" data-r="7d">7 Hari</button>
        <button class="rb on" data-r="30d">30 Hari</button>
      </div>
    </div>
    <div class="card-bd">
      <div class="pvf-stats" style="margin-bottom:14px">
        <div class="pvf-stat">
          <div class="pvf-stat-lbl">Total Periode</div>
          <div><span class="pvf-stat-val" id="ch-total">-</span><span class="pvf-stat-unit"> kWh</span></div>
        </div>
        <div class="pvf-stat">
          <div class="pvf-stat-lbl">Rata-rata/Hari</div>
          <div><span class="pvf-stat-val" id="ch-avg">-</span><span class="pvf-stat-unit"> kWh</span></div>
        </div>
        <div class="pvf-stat">
          <div class="pvf-stat-lbl">Hari Tertinggi</div>
          <div><span class="pvf-stat-val" id="ch-max">-</span><span class="pvf-stat-unit"> kWh</span></div>
          <div class="hs-sub" id="ch-max-date">-</div>
        </div>
      </div>
      <div id="chart-consumption" style="min-height:220px"></div>
    </div>
    <div style="overflow-x:auto">
      <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Konsumsi</th><th>vs Rata-rata</th></tr></thead>
        <tbody id="consumption-table-body">
          <tr><td colspan="3" style="text-align:center;color:var(--muted)">Memuat...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ANALISIS MALAM VS SIANG -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Analisis Malam vs Siang</div><div class="card-sub">Bandingkan konsumsi saat PV tidak berproduksi vs produksi saat PV aktif</div></div>
      <span class="tag" id="an-verdict">-</span>
    </div>
    <div class="card-bd">
      <div class="pvf-stats" style="margin-bottom:14px">
        <div class="pvf-stat">
          <div class="pvf-stat-lbl"><?= icon('moon', 13) ?> Konsumsi Malam</div>
          <div><span class="pvf-stat-val" id="an-night-kwh" style="color:var(--indigo)">-</span><span class="pvf-stat-unit"> kWh</span></div>
          <div class="hs-sub" id="an-night-hours">- jam tanpa PV</div>
        </div>
        <div class="pvf-stat">
          <div class="pvf-stat-lbl"><?= icon('sun', 13) ?> Produksi Siang</div>
          <div><span class="pvf-stat-val" id="an-day-kwh" style="color:var(--amber2)">-</span><span class="pvf-stat-unit"> kWh</span></div>
          <div class="hs-sub" id="an-day-hours">- jam PV aktif</div>
        </div>
        <div class="pvf-stat">
          <div class="pvf-stat-lbl"><?= icon('plug', 13) ?> Total dari Grid</div>
          <div><span class="pvf-stat-val" id="an-grid-kwh" style="color:var(--rose)">-</span><span class="pvf-stat-unit"> kWh</span></div>
          <div class="hs-sub" id="an-grid-window">-</div>
        </div>
      </div>
      <div id="chart-flow-analysis" style="min-height:260px"></div>
      <div class="note-card" style="margin-top:14px" id="an-note">
        <?= icon('gauge', 16) ?>
        <div id="an-note-text">Memuat analisis...</div>
      </div>
    </div>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
// ── HELPERS ─────────────────────────────────────────────────────
const el  = id => document.getElementById(id);
const INVERTER_CAPACITY_W = 3000; // kapasitas inverter Anda, dipakai indikator beban vs kapasitas
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toFixed(d);

function sv(d, k) {
  if (!d || !d[k]) return 0;
  const v = parseFloat(d[k].state);
  return isNaN(v) ? 0 : v;
}
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }
function on(d, k) { return ss(d, k) === 'on'; }

// Energi: jika HA melaporkan satuan "Wh", konversi otomatis ke kWh
function ev(d, k) {
  if (!d || !d[k]) return 0;
  let v = parseFloat(d[k].state);
  if (isNaN(v)) return 0;
  if ((d[k].unit || '').toLowerCase() === 'wh') v = v / 1000;
  return v;
}

// Render satu baris alarm (value + warna)
function setAlarm(id, active, okText, badText) {
  const e = el(id);
  if (!e) return;
  e.textContent = active ? badText : okText;
  e.style.color = active ? 'var(--rose)' : 'var(--mint)';
}

// ── CLOCK ────────────────────────────────────────────────────────
function updateClock() {
  const now = new Date();
  set('clock', now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}));
  set('tb-title', 'AC Power Meter - ' + now.toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}));
}
setInterval(updateClock, 1000); updateClock();

// ── APEXCHARTS ───────────────────────────────────────────────────
const APEX_COMMON = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

const chartLoad = new ApexCharts(el('chart-load'), {
  series: [
    {name:'Daya', type:'area', data:[]},
    {name:'Tegangan', type:'line', data:[]},
  ],
  chart: {...APEX_COMMON.chart, type:'line', height:300},
  colors: ['#38bdf8', '#34d399'],
  stroke: {curve:'smooth', width:[2.5, 1.8]},
  fill: { type:['gradient','solid'], gradient:{shadeIntensity:1, opacityFrom:.30, opacityTo:.02} },
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: [
    { seriesName:'Daya', title:{text:'Watt', style:{color:'#0284c7'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0 },
    { seriesName:'Tegangan', opposite:true, title:{text:'Volt', style:{color:'#059669'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:180, max:260, forceNiceScale:true },
  ],
  grid: APEX_COMMON.grid,
  legend:{show:true, position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, markers:{width:10,height:10}},
  tooltip:{...APEX_COMMON.tooltip, shared:true, x:{format:'dd MMM HH:mm'},
    y:{formatter:(v,{seriesIndex})=> seriesIndex===0 ? fmt(v,0)+' W' : fmt(v,1)+' V'}},
});
chartLoad.render();

let lastVoltage = 0;
const chartVolt = new ApexCharts(el('g-voltage'), {
  series: [0],
  chart: {...APEX_COMMON.chart, type:'radialBar', height:180, sparkline:{enabled:true}},
  plotOptions:{radialBar:{
    startAngle: -100, endAngle: 100, hollow:{size:'65%'}, track:{background:'#e5e9f2', margin:0},
    dataLabels:{
      name:{show:true, offsetY:20, color:'#98a0b3', fontSize:'11px'},
      value:{offsetY:-5, fontSize:'24px', fontWeight:'700', color:'#171a24', formatter: () => fmt(lastVoltage,1)+' V'}
    }
  }},
  colors: ['#38bdf8'], stroke:{lineCap:'round'}, labels: ['Tegangan'],
});
chartVolt.render();

function voltToPct(v){ return Math.max(0, Math.min(100, ((v - 180) / (260 - 180)) * 100)); }

// ── RENDER STATES ────────────────────────────────────────────────
function renderStates(d) {
  if (!d || typeof d !== 'object') return;
  try {
    const v   = sv(d,'sensor.smartpowermeter_voltage');
    const a   = sv(d,'sensor.smartpowermeter_current');
    const w   = sv(d,'sensor.smartpowermeter_power');
    const pf  = sv(d,'sensor.smartpowermeter_power_factor');
    const hz  = sv(d,'sensor.smartpowermeter_frequency');

    const vStat = ss(d,'sensor.smartpowermeter_voltage_status');
    const lStat = ss(d,'sensor.smartpowermeter_load_status');

    // Alarm (4 + agregat)
    const alOv  = on(d,'binary_sensor.smartpowermeter_over_voltage_alarm');
    const alUv  = on(d,'binary_sensor.smartpowermeter_under_voltage_alarm');
    const alOp  = on(d,'binary_sensor.smartpowermeter_over_power_alarm');
    const alOt  = on(d,'binary_sensor.smartpowermeter_over_temperature_alarm');
    const alAny = on(d,'binary_sensor.smartpowermeter_any_alarm_active') || alOv || alUv || alOp || alOt;

    // Status perangkat (ESPHome connectivity)
    const devUp = ss(d,'binary_sensor.smartpowermeter_status') !== 'off';

    // Setpoint ambang (number)
    const spOv = sv(d,'number.smartpowermeter_over_voltage_setpoint');
    const spUv = sv(d,'number.smartpowermeter_under_voltage_setpoint');
    const spOp = sv(d,'number.smartpowermeter_over_power_setpoint');
    const spOt = sv(d,'number.smartpowermeter_over_temperature_setpoint');

    // Topbar
    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));

    // Badge sumber daya beban (Grid / Baterai / Solar)
    const src = ss(d,'sensor.power_source_status');
    const sb = el('src-badge');
    if (sb) {
      sb.textContent = 'Sumber: ' + src;
      const isGrid = src.indexOf('Grid') !== -1;
      sb.className = 'pill ' + (isGrid ? 'warn' : (src === '-' ? '' : 'ok'));
    }
    const badge = el('sys-badge');
    if (badge) {
      badge.textContent = alAny ? 'Alarm Aktif' : devUp ? 'Online' : 'Terputus';
      badge.className = 'pill ' + (alAny ? 'warn' : devUp ? 'ok' : 'err');
    }

    // Hero: metrik paling "berguna dilihat sekilas" — beban, konsumsi,
    // biaya, dan skor kesehatan tegangan (bukan angka V mentah).
    set('hero-status', `[ Kategori: ${lStat} ]`);
    set('h-w', fmt(w,0));  set('h-w-sub', `Kategori: ${lStat}`);

    const energyTodayKwh = ev(d,'sensor.energy_today_kwh');
    set('h-kwh', fmt(energyTodayKwh,2));
    set('h-kwh-sub', `Total akumulatif: ${fmt(ev(d,'sensor.smartpowermeter_energy'),1)} kWh`);

    // Biaya hari ini - pakai hasil hitung ulang backend (berdasarkan
    // autarky, konsisten dengan index.php), bukan sensor HA yang
    // menghitung dari total konsumsi tanpa memandang sumbernya.
    const ccs = d.computed?.cost_savings;
    set('h-cost', ccs ? fmt(ccs.pln_cost_today,0) : '-');
    set('h-cost-sub', ccs ? `${fmt(ccs.grid_kwh_today,2)} kWh dari grid` : 'Dari grid PLN');

    // KPI kualitas tegangan - pakai hasil backend (lib/metrics.php) yang
    // sudah menangani fallback estimasi kalau sensor HA 0/unavailable.
    const cvq = d.computed?.voltage_quality;
    let vq;
    if (cvq) {
      vq = cvq.score;
      set('h-vq-sub', cvq.estimated ? 'Estimasi panel' : 'Kestabilan V PLN/Inverter');
    } else {
      vq = sv(d,'sensor.voltage_quality_score');
      if (!vq || vq <= 0) {
        const dev = Math.abs(v - 220);
        vq = (v <= 0) ? 0 : Math.max(0, Math.min(100, 100 - dev * 2.5));
        set('h-vq-sub', 'Estimasi panel');
      } else {
        set('h-vq-sub', 'Kestabilan V PLN/Inverter');
      }
    }
    set('h-vq', fmt(vq,0));

    // KPI: detail teknis (tegangan mentah, arus, power factor) - masih
    // penting, tapi bukan lagi headline utama.
    set('k-v', fmt(v,1));
    set('k-v-sub', `Arus: ${fmt(a,2)} A`);
    const bVStatus = el('b-v-status');
    if (bVStatus) { bVStatus.textContent = vStat; }

    set('k-pf', fmt(pf,2));
    set('k-pf-sub', pf >= 0.95 ? 'Efisiensi sangat baik' : pf >= 0.85 ? 'Efisiensi baik' : 'Beban reaktif tinggi');

    set('k-hz', fmt(hz,1));

    // KPI puncak - Arus Maks kini dari sensor ASLI (bukan estimasi)
    const peakW = sv(d,'sensor.smartpowermeter_peak_power');
    const peakA = sv(d,'sensor.smartpowermeter_peak_current');
    set('k-peak-w', fmt(peakW,0));
    set('k-peak-w-sub', `Arus Maks: ${fmt(peakA || (peakW/220),2)} A`);

    // Chart live number
    set('c-w-now', fmt(w,0) + ' W');

    // Gauge tegangan
    let vColor = '#10b981';
    if (v < 200) vColor = '#f59e0b';
    if (v > 240) vColor = '#f43f5e';
    lastVoltage = v;
    chartVolt.updateOptions({ colors: [vColor] }, false, false);
    chartVolt.updateSeries([ voltToPct(v) ]);
    set('g-volt-note', `Ambang alarm: ${fmt(spUv,0)} V (bawah) / ${fmt(spOv,0)} V (atas)`);

    // ── BEBAN VS KAPASITAS INVERTER (3000W) ──
    const invPct = Math.min(100, (w / INVERTER_CAPACITY_W) * 100);
    set('inv-load-pct', fmt(invPct,0) + ' %');
    const invBar = el('inv-load-bar');
    if (invBar) {
      invBar.style.width = invPct.toFixed(1) + '%';
      invBar.className = 'fb-fill ' + (invPct >= 90 ? 'rose' : invPct >= 70 ? 'amber' : 'mint');
    }
    set('inv-load-note',
      invPct >= 90 ? 'Mendekati batas maksimum inverter!' :
      invPct >= 70 ? 'Beban tinggi - masih aman' :
      `Kapasitas tersisa: ${fmt(INVERTER_CAPACITY_W - w,0)} W`);

    // ── ALARM panel ──
    const agg = el('alarm-agg');
    if (agg) { agg.textContent = alAny ? 'BAHAYA' : 'AMAN'; agg.className = 'tag ' + (alAny ? 'bad' : 'ok'); }
    setAlarm('r-al-ov', alOv, 'Normal', 'Over-Volt');
    setAlarm('r-al-uv', alUv, 'Normal', 'Under-Volt');
    setAlarm('r-al-op', alOp, 'Normal', 'Overload');
    setAlarm('r-al-ot', alOt, 'Normal', 'Over-Temp');
    set('ctx-ov', `trip > ${fmt(spOv,1)} V`);
    set('ctx-uv', `trip < ${fmt(spUv,1)} V`);
    set('ctx-op', `trip > ${fmt(spOp,0)} W`);
    set('ctx-ot', `trip > ${fmt(spOt,0)} C (MPPT)`);

    // ── KUALITAS & PUNCAK ──
    set('r-v-max', fmt(sv(d,'sensor.smartpowermeter_peak_voltage'),1) + ' V');
    set('r-v-min', fmt(sv(d,'sensor.smartpowermeter_lowest_voltage'),1) + ' V');
    set('r-a-max', fmt(peakA,2) + ' A');
    set('r-w-max', fmt(peakW,0) + ' W');
    set('r-va',  fmt(sv(d,'sensor.apparent_power'),0) + ' VA');
    set('r-var', fmt(sv(d,'sensor.reactive_power'),0) + ' var');

    // ── KESEHATAN PERANGKAT ──
    const boxT = sv(d,'sensor.smartpowermeter_box_temperature');
    const boxH = sv(d,'sensor.smartpowermeter_box_humidity');
    el('d-box-t').innerHTML = fmt(boxT,1) + ' <small>C</small>';
    el('d-box-h').innerHTML = fmt(boxH,0) + ' <small>%</small>';
    el('d-esp-t').innerHTML = fmt(sv(d,'sensor.smartpowermeter_esp_temperature'),1) + ' <small>C</small>';
    el('d-wifi').innerHTML  = fmt(sv(d,'sensor.smartpowermeter_wifi_signal'),0) + ' <small>dBm</small>';
    set('d-uptime', ss(d,'sensor.smartpowermeter_uptime_human'));

    const devB = el('dev-badge');
    if (devB) { devB.textContent = devUp ? 'ONLINE' : 'OFFLINE'; devB.className = 'tag ' + (devUp ? 'ok' : 'bad'); }

  } catch(err) { console.error('renderStates error:', err); }
}

// ── HISTORY ──────────────────────────────────────────────────────
let currentRange = '24h';
const mapSeries = arr => Array.isArray(arr) ? arr.map(r => [ new Date(r.time).getTime(), parseFloat(r.value) ]) : [];

function updateHistoryStats(pData, vData) {
  set('c-points', `${pData.length} titik data`);
  if (pData.length) {
    const vals = pData.map(p => p[1]).filter(n => !isNaN(n));
    const avg = vals.reduce((s,n)=>s+n,0) / vals.length;
    el('st-w-avg').innerHTML = fmt(avg,0) + ' <small>W</small>';
    el('st-w-max').innerHTML = fmt(Math.max(...vals),0) + ' <small>W</small>';
  } else {
    el('st-w-avg').innerHTML = '- <small>W</small>';
    el('st-w-max').innerHTML = '- <small>W</small>';
  }
  if (vData.length) {
    const vals = vData.map(p => p[1]).filter(n => !isNaN(n));
    const avg = vals.reduce((s,n)=>s+n,0) / vals.length;
    el('st-v-avg').innerHTML   = fmt(avg,1) + ' <small>V</small>';
    el('st-v-range').innerHTML = fmt(Math.min(...vals),0) + '-' + fmt(Math.max(...vals),0) + ' <small>V</small>';
  } else {
    el('st-v-avg').innerHTML   = '- <small>V</small>';
    el('st-v-range').innerHTML = '- <small>V</small>';
  }
}

async function fetchHistory() {
  let pData = [], vData = [];
  try {
    const res = await fetch('proxy.php?type=influx_history&range=' + currentRange);
    if (res.ok) {
      const raw = await res.json();
      if (raw && !raw.error) {
        pData = mapSeries(raw.power);
        vData = mapSeries(raw.voltage);
        if (!pData.length && Array.isArray(raw.load)) pData = mapSeries(raw.load);
      }
    } else {
      const legacy = await fetch('proxy.php?type=influx_power_24h');
      if (legacy.ok) { const lr = await legacy.json(); if (lr && Array.isArray(lr.load)) pData = mapSeries(lr.load); }
    }
  } catch(e) { console.warn('History error:', e); }

  chartLoad.updateSeries([{name:'Daya', data:pData},{name:'Tegangan', data:vData}]);
  updateHistoryStats(pData, vData);
}

el('range-sel').addEventListener('click', e => {
  const btn = e.target.closest('.rb');
  if (!btn) return;
  currentRange = btn.dataset.r;
  document.querySelectorAll('.rb').forEach(b => b.classList.toggle('on', b === btn));
  fetchHistory();
});

// ── LIVE DATA: SSE (real-time push), fallback ke polling 15 detik ──
async function fetchStatesOnce() {
  try {
    const res = await fetch('proxy.php?type=ha_states');
    if (res.status === 401) { location.href = 'login.php'; return; }
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    if (data && !data.error) renderStates(data);
  } catch(e) {
    set('tb-sub', 'Gagal memuat - ' + new Date().toLocaleTimeString('id-ID'));
  }
}

let pollTimer = null;
let liveEs = null;
let lastLiveTs = Date.now();
function startPollingFallback() {
  if (pollTimer) return;
  console.warn('SSE tidak tersedia - pakai polling 15 detik sebagai cadangan.');
  fetchStatesOnce();
  pollTimer = setInterval(fetchStatesOnce, 15000);
}
function stopPollingFallback() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}
function connectSSE() {
  if (!window.EventSource) { startPollingFallback(); return; }
  if (liveEs) { try { liveEs.close(); } catch(e) {} }
  liveEs = new EventSource('proxy.php?type=sse');
  let sseWorking = false;
  let fallbackTimer = setTimeout(startPollingFallback, 6000);

  liveEs.onmessage = (ev) => {
    sseWorking = true;
    lastLiveTs = Date.now();
    clearTimeout(fallbackTimer);
    stopPollingFallback();
    try {
      const data = JSON.parse(ev.data);
      if (data && !data.error) renderStates(data);
    } catch (e) {}
  };

  // Heartbeat named event (bukan comment SSE) - jadi bisa dideteksi JS
  // buat watchdog di bawah, penjaga kalau koneksi macet diam-diam.
  liveEs.addEventListener('heartbeat', () => { lastLiveTs = Date.now(); });
  liveEs.addEventListener('reconnect', () => {});

  liveEs.onerror = () => {
    if (!sseWorking) {
      fetch('proxy.php?type=ha_states').then(r => {
        if (r.status === 401) { location.href = 'login.php'; }
      }).catch(() => {});
      startPollingFallback();
    }
  };
}

function startLive() {
  connectSSE();
  // Watchdog: kalau >30 detik nggak ada data ATAU heartbeat sama sekali,
  // paksa reconnect sendiri - ini menutup celah "macet diam-diam tanpa
  // error" yang bikin dashboard berhenti update total tanpa alasan jelas.
  setInterval(() => {
    if (Date.now() - lastLiveTs > 30000) {
      console.warn('SSE macet lebih dari 30 detik - reconnect paksa...');
      startPollingFallback();
      connectSSE();
      lastLiveTs = Date.now();
    }
  }, 10000);
}

startLive();
fetchHistory();
setInterval(fetchHistory, 300000);

// ── RIWAYAT KONSUMSI (kWh per hari, 7/30 hari) ──────────────────
const chartConsumption = new ApexCharts(el('chart-consumption'), {
  series: [{name:'Konsumsi (kWh)', data:[]}],
  chart: {background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif', type:'bar', height:220},
  colors: ['#0284c7'],
  plotOptions: {bar:{borderRadius:5, columnWidth:'55%'}},
  dataLabels: {enabled:false},
  xaxis: {type:'category', labels:{style:{colors:'#98a0b3', fontSize:'10px'}, rotate:-45}, axisBorder:{show:false}, axisTicks:{show:false}},
  yaxis: {title:{text:'kWh', style:{color:'#98a0b3'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}},
  grid: {borderColor:'#e3e7ef', strokeDashArray:4},
  tooltip: {theme:'light', y:{formatter:v=>v.toFixed(2)+' kWh'}},
});
chartConsumption.render();

let consumptionRange = '7d'; // default 7d dulu, user bisa pilih 30d
async function fetchConsumptionHistory() {
  try {
    const res = await fetch('proxy.php?type=influx_cost_history&range=' + consumptionRange);
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (!data || !Array.isArray(data.days) || !data.days.length) return;

    const days = data.days;
    const total = days.reduce((s,d) => s + d.energy_kwh, 0);
    const avg = total / days.length;
    const maxDay = days.reduce((m,d) => d.energy_kwh > m.energy_kwh ? d : m, days[0]);

    set('ch-total', total.toFixed(1));
    set('ch-avg', avg.toFixed(2));
    set('ch-max', maxDay.energy_kwh.toFixed(2));
    set('ch-max-date', new Date(maxDay.date).toLocaleDateString('id-ID', {weekday:'long', day:'numeric', month:'short'}));

    const labels = days.map(d => new Date(d.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}));
    chartConsumption.updateSeries([
      {name:'Konsumsi (kWh)', data: days.map((d,i) => ({x: labels[i], y: +d.energy_kwh.toFixed(2)}))}
    ]);

    const tbody = el('consumption-table-body');
    if (tbody) {
      tbody.innerHTML = days.slice().reverse().map(d => {
        const diff = d.energy_kwh - avg;
        const diffPct = avg > 0 ? (diff / avg * 100) : 0;
        const diffColor = diff > 0 ? 'var(--rose)' : 'var(--mint2)';
        const diffLabel = diff > 0 ? `+${diffPct.toFixed(0)}%` : `${diffPct.toFixed(0)}%`;
        return `<tr>
          <td>${new Date(d.date).toLocaleDateString('id-ID', {weekday:'short', day:'numeric', month:'short'})}</td>
          <td class="val-cell">${d.energy_kwh.toFixed(2)} kWh</td>
          <td class="val-cell" style="color:${diffColor}">${diffLabel}</td>
        </tr>`;
      }).join('');
    }
  } catch(e) { console.warn('Consumption history fetch error:', e); }
}

document.getElementById('range-sel-consumption').addEventListener('click', e => {
  const b = e.target.closest('.rb'); if (!b) return;
  consumptionRange = b.dataset.r;
  document.querySelectorAll('#range-sel-consumption .rb').forEach(x => x.classList.toggle('on', x === b));
  fetchConsumptionHistory();
});

setTimeout(fetchConsumptionHistory, 1500); // delayed: tidak block live data
setInterval(fetchConsumptionHistory, 600000);

// ── ANALISIS MALAM VS SIANG ──────────────────────────────────────
// Desain baru: cuma 2 garis bersih (PV & Beban) supaya gampang dibaca.
// Kapan grid dipakai ditunjukkan lewat ZONA BAYANGAN merah di
// background (bukan garis/area ke-3 yang bikin tumpang tindih).
const chartFlowAnalysis = new ApexCharts(el('chart-flow-analysis'), {
  series: [
    {name:'PV (W)', type:'area', data:[]},
    {name:'Beban (W)', type:'line', data:[]},
  ],
  chart: {background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif', type:'line', height:260},
  colors: ['#f59e0b', '#0284c7'],
  stroke: {curve:'smooth', width:[2, 2.5]},
  fill: {type:['gradient','solid'], gradient:{shadeIntensity:1,opacityFrom:.25,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: {type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}}, axisBorder:{show:false}, axisTicks:{show:false}},
  yaxis: {title:{text:'Watt', style:{color:'#98a0b3'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0},
  grid: {borderColor:'#e3e7ef', strokeDashArray:4},
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {theme:'light', shared:true, x:{format:'dd MMM HH:mm'}},
});
chartFlowAnalysis.render();

// Kelompokkan titik waktu yang berurutan dengan grid>ambang jadi
// rentang [awal,akhir] tunggal, supaya jadi 1 zona bayangan per periode
// (bukan berkedip-kedip tiap titik data).
function computeGridZones(points, threshold = 30) {
  const zones = [];
  let start = null;
  for (let i = 0; i < points.length; i++) {
    const t = new Date(points[i].time).getTime();
    if (points[i].grid > threshold) {
      if (start === null) start = t;
    } else if (start !== null) {
      zones.push({ x: start, x2: t });
      start = null;
    }
  }
  if (start !== null) zones.push({ x: start, x2: new Date(points[points.length-1].time).getTime() });
  return zones;
}

let flowRange = '24h';
async function fetchFlowAnalysis() {
  try {
    const res = await fetch('proxy.php?type=influx_flow_history&range=' + flowRange);
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (!data || !Array.isArray(data.points) || !data.points.length) return;

    set('an-night-kwh', data.night_consumption_kwh.toFixed(2));
    set('an-night-hours', `${data.night_hours.toFixed(1)} jam tanpa PV`);
    set('an-day-kwh', data.day_production_kwh.toFixed(2));
    set('an-day-hours', `${data.day_hours.toFixed(1)} jam PV aktif`);
    set('an-grid-kwh', data.grid_kwh.toFixed(2));

    const gridZones = computeGridZones(data.points);
    if (gridZones.length) {
      const first = new Date(gridZones[0].x).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
      const last  = new Date(gridZones[gridZones.length-1].x2).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
      set('an-grid-window', gridZones.length > 1 ? `${gridZones.length} periode, ${first} - ${last}` : `Sekitar jam ${first} - ${last}`);
    } else {
      set('an-grid-window', 'Tidak ada penggunaan grid signifikan');
    }

    const pvSeries   = data.points.map(p => [new Date(p.time).getTime(), p.pv]);
    const loadSeries = data.points.map(p => [new Date(p.time).getTime(), p.load]);
    chartFlowAnalysis.updateSeries([
      {name:'PV (W)', data: pvSeries},
      {name:'Beban (W)', data: loadSeries},
    ]);
    chartFlowAnalysis.updateOptions({
      annotations: {
        xaxis: gridZones.map(z => ({
          x: z.x, x2: z.x2,
          fillColor: '#e11d48', opacity: 0.12,
          label: { text: 'Grid', orientation:'horizontal', style: { color:'#e11d48', background:'transparent', fontSize:'10px', fontWeight:700 } }
        }))
      }
    }, false, false);

    // Verdict + catatan otomatis
    const verdict = el('an-verdict');
    const deficit = data.night_consumption_kwh - 0; // baterai usable tidak diketahui pasti dari sini
    const noteEl = el('an-note-text');
    if (data.grid_kwh > data.night_consumption_kwh * 0.15) {
      if (verdict) { verdict.textContent = 'Grid Signifikan'; verdict.className = 'tag bad'; }
      if (noteEl) noteEl.textContent = `Konsumsi malam (${data.night_consumption_kwh.toFixed(1)} kWh) tampaknya melebihi kapasitas baterai yang benar-benar bisa dipakai (usable capacity biasanya lebih kecil dari kapasitas design karena cutoff voltage, SOH, dan rugi konversi). Grid ikut menutup ${data.grid_kwh.toFixed(1)} kWh - cek halaman Baterai untuk lihat SOC minimum yang tercapai semalam.`;
    } else {
      if (verdict) { verdict.textContent = 'Normal'; verdict.className = 'tag ok'; }
      if (noteEl) noteEl.textContent = `Baterai + solar berhasil menutup hampir seluruh kebutuhan pada periode ini. Grid cuma menyumbang ${data.grid_kwh.toFixed(1)} kWh dari total ${(data.night_consumption_kwh).toFixed(1)} kWh konsumsi malam.`;
    }
  } catch(e) { console.warn('Flow analysis fetch error:', e); }
}

setTimeout(fetchFlowAnalysis, 3000); // delayed: paling berat, load terakhir
setInterval(fetchFlowAnalysis, 600000);
</script>
</body>
</html>