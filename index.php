<?php
require __DIR__ . '/_session.php';

// Logout dicek DULU biar selalu jalan
if (isset($_GET['logout'])) {
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }

// ── Konfigurasi partials untuk halaman ini ─────────────────────
$page_title = 'Voltara EMS';
$active     = 'overview';
$tb_h1      = 'Voltara EMS';
$tb_sub_init = 'Menghubungkan...';
$tb_pills   = [
    ['id' => 'src-badge', 'class' => 'pill',    'text' => 'Sumber: -'],
    ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => 'Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<!-- HERO -->
<div class="hero">
  <div class="hero-top">
    <div class="hero-eyebrow" id="hero-greet">Memuat...</div>
    <div class="hero-title">Sistem energi hibrida berjalan <em id="hero-word">...</em></div>
  </div>
  <div class="hero-stats">
    <div class="hs">
      <div class="hs-lbl"><?= icon('sun', 13) ?> PV Power</div>
      <div><span class="hs-val" id="h-pv">-</span><span class="hs-unit"> W</span></div>
      <div class="hs-sub" id="h-pv-sub">-</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('battery', 13) ?> Battery SOC</div>
      <div><span class="hs-val" id="h-soc">-</span><span class="hs-unit"> %</span></div>
      <div class="hs-sub" id="h-soc-sub">-</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('bolt', 13) ?> Beban AC</div>
      <div><span class="hs-val" id="h-load">-</span><span class="hs-unit"> W</span></div>
      <div class="hs-sub" id="h-load-sub">-</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('leaf', 13) ?> Autarki Hari Ini</div>
      <div><span class="hs-val" id="h-autarky">-</span><span class="hs-unit"> %</span></div>
      <div class="hs-sub">Mandiri dari PLN</div>
    </div>
    <div class="hs">
      <div class="hs-lbl"><?= icon('coin', 13) ?> Hemat Hari Ini</div>
      <div><span class="hs-val" id="h-save">-</span><span class="hs-unit"> Rp</span></div>
      <div class="hs-sub" id="h-save-sub">-</div>
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="content">

  <!-- PERKIRAAN PV (cuaca) -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Perkiraan PV Hari Ini</div><div class="card-sub" id="pvf-loc">Memuat lokasi...</div></div>
      <span class="tag" id="pvf-badge">-</span>
    </div>
    <div class="card-bd">
      <div class="pvf-stats">
        <div class="pvf-stat">
          <div class="pvf-stat-lbl">Dihasilkan</div>
          <div><span class="pvf-stat-val" id="pvf-generated">-</span><span class="pvf-stat-unit"> kWh</span></div>
        </div>
        <div class="pvf-stat">
          <div class="pvf-stat-lbl">Sisa Perkiraan</div>
          <div><span class="pvf-stat-val" id="pvf-remaining">-</span><span class="pvf-stat-unit"> kWh</span></div>
        </div>
      </div>
      <div class="pvf-weather-strip" id="pvf-weather-strip"></div>
      <div id="chart-pv-forecast" style="min-height:160px;margin-bottom:6px"></div>
    </div>
  </div>

  <!-- KPI ROW -->
  <div class="kpi-row">
    <div class="kpi amber">
      <div class="kpi-head"><div class="kpi-ico"><?= icon('sun', 18) ?></div><span class="kpi-badge neu" id="b-solar">Solar</span></div>
      <div class="kpi-lbl">Energi Solar Hari Ini</div>
      <div><span class="kpi-val" id="kv-solar">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub" id="ks-solar">Daya: - W</div>
      <div class="kpi-spark" id="sp-solar"></div>
    </div>
    <div class="kpi mint">
      <div class="kpi-head"><div class="kpi-ico"><?= icon('battery', 18) ?></div><span class="kpi-badge neu" id="b-batt">Baterai</span></div>
      <div class="kpi-lbl">State of Charge</div>
      <div><span class="kpi-val" id="kv-soc">-</span><span class="kpi-unit">%</span></div>
      <div class="kpi-sub" id="ks-soc">- kWh tersisa</div>
      <div class="kpi-spark" id="sp-soc"></div>
    </div>
    <div class="kpi sky">
      <div class="kpi-head"><div class="kpi-ico"><?= icon('bolt', 18) ?></div><span class="kpi-badge neu" id="b-ac">AC</span></div>
      <div class="kpi-lbl">Konsumsi AC Hari Ini</div>
      <div><span class="kpi-val" id="kv-energy">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub" id="ks-energy">Daya: - W</div>
      <div class="kpi-spark" id="sp-power"></div>
    </div>
    <div class="kpi rose">
      <div class="kpi-head"><div class="kpi-ico"><?= icon('coin', 18) ?></div><span class="kpi-badge up">Hemat</span></div>
      <div class="kpi-lbl">Biaya PLN Hari Ini</div>
      <div><span class="kpi-val" id="kv-cost">-</span><span class="kpi-unit">Rp</span></div>
      <div class="kpi-sub" id="ks-cost">Hemat mandiri: - Rp</div>
      <div class="kpi-spark" id="sp-cost"></div>
    </div>
  </div>

  <!-- MAIN GRID -->
  <div class="grid2">

    <!-- CHART UTAMA -->
    <div class="card">
      <div class="card-hd">
        <div>
          <div class="card-ttl">PV / Beban AC / SOC Baterai</div>
          <div class="card-sub">24 jam terakhir</div>
        </div>
        <span class="tag">Live</span>
      </div>
      <div class="card-bd">
        <div class="chart-hero">
          <div><span class="chart-big" id="chart-pv-now">- W</span>
               <span style="font-size:12px;color:var(--muted);margin-left:8px">PV sekarang</span></div>
          <div class="chart-note" id="chart-note">Memuat data historis...</div>
        </div>
        <div id="chart-main"></div>
      </div>
    </div>

    <!-- ENERGY FLOW -->
    <div class="card">
      <div class="card-hd">
        <div><div class="card-ttl">Aliran Energi</div><div class="card-sub">Real-time</div></div>
        <span class="dot"></span>
      </div>
      <div class="card-bd">
        <div class="flow-wrap">
          <div class="fn solar"><span class="fn-ico"><?= icon('sun', 20) ?></span><div class="fn-lbl">Solar PV</div><div class="fn-val" id="fl-pv" style="color:var(--amber)">-</div><div class="fn-unit">W</div></div>
          <div class="fn batt"><span class="fn-ico"><?= icon('battery', 20) ?></span><div class="fn-lbl">Baterai</div><div class="fn-val" id="fl-batt" style="color:var(--mint)">-</div><div class="fn-unit">W</div></div>
          <div class="fn home"><span class="fn-ico"><?= icon('home', 20) ?></span><div class="fn-lbl">Beban</div><div class="fn-val" id="fl-load" style="color:var(--sky)">-</div><div class="fn-unit">W</div></div>
          <div class="fn grid"><span class="fn-ico"><?= icon('plug', 20) ?></span><div class="fn-lbl">Grid PLN</div><div class="fn-val" id="fl-grid" style="color:var(--indigo)">-</div><div class="fn-unit">W</div></div>
        </div>

        <!-- Progress bars aliran -->
        <div class="flow-bars">
          <div class="fb">
            <div class="fb-lbl">PV</div>
            <div class="fb-track"><div class="fb-fill amber" id="fb-pv" style="width:0%"></div></div>
            <div class="fb-val" id="fv-pv">- W</div>
          </div>
          <div class="fb">
            <div class="fb-lbl">Baterai</div>
            <div class="fb-track"><div class="fb-fill mint" id="fb-batt" style="width:0%"></div></div>
            <div class="fb-val" id="fv-batt">- W</div>
          </div>
          <div class="fb">
            <div class="fb-lbl">Beban</div>
            <div class="fb-track"><div class="fb-fill sky" id="fb-load" style="width:0%"></div></div>
            <div class="fb-val" id="fv-load">- W</div>
          </div>
        </div>

        <div class="src-badge">
          <div>Sumber Daya Aktif</div>
          <div class="src-val" id="src-val">-</div>
        </div>
      </div>
    </div>
  </div>

  <!-- KESEHATAN SISTEM (konsolidasi: kualitas, lingkungan, status, diagnostik) -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Kesehatan &amp; Dampak Sistem</div><div class="card-sub">Kualitas daya, lingkungan, dan status perangkat</div></div>
    </div>
    <div class="card-bd">
      <div class="health-grid">

        <!-- Gauge kualitas -->
        <div class="health-col">
          <div class="gauge-wrap"><div id="g-voltage"></div>
            <div class="gauge-info">
              <div class="gauge-name">Tegangan AC</div>
              <div class="gauge-desc" id="gd-voltage">V: - V<br>Status: -</div>
            </div>
          </div>
          <div class="gauge-wrap" style="border-top:1px solid var(--rim);padding-top:10px;margin-top:6px">
            <div id="g-inverter"></div>
            <div class="gauge-info">
              <div class="gauge-name">Efisiensi MPPT (Solar ke Baterai)</div>
              <div class="gauge-desc" id="gd-inverter">PV masuk ke Baterai</div>
            </div>
          </div>
        </div>

        <!-- Dampak lingkungan -->
        <div class="health-col">
          <div class="diag-row"><span class="dl"><?= icon('leaf', 14) ?> CO2 Dihindari Hari Ini</span><span class="dv" id="e-co2d">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('leaf', 14) ?> CO2 Dihindari Bulan Ini</span><span class="dv" id="e-co2m">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('coin', 14) ?> Nilai Solar Bulan Ini</span><span class="dv" id="e-sval">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('recycle', 14) ?> Round-Trip Efficiency</span><span class="dv" id="e-rte">-</span></div>
        </div>

        <!-- Status sistem -->
        <div class="health-col">
          <div class="diag-row"><span class="dl"><?= icon('clock', 14) ?> Uptime</span><span class="dv" id="st-uptime">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('wifi', 14) ?> WiFi Signal</span><span class="dv" id="st-wifi">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('thermo', 14) ?> ESP32 Temp</span><span class="dv" id="st-esp">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('sun', 14) ?> MPPT Status</span><span class="dv" id="st-mppt">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('gauge', 14) ?> MPPT Mode</span><span class="dv" id="st-mode">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('alert-triangle', 14) ?> Alarm</span><span class="dv" id="st-alarm">-</span></div>
          <div class="diag-row"><span class="dl"><?= icon('battery', 14) ?> Runtime Baterai</span><span class="dv" id="st-runtime">-</span></div>
        </div>

      </div>
    </div>
  </div>

</div><!-- /content -->
</main>
</div><!-- /layout -->

<script>
// ── HELPERS ─────────────────────────────────────────────────────
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const setHTML = (id, html) => { const e = el(id); if (e) e.innerHTML = html; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toFixed(d);
const fmtK = v => (!v || isNaN(v)) ? '-' : v >= 1000 ? (v/1000).toFixed(1)+'k' : Math.round(v).toString();
const pct = (v, max) => Math.min(100, Math.max(0, (v / max) * 100)).toFixed(1);

// Panah kecil buat indikator arah (charging/discharging, import grid) —
// SVG inline, bukan karakter teks, supaya selalu tampil rapi di semua device.
const ARROW_UP   = '<svg width="11" height="11" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px"><path d="M12 20V4M6 10l6-6 6 6" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
const ARROW_DOWN = '<svg width="11" height="11" viewBox="0 0 24 24" style="vertical-align:-1px;margin-right:3px"><path d="M12 4v16M6 14l6 6 6-6" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

function sv(d, k) {
  if (!d || !d[k]) return 0;
  const v = parseFloat(d[k].state);
  return isNaN(v) ? 0 : v;
}
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }

// ── CLOCK ────────────────────────────────────────────────────────
function updateClock() {
  const now = new Date();
  const h = now.getHours();
  set('clock', now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}));
  set('hero-greet',
    h < 5  ? 'Selamat dini hari' :
    h < 11 ? 'Selamat pagi' :
    h < 15 ? 'Selamat siang' :
    h < 18 ? 'Selamat sore' : 'Selamat malam'
  );
  set('tb-title', `Voltara EMS - ${now.toLocaleDateString('id-ID', {weekday:'long',day:'numeric',month:'short',year:'numeric'})}`);
}
setInterval(updateClock, 1000); updateClock();

// ── APEXCHARTS INIT ──────────────────────────────────────────────
const APEX_COMMON = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

// Sparklines
function mkSpark(divId, color) {
  const e = el(divId); if (!e) return null;
  const c = new ApexCharts(e, {
    series:[{data:[]}],
    chart:{...APEX_COMMON.chart, type:'area', height:44, sparkline:{enabled:true}},
    stroke:{curve:'smooth',width:2},
    fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:.25,opacityTo:.02}},
    colors:[color], tooltip:{enabled:false},
  });
  c.render(); return c;
}
const spSolar = mkSpark('sp-solar','#f59e0b');
const spSoc   = mkSpark('sp-soc',  '#10b981');
const spPow   = mkSpark('sp-power','#0284c7');
const spCost  = mkSpark('sp-cost', '#e11d48');

// Radial gauge
function mkGauge(divId, color, label) {
  const e = el(divId); if (!e) return null;
  const c = new ApexCharts(e, {
    series:[0],
    chart:{...APEX_COMMON.chart, type:'radialBar', height:130, sparkline:{enabled:true}},
    plotOptions:{radialBar:{
      hollow:{size:'60%'},
      dataLabels:{name:{show:false},value:{offsetY:5,fontSize:'16px',fontWeight:'700',color:'#171a24',formatter:v => v+'%'}},
      track:{background:'#e5e9f2'},
    }},
    colors:[color],
    stroke:{lineCap:'round'},
  });
  c.render(); return c;
}
const gVoltage  = mkGauge('g-voltage',  '#f59e0b');
const gInverter = mkGauge('g-inverter', '#10b981');

// Main multi-axis chart
const mainChart = new ApexCharts(el('chart-main'), {
  series: [
    {name:'PV Power (W)',    type:'area', data:[]},
    {name:'Beban AC (W)',   type:'area', data:[]},
    {name:'SOC Baterai (%)',type:'line', data:[]},
  ],
  chart: {...APEX_COMMON.chart, type:'line', height:240},
  colors: ['#f59e0b','#0284c7','#10b981'],
  stroke: {curve:'smooth', width:[2,2,2.5]},
  fill:   {type:['gradient','gradient','solid'], gradient:{shadeIntensity:1,opacityFrom:.2,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: {
    type:'datetime',
    labels:{datetimeUTC:false, style:{colors:'#98a0b3'}, datetimeFormatter:{hour:'HH:mm'}},
    axisBorder:{show:false}, axisTicks:{show:false},
  },
  yaxis: [
    {title:{text:'Watt',style:{color:'#f59e0b'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0},
    {show:false},
    {opposite:true, title:{text:'SOC %',style:{color:'#10b981'}}, min:0, max:100, labels:{style:{colors:'#10b981'}, formatter:v=>Math.round(v)}},
  ],
  grid: APEX_COMMON.grid,
  legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, itemMargin:{horizontal:10}},
  tooltip:{...APEX_COMMON.tooltip, shared:true, x:{format:'HH:mm dd MMM'}},
});
mainChart.render();

// Chart perkiraan PV: prediksi (dari cuaca) vs aktual
const chartPvForecast = new ApexCharts(el('chart-pv-forecast'), {
  series: [
    {name:'Prediksi', type:'line', data:[]},
    {name:'Aktual',   type:'area', data:[]},
  ],
  chart: {...APEX_COMMON.chart, type:'line', height:160},
  colors: ['#f59e0b', '#d97706'],
  stroke: {curve:'smooth', width:[2, 2], dashArray:[6, 0]},
  fill: {type:['solid','gradient'], opacity:[1,.9], gradient:{shadeIntensity:1,opacityFrom:.25,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: {
    type:'datetime',
    labels:{datetimeUTC:false, style:{colors:'#98a0b3'}, datetimeFormatter:{hour:'HH:mm'}},
    axisBorder:{show:false}, axisTicks:{show:false},
  },
  yaxis: {title:{text:'Watt', style:{color:'#98a0b3'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0},
  grid: APEX_COMMON.grid,
  legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip:{...APEX_COMMON.tooltip, shared:true, x:{format:'HH:mm'}},
});
chartPvForecast.render();

// Ikon cuaca WMO -> nama ikon di partials/icons.php
function weatherIconFor(code) {
  if (code === 0) return {icon:'sun', cls:''};
  if (code === 1 || code === 2) return {icon:'cloud-sun', cls:''};
  if (code === 3 || (code >= 45 && code <= 48)) return {icon:'cloud', cls:'cloudy'};
  if ((code >= 51 && code <= 67) || (code >= 80 && code <= 99)) return {icon:'cloud-rain', cls:'rain'};
  return {icon:'cloud-sun', cls:''};
}
// SVG mini generator di sisi klien (icon set sama dengan partials/icons.php,
// disalin ringkas supaya tidak perlu round-trip ke server tiap render)
const WEATHER_SVG = {
  sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
  'cloud-sun': '<circle cx="8" cy="9" r="3"/><path d="M8 3v1.2M3.5 6.5l.9.9M2 9h1.2M14 9h.5a3.5 3.5 0 0 1 0 7H8a4 4 0 0 1-.5-8"/>',
  cloud: '<path d="M6.5 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 16.6 9 4.5 4.5 0 0 1 16 18Z"/>',
  'cloud-rain': '<path d="M6.5 15a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 16.6 6 4.5 4.5 0 0 1 16 15Z"/><path d="M8 19l-1 2M12 19l-1 2M16 19l-1 2"/>',
};
function weatherSvg(name, size=18) {
  return `<svg width="${size}" height="${size}" viewBox="0 0 24 24" class="icn"><g fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${WEATHER_SVG[name] || WEATHER_SVG.sun}</g></svg>`;
}


// ── RENDER STATES ────────────────────────────────────────────────
let maxPV = 1500, maxLoad = 2500;
let latestPredictedTodayKwh = 0; // di-update tiap 30 menit oleh fetchPvForecast(),
                                  // dipakai renderStates() supaya "Sisa Perkiraan"
                                  // tetap sinkron dgn "Dihasilkan" yang update real-time

function renderStates(d) {
  if (!d || typeof d !== 'object') return;

  try {
    // Kalkulasi: pakai hasil backend (lib/metrics.php via proxy.php) kalau
    // tersedia — supaya identik dengan mppt.php & cron alert. Fallback ke
    // hitung lokal kalau proxy.php lama belum punya key "computed".
    const cpf = d.computed?.power_flow;
    const csv = d.computed?.savings;
    const cb  = d.computed?.battery_combined;
    const cps = d.computed?.power_source;
    const batV = cpf ? cpf.battery_voltage : sv(d,'sensor.smartpowermeter_mppt_battery_voltage');
    const chgA = cpf ? cpf.charging_current : sv(d,'sensor.smartpowermeter_mppt_charging_current');
    const pvPow   = cpf ? cpf.pv_power       : (sv(d,'sensor.mppt_pv_power')       || (batV * chgA / 0.96));
    const chgPow  = cpf ? cpf.charging_power : (sv(d,'sensor.mppt_charging_power') || (batV * chgA));
    // SOC & daya baterai: pakai GABUNGAN 2 bank (Huawei + Jikong), bukan
    // Huawei saja — sebelumnya overview cuma baca huawei_soc/huawei_power
    // sehingga kalau Jikong yang sedang discharge, angkanya tidak kelihatan.
    const soc     = cb ? cb.soc_avg : sv(d,'sensor.huawei_soc');
    const acLoad  = sv(d,'sensor.smartpowermeter_power');
    const battPow = cb ? cb.total_power : sv(d,'sensor.huawei_power');
    const netGrid = cps ? cps.grid_power : sv(d,'sensor.net_grid_power');
    const autarky = csv ? csv.autarky_today : sv(d,'sensor.autarky_today');
    const solarDay= sv(d,'sensor.mppt_daily_energy') || sv(d,'sensor.mppt_solar_daily_kwh');
    const energyDay = csv ? csv.energy_today_kwh : sv(d,'sensor.energy_today_kwh');
    // Biaya PLN & Hemat: pakai hasil hitung ulang backend (berdasarkan
    // autarky_today yang terbukti benar), bukan sensor.pln_cost_today /
    // sensor.solar_value_today HA yang ternyata menghitung dari total
    // konsumsi tanpa memandang sumbernya (lihat compute_cost_savings).
    const ccs = d.computed?.cost_savings;
    const costDay  = ccs ? ccs.pln_cost_today : (csv ? csv.pln_cost_today : sv(d,'sensor.pln_cost_today'));
    const solarVal = ccs ? ccs.savings_today  : (csv ? csv.solar_value_today : sv(d,'sensor.solar_value_today'));
    const energyRem = cb ? cb.total_remaining_kwh : (csv ? csv.energy_remaining_kwh : sv(d,'sensor.huawei_energy_remaining'));

    // System status
    const online  = ss(d,'binary_sensor.voltara_system_online') === 'on';
    const alarm   = ss(d,'binary_sensor.smartpowermeter_any_alarm_active') === 'on';
    const mpptOn  = ss(d,'binary_sensor.smartpowermeter_mppt_online') === 'on' ||
                    ss(d,'binary_sensor.voltara_system_online') === 'on';

    // Topbar & sidebar
    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));

    // Badge sumber daya beban (Grid / Baterai / Solar) — pakai hasil hitung
    // backend (lib/metrics.php: compute_power_source) yang mempertimbangkan
    // GABUNGAN 2 bank baterai + neraca energi, bukan sensor HA mentah yang
    // dibuat waktu sistem masih 1 bank.
    const srcStat = cps ? cps.label : ss(d,'sensor.power_source_status');
    const srcBadge = el('src-badge');
    if (srcBadge) {
      srcBadge.textContent = 'Sumber: ' + srcStat;
      const isGrid = cps ? cps.source === 'grid' : srcStat.indexOf('Grid') !== -1;
      srcBadge.className = 'pill ' + (isGrid ? 'warn' : (srcStat === '-' ? '' : 'ok'));
    }

    const badge = el('sys-badge');
    if (badge) { badge.textContent = alarm ? 'Alarm Aktif' : online ? 'Online' : 'Offline';
      badge.className = 'pill ' + (alarm ? 'warn' : online ? 'ok' : 'err'); }
    const pill = el('sys-pill'); if (pill) pill.className = 'sys-pill' + (online?'':' off');
    const dot  = el('sys-dot');  if (dot)  dot.className  = 'dot' + (online?'':' off');
    const txt  = el('sys-txt');  if (txt)  { txt.textContent = online ? 'Semua Sistem Online' : 'Sistem Offline'; txt.className = 'sys-txt' + (online?'':' off'); }

    // Hero
    const word = el('hero-word');
    if (word) {
      word.textContent = alarm ? 'perlu perhatian' : !online ? 'offline' : pvPow > 100 ? 'sangat baik' : 'normal';
      word.style.color = alarm ? '#f97316' : !online ? '#ef4444' : '#10b981';
    }
    set('h-pv', fmt(pvPow,0)); set('h-pv-sub', ss(d,'sensor.smartpowermeter_mppt_charging_mode'));
    set('h-soc', fmt(soc,0));  set('h-soc-sub', cb ? (cb.banks_active + ' bank aktif - ' + (battPow < -20 ? 'Discharging' : battPow > 20 ? 'Charging' : 'Idle')) : ss(d,'sensor.huawei_state_mode'));
    set('h-load', fmt(acLoad,0)); set('h-load-sub', ss(d,'sensor.smartpowermeter_load_status'));
    set('h-autarky', fmt(autarky,1));
    set('h-save', fmtK(solarVal)); set('h-save-sub', `Biaya PLN: Rp ${fmtK(costDay)}`);

    // KPI
    set('kv-solar', fmt(solarDay,2)); set('ks-solar', `Daya: ${fmt(pvPow,0)} W`);
    // Sinkron dgn kartu Perkiraan PV — real-time, bukan ikut jadwal 30 menit
    // fetchPvForecast() (itu cuma buat data cuaca/prediksi, bukan angka aktual).
    set('pvf-generated', fmt(solarDay,2));
    set('pvf-remaining', fmt(Math.max(0, latestPredictedTodayKwh - solarDay),2));
    const solarBadge = el('b-solar');
    if (solarBadge) { solarBadge.textContent = pvPow > 50 ? 'Aktif' : 'Standby';
      solarBadge.className = 'kpi-badge ' + (pvPow > 50 ? 'up' : 'neu'); }

    set('kv-soc', fmt(soc,0)); set('ks-soc', `${fmt(energyRem,2)} kWh tersisa`);
    const battBadge = el('b-batt');
    if (battBadge) { battBadge.textContent = soc >= 80 ? 'Penuh' : soc >= 30 ? 'Normal' : 'Rendah';
      battBadge.className = 'kpi-badge ' + (soc >= 50 ? 'up' : 'neu'); }

    set('kv-energy', fmt(energyDay,2)); set('ks-energy', `Daya: ${fmt(acLoad,0)} W`);
    set('kv-cost', fmtK(costDay)); set('ks-cost', `Hemat mandiri: Rp ${fmtK(solarVal)}`);

    // Chart topbar
    set('chart-pv-now', fmt(pvPow,0) + ' W');
    set('chart-note', `Mode: ${ss(d,'sensor.smartpowermeter_mppt_charging_mode')} - SOC: ${fmt(soc,0)}% - Bat: ${fmt(batV,1)}V`);

    // Energy flow nodes
    set('fl-pv',   fmt(pvPow,0));
    setHTML('fl-batt', (battPow < -10 ? ARROW_DOWN : battPow > 10 ? ARROW_UP : '') + fmt(Math.abs(battPow),0));
    set('fl-load', fmt(acLoad,0));
    setHTML('fl-grid', (netGrid > 10 ? ARROW_DOWN : '') + fmt(Math.abs(netGrid),0));
    set('src-val', srcStat);

    // Progress bars (max dinamis)
    if (pvPow  > maxPV)   maxPV  = pvPow  * 1.1;
    if (acLoad > maxLoad) maxLoad= acLoad * 1.1;
    const setBar = (id, pct) => { const e = el(id); if (e) e.style.width = pct + '%'; };
    setBar('fb-pv',   pct(pvPow,   maxPV));
    setBar('fb-batt', pct(Math.abs(battPow), maxLoad));
    setBar('fb-load', pct(acLoad,  maxLoad));
    set('fv-pv',   fmt(pvPow,0) + ' W');
    set('fv-batt', fmt(Math.abs(battPow),0) + ' W');
    set('fv-load', fmt(acLoad,0) + ' W');

    // Gauges
    const voltQ = sv(d,'sensor.voltage_quality_score') || 100;
    const invEff= sv(d,'sensor.inverter_efficiency') || 0;
    if (gVoltage)  gVoltage.updateSeries([Math.round(voltQ)]);
    if (gInverter) gInverter.updateSeries([Math.round(invEff)]);
    set('gd-voltage', `V: ${fmt(sv(d,'sensor.smartpowermeter_voltage'),1)} V - ${ss(d,'sensor.smartpowermeter_voltage_status')}`);
    set('gd-inverter', `PV: ${fmt(pvPow,0)} W ke Charge: ${fmt(chgPow,0)} W`);

    // Dampak lingkungan
    set('e-co2d', fmt(csv ? csv.co2_avoided_today : sv(d,'sensor.co2_avoided_today'),2) + ' kg');
    set('e-co2m', fmt(csv ? csv.co2_avoided_month : sv(d,'sensor.co2_avoided_month'),2) + ' kg');
    set('e-sval', 'Rp ' + fmtK(csv ? csv.solar_value_month : sv(d,'sensor.solar_value_month')));
    set('e-rte',  fmt(sv(d,'sensor.battery_round_trip_efficiency') || sv(d,'sensor.round_trip_efficiency'),1) + ' %');

    // Status sistem
    set('st-uptime',  ss(d,'sensor.smartpowermeter_uptime_human'));
    set('st-wifi',    fmt(sv(d,'sensor.smartpowermeter_wifi_signal'),0) + ' dBm');
    set('st-esp',     fmt(sv(d,'sensor.smartpowermeter_esp_temperature'),1) + ' C');
    set('st-mppt',    mpptOn ? 'Online' : 'Offline');
    set('st-mode',    ss(d,'sensor.smartpowermeter_mppt_charging_mode'));
    set('st-alarm',   alarm ? 'ADA ALARM' : 'Normal');
    set('st-runtime', ss(d,'sensor.battery_runtime_estimate'));

    // Sparkline update (inject live point)
    const injectSpark = (chart, val) => {
      if (!chart || isNaN(val)) return;
      try {
        const cur = chart.w?.globals?.series?.[0] || [];
        const next = [...cur.slice(-11), val];
        chart.updateSeries([{data:next}]);
      } catch(e){}
    };
    injectSpark(spSolar, solarDay);
    injectSpark(spSoc,   soc);
    injectSpark(spPow,   acLoad);
    injectSpark(spCost,  costDay);

  } catch(err) { console.error('renderStates error:', err); }
}

// ── CHART DATA dari Influx ───────────────────────────────────────
async function fetchChartData() {
  try {
    const [resPow, resSoc] = await Promise.allSettled([
      fetch('proxy.php?type=influx_power_24h'),
      fetch('proxy.php?type=influx_soc_24h'),
    ]);

    let pvSeries = [], loadSeries = [], socSeries = [];

    if (resPow.status === 'fulfilled' && resPow.value.ok) {
      const raw = await resPow.value.json();
      if (raw && typeof raw === 'object') {
        const pvArr   = Array.isArray(raw.pv_in) ? raw.pv_in : (Array.isArray(raw.pv) ? raw.pv : []);
        const loadArr = Array.isArray(raw.load)  ? raw.load : [];
        pvSeries   = pvArr.map(r   => [new Date(r.time).getTime(), r.value]);
        loadSeries = loadArr.map(r  => [new Date(r.time).getTime(), r.value]);
      }
    }

    if (resSoc.status === 'fulfilled' && resSoc.value.ok) {
      const raw = await resSoc.value.json();
      if (raw && typeof raw === 'object') {
        const socArr = Array.isArray(raw.soc) ? raw.soc : [];
        socSeries = socArr.map(r => [new Date(r.time).getTime(), r.value]);
      }
    }

    if (pvSeries.length > 0 || loadSeries.length > 0 || socSeries.length > 0) {
      mainChart.updateSeries([
        {name:'PV Power (W)',     data: pvSeries},
        {name:'Beban AC (W)',    data: loadSeries},
        {name:'SOC Baterai (%)', data: socSeries},
      ]);
    }

    if (spSolar && pvSeries.length >= 6) {
      const tail = pvSeries.slice(-12).map(p => p[1]);
      spSolar.updateSeries([{data: tail}]);
    }
    if (spPow && loadSeries.length >= 6) {
      const tail = loadSeries.slice(-12).map(p => p[1]);
      spPow.updateSeries([{data: tail}]);
    }
    if (spSoc && socSeries.length >= 6) {
      const tail = socSeries.slice(-12).map(p => p[1]);
      spSoc.updateSeries([{data: tail}]);
    }

  } catch(e) { console.warn('Chart error:', e); }
}

// ── PERKIRAAN PV (cuaca) ─────────────────────────────────────────
async function fetchPvForecast() {
  try {
    const [resForecast, resActual] = await Promise.allSettled([
      fetch('proxy.php?type=weather_forecast'),
      fetch('proxy.php?type=influx_power_24h'),
    ]);

    let predSeries = [], actSeries = [], hours = [];

    if (resForecast.status === 'fulfilled' && resForecast.value.ok) {
      const fc = await resForecast.value.json();
      if (fc && !fc.error && Array.isArray(fc.hours)) {
        hours = fc.hours;
        predSeries = hours.map(h => [new Date(h.time).getTime(), h.predicted_w]);
        latestPredictedTodayKwh = fc.predicted_today_kwh || 0;
        set('pvf-loc', fc.location || 'Lokasi tidak diketahui');
      } else {
        set('pvf-loc', 'Data cuaca tidak tersedia');
      }
    } else {
      set('pvf-loc', 'Gagal memuat data cuaca');
    }

    if (resActual.status === 'fulfilled' && resActual.value.ok) {
      const raw = await resActual.value.json();
      const pvArr = Array.isArray(raw.pv_in) ? raw.pv_in : [];
      const today = new Date().toDateString();
      actSeries = pvArr
        .filter(r => new Date(r.time).toDateString() === today)
        .map(r => [new Date(r.time).getTime(), r.value]);
    }

    chartPvForecast.updateSeries([
      {name:'Prediksi', data: predSeries},
      {name:'Aktual',   data: actSeries},
    ]);

    // Strip ikon cuaca — ambil 5 titik dari jam 7 pagi - 5 sore (lebih ringkas)
    const strip = el('pvf-weather-strip');
    if (strip && hours.length) {
      const daylightHours = hours.filter(h => {
        const hr = new Date(h.time).getHours();
        return hr >= 7 && hr <= 17;
      });
      const step = Math.max(1, Math.floor(daylightHours.length / 5));
      const picked = daylightHours.filter((_, i) => i % step === 0).slice(0, 5);
      const nowHour = new Date().getHours();

      strip.innerHTML = picked.map(h => {
        const hr = new Date(h.time).getHours();
        const w = weatherIconFor(h.weather_code);
        const isNow = hr === nowHour;
        return `<div class="pvf-hour ${w.cls}"${isNow ? ' style="color:var(--amber);font-weight:700"' : ''}>
                  ${weatherSvg(w.icon, 16)}
                  <span class="pvf-hour-lbl">${hr}:00${isNow ? ' *' : ''}</span>
                </div>`;
      }).join('');

      // Badge status cuaca "sekarang"
      const nowData = hours.find(h => new Date(h.time).getHours() === nowHour);
      const badge = el('pvf-badge');
      if (badge && nowData) {
        const w = weatherIconFor(nowData.weather_code);
        badge.textContent = w.cls === 'rain' ? 'Hujan' : w.cls === 'cloudy' ? 'Berawan' : w.icon === 'cloud-sun' ? 'Sebagian Berawan' : 'Cerah';
      }
    }
  } catch(e) { console.warn('PV forecast error:', e); set('pvf-loc', 'Gagal memuat'); }
}

// ── LIVE DATA: SSE (real-time push), fallback ke polling 15 detik ──
// kalau SSE gagal konek (mis. jaringan/proxy yang tidak mendukung
// text/event-stream). fetchStatesOnce() dipakai kedua mode supaya
// logicnya satu tempat.
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
  if (pollTimer) return; // sudah jalan
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

// ── INIT ─────────────────────────────────────────────────────────
startLive();
fetchChartData();
fetchPvForecast();
setInterval(fetchChartData, 300000);
setInterval(fetchPvForecast, 1800000); // 30 menit, sinkron dgn cache backend
</script>

</body>
</html>