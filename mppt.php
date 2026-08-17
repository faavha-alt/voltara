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

$page_title = 'Voltara EMS - Solar MPPT';
$active   = 'mppt';
$tb_h1    = 'Solar MPPT Controller';
$tb_sub_init = 'Menghubungkan...';
$tb_pills  = [
  ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => 'Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="hero">
 <div class="hero-top">
  <div class="hero-eyebrow">Produksi Tenaga Surya</div>
  <div class="hero-title">MPPT Controller <span id="hero-mode" style="color:var(--amber);font-weight:400;font-size:20px;margin-left:8px">-</span></div>
 </div>
 <div class="hero-stats">
  <div class="hs">
   <div class="hs-lbl">Tegangan PV (Input)</div>
   <div><span class="hs-val" id="h-pv-v">-</span><span class="hs-unit"> V</span></div>
   <div class="hs-sub">Dari Panel Surya</div>
  </div>
  <div class="hs">
   <div class="hs-lbl">Daya PV (Input)</div>
   <div><span class="hs-val" id="h-pv-w">-</span><span class="hs-unit"> W</span></div>
   <div class="hs-sub" id="h-pv-max">Peak Hari Ini: - W</div>
  </div>
  <div class="hs">
   <div class="hs-lbl">Arus Charging (Output)</div>
   <div><span class="hs-val" id="h-chg-a">-</span><span class="hs-unit"> A</span></div>
   <div class="hs-sub" id="h-batt-v">Tegangan Baterai: - V</div>
  </div>
  <div class="hs">
   <div class="hs-lbl">Daya Charging (Output)</div>
   <div><span class="hs-val" id="h-chg-w">-</span><span class="hs-unit"> W</span></div>
   <div class="hs-sub" id="h-eff">Efisiensi: - %</div>
  </div>
 </div>
</div>

<div class="content">

 <div class="grid4">
  <div class="kpi">
   <div class="kpi-head"><div class="kpi-ico"><?= icon('sun', 18) ?></div><span class="tag">Hari Ini</span></div>
   <div class="kpi-lbl">Energi Matahari Dihasilkan</div>
   <div><span class="kpi-val" id="k-solar-day">-</span><span class="kpi-unit">kWh</span></div>
   <div class="kpi-sub" id="k-co2">Menghemat: - kg CO2</div>
  </div>
  <div class="kpi">
   <div class="kpi-head"><div class="kpi-ico"><?= icon('coin', 18) ?></div><span class="tag">Hari Ini</span></div>
   <div class="kpi-lbl">Nilai Ekonomis</div>
   <div><span class="kpi-val" id="k-val-day">-</span><span class="kpi-unit">Rp</span></div>
   <div class="kpi-sub" id="k-val-month">Bulan ini: Rp -</div>
  </div>
  <div class="kpi">
   <div class="kpi-head"><div class="kpi-ico" style="background:rgba(244,63,94,.1)"><?= icon('thermo', 18) ?></div><span class="tag" id="t-temp-tag">Normal</span></div>
   <div class="kpi-lbl">Suhu Heatsink MPPT</div>
   <div><span class="kpi-val" id="k-temp">-</span><span class="kpi-unit">C</span></div>
   <div class="kpi-sub" id="k-temp-esp">ESP32 Temp: - C</div>
  </div>
  <div class="kpi">
   <div class="kpi-head"><div class="kpi-ico" style="background:rgba(16,185,129,.1)"><?= icon('chart', 18) ?></div><span class="tag">Lifetime</span></div>
   <div class="kpi-lbl">Total Produksi Energi</div>
   <div><span class="kpi-val" id="k-total-kwh">-</span><span class="kpi-unit">kWh</span></div>
   <div class="kpi-sub">Sejak sistem dipasang</div>
  </div>
 </div>

 <div class="grid2">

  <div class="card">
   <div class="card-hd">
    <div><div class="card-ttl">Kurva Daya Matahari</div><div class="card-sub">Daya Input PV vs Daya Charging Output</div></div>
    <div class="range-sel" id="range-sel">
     <button class="rb on" data-r="24h">24 Jam</button>
     <button class="rb" data-r="7d">7 Hari</button>
     <button class="rb" data-r="30d">30 Hari</button>
    </div>
   </div>
   <div class="card-bd">
    <div class="chart-hero">
     <div><span class="chart-big" id="c-pv-now">- W</span>
        <span style="font-size:12px;color:var(--muted);margin-left:8px">PV Input Sekarang</span></div>
    </div>
    <div id="chart-pv"></div>
   </div>
  </div>

  <div>

   <div class="card" style="margin-bottom:14px">
    <div class="card-hd">
     <div><div class="card-ttl">Produksi Mingguan</div><div class="card-sub">7 Hari Terakhir</div></div>
    </div>
    <div class="card-bd" style="padding:10px 15px">
     <div id="chart-7d"></div>
    </div>
   </div>

   <div class="card">
    <div class="card-hd"><div class="card-ttl">Status Hardware MPPT</div></div>
    <table class="rtable">
     <tbody>
      <tr><td style="color:var(--muted)">Koneksi Controller</td><td class="val-cell" id="r-online" style="color:var(--mint)">-</td></tr>
      <tr><td style="color:var(--muted)">Mode Operasi</td><td class="val-cell" id="r-mode">-</td></tr>
      <tr><td style="color:var(--muted)">Error Code</td><td class="val-cell" id="r-error">-</td></tr>
      <tr><td style="color:var(--muted)">Beban DC (Load)</td><td class="val-cell" id="r-dc-load">- A</td></tr>
      <tr><td style="color:var(--muted)">Sinyal WiFi ESP</td><td class="val-cell" id="r-wifi">- dBm</td></tr>
      <tr><td style="color:var(--muted)">Data Frame Age</td><td class="val-cell" id="r-age">- s</td></tr>
     </tbody>
    </table>
   </div>

  </div>
 </div>

 <div class="grid3">

  <!-- Bar tegangan baterai di antara ambang charging -->
  <div class="card">
   <div class="card-hd">
    <div><div class="card-ttl">Profil Tegangan Charging</div><div class="card-sub">Posisi baterai relatif terhadap ambang MPPT</div></div>
    <span class="tag" id="vb-mode-tag">-</span>
   </div>
   <div class="card-bd">
    <div class="vbar-now"><b id="vb-now">-</b>V <span id="vb-zone" style="margin-left:6px">-</span></div>
    <div class="vbar-track">
     <div class="vbar-tick" id="vb-rtb"><span>RTB</span></div>
     <div class="vbar-tick" id="vb-float"><span>Float</span></div>
     <div class="vbar-cursor" id="vb-cursor" style="left:0%"></div>
    </div>
    <div class="vbar-scale"><span id="vb-min">- V</span><span style="color:var(--mint)">zona aman</span><span id="vb-max">- V</span></div>
    <div class="vbar-legend">
     <div>Cutoff: <b id="vl-cutoff">-</b></div>
     <div>Return-Bulk: <b id="vl-rtb">-</b></div>
     <div>Float: <b id="vl-float">-</b></div>
     <div>OV Thresh: <b id="vl-ov">-</b></div>
     <div>Max Charge: <b id="vl-maxa">-</b></div>
    </div>
   </div>
  </div>

  <!-- Kesehatan panel surya (pakai model matahari) -->
  <div class="card">
   <div class="card-hd">
    <div class="card-ttl">Kesehatan Panel Surya</div>
    <span class="tag" id="pv-fault-tag">-</span>
   </div>
   <table class="rtable">
    <tbody>
     <tr><td style="color:var(--muted)">Performa Panel (PR)</td><td class="val-cell" id="sh-pr">- %</td></tr>
     <tr><td style="color:var(--muted)">Daya Aktual</td><td class="val-cell" id="sh-act">- W</td></tr>
     <tr><td style="color:var(--muted)">Ekspektasi (model)</td><td class="val-cell" id="sh-exp">- W</td></tr>
     <tr><td style="color:var(--muted)">Efisiensi Konversi MPPT</td><td class="val-cell" id="sh-conv">- %</td></tr>
     <tr><td style="color:var(--muted)">Status Baterai (MPPT)</td><td class="val-cell" id="sh-batst">-</td></tr>
     <tr><td style="color:var(--muted)">Posisi Matahari</td><td class="val-cell" id="sh-sun">-</td></tr>
    </tbody>
   </table>
  </div>

  <!-- Diagnostik firmware ESP32 -->
  <div class="card">
   <div class="card-hd">
    <div class="card-ttl">Diagnostik ESP32</div>
    <span class="tag" id="esp-tag">-</span>
   </div>
   <div class="hg">
    <div class="hg-cell"><div class="hg-lbl"><?= icon('cpu', 13) ?> Heap Free</div><div class="hg-val" id="esp-heap">- <small>KB</small></div></div>
    <div class="hg-cell"><div class="hg-lbl"><?= icon('cpu', 13) ?> Fragmentasi</div><div class="hg-val" id="esp-frag">- <small>%</small></div></div>
    <div class="hg-cell"><div class="hg-lbl"><?= icon('clock', 13) ?> Loop Time</div><div class="hg-val" id="esp-loop">- <small>ms</small></div></div>
    <div class="hg-cell"><div class="hg-lbl"><?= icon('thermo', 13) ?> Suhu ESP</div><div class="hg-val" id="esp-temp">- <small>C</small></div></div>
   </div>
   <div style="padding:13px 16px;border-top:1px solid var(--rim);font-size:12px;color:var(--muted);display:flex;justify-content:space-between">
    <span>Uptime</span><span class="mono" style="color:var(--body)" id="esp-uptime">-</span>
   </div>
  </div>

 </div>

</div>
</main>
</div><!-- /layout -->

<script>
// ── HELPERS ─────────────────────────────────────────────────────
const el = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toFixed(d);
const fmtK = v => (!v || isNaN(v)) ? '-' : v >= 1000 ? (v/1000).toFixed(1)+'k' : Math.round(v).toString();

function sv(d, k) {
 if (!d || !d[k]) return 0;
 const v = parseFloat(d[k].state);
 return isNaN(v) ? 0 : v;
}
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }

// ── CLOCK ────────────────────────────────────────────────────────
function updateClock() {
 const now = new Date();
 set('clock', now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}));
 set('tb-title', 'Solar MPPT - ' + now.toLocaleDateString('id-ID', {day:'numeric',month:'short',year:'numeric'}));
}
setInterval(updateClock, 1000); updateClock();

// ── APEXCHARTS INIT ──────────────────────────────────────────────
const APEX_COMMON = {
 chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
 grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
 tooltip: { theme:'light' },
};

// 1. Chart PV 24 Jam
const chartPV = new ApexCharts(el('chart-pv'), {
 series: [
  {name:'PV Power (In)', type:'area', data:[]},
  {name:'Charging (Out)', type:'line', data:[]},
 ],
 chart: {...APEX_COMMON.chart, type:'line', height:260},
 colors: ['#f59e0b', '#34d399'],
 stroke: {curve:'smooth', width:[2, 2.5]},
 fill:  {type:['gradient','solid'], gradient:{shadeIntensity:1,opacityFrom:.25,opacityTo:.02}},
 dataLabels: {enabled:false},
 xaxis: {
  type:'datetime',
  labels:{datetimeUTC:false, style:{colors:'#98a0b3'}, datetimeFormatter:{hour:'HH:mm'}},
  axisBorder:{show:false}, axisTicks:{show:false},
 },
 yaxis: [
  {title:{text:'Watt',style:{color:'#f59e0b'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0},
 ],
 grid: APEX_COMMON.grid,
 legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
 tooltip:{...APEX_COMMON.tooltip, shared:true, x:{format:'HH:mm dd MMM'}},
});
chartPV.render();

// 2. Chart 7 Days Energy (Bar)
const chart7D = new ApexCharts(el('chart-7d'), {
 series: [{name:'Energi Surya', data:[]}],
 chart: {...APEX_COMMON.chart, type:'bar', height:220},
 colors: ['#f59e0b'],
 plotOptions: { bar: { borderRadius:5, columnWidth:'55%' } },
 dataLabels: {enabled:false},
 xaxis: {
  type:'category',
  labels:{style:{colors:'#5b6178'}, rotate:0},
  axisBorder:{show:false}, axisTicks:{show:false},
 },
 yaxis: { labels:{style:{colors:'#98a0b3'}, formatter: (v) => v.toFixed(1) + ' kWh'} },
 grid: { borderColor:'#e3e7ef', strokeDashArray:4, padding:{top:0,bottom:0,left:10,right:0} },
 tooltip: {...APEX_COMMON.tooltip},
});
chart7D.render();

// ── RENDER STATES ────────────────────────────────────────────────
let peakPV = 0;

function renderStates(d) {
 if (!d || typeof d !== 'object') return;

 try {
  // Power flow: pakai hasil backend (lib/metrics.php) kalau tersedia -
  // fungsi & formula fallback yang SAMA dipakai juga oleh index.php,
  // supaya angka di kedua halaman selalu identik.
  const cpf = d.computed?.power_flow;
  const pvVolt = sv(d,'sensor.smartpowermeter_mppt_pv_voltage');
  const batVolt = cpf ? cpf.battery_voltage : sv(d,'sensor.smartpowermeter_mppt_battery_voltage');
  const chgCurr = cpf ? cpf.charging_current : sv(d,'sensor.smartpowermeter_mppt_charging_current');

  const chgPow = cpf ? cpf.charging_power : (sv(d,'sensor.mppt_charging_power') || (batVolt * chgCurr));
  const pvPow  = cpf ? cpf.pv_power    : (sv(d,'sensor.mppt_pv_power')    || (batVolt * chgCurr / 0.96));
  if (pvPow > peakPV) peakPV = pvPow;

  const mode  = ss(d,'sensor.smartpowermeter_mppt_charging_mode');
  const online = ss(d,'binary_sensor.smartpowermeter_mppt_online') === 'on' || ss(d,'binary_sensor.voltara_system_online') === 'on';
  const mpptErr = ss(d,'sensor.smartpowermeter_mppt_error_code');
  const mpptTmp = sv(d,'sensor.smartpowermeter_mppt_temperature');

  const solDay = sv(d,'sensor.mppt_daily_energy') || sv(d,'sensor.mppt_solar_daily_kwh');
  const solTot = sv(d,'sensor.mppt_total_energy');
  const solVal = sv(d,'sensor.solar_value_today');

  // Update Topbar
  set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));
  const badge = el('sys-badge');
  if (badge) {
   badge.textContent = online ? 'MPPT Terhubung' : 'Terputus';
   badge.className = 'pill ' + (online ? 'ok' : 'err');
  }

  // Hero
  set('hero-mode', `[ Mode: ${mode} ]`);
  set('h-pv-v', fmt(pvVolt,1));
  set('h-pv-w', fmt(pvPow,0));
  set('h-pv-max', `Peak Hari Ini: ${fmt(peakPV,0)} W`);

  set('h-chg-a', fmt(chgCurr,1));
  set('h-chg-w', fmt(chgPow,0));
  set('h-batt-v', `Tegangan Baterai: ${fmt(batVolt,1)} V`);

  let eff = 0;
  if (pvPow > 10) eff = (chgPow / pvPow) * 100;
  set('h-eff', `Efisiensi Konversi: ${fmt(Math.min(eff, 100),1)} %`);

  // KPIs
  set('k-solar-day', fmt(solDay,2));
  set('k-co2', `Menghemat: ${fmt(sv(d,'sensor.co2_avoided_today'),2)} kg CO2`);

  set('k-val-day', fmtK(solVal));
  set('k-val-month', `Bulan ini: Rp ${fmtK(sv(d,'sensor.solar_value_month'))}`);

  set('k-temp', fmt(mpptTmp,1));
  set('k-temp-esp', `ESP32 Temp: ${fmt(sv(d,'sensor.smartpowermeter_esp_temperature'),1)} C`);
  const tempTag = el('t-temp-tag');
  if(tempTag) {
    tempTag.textContent = mpptTmp > 60 ? 'Overheat' : mpptTmp > 45 ? 'Hangat' : 'Normal';
    tempTag.style.color = mpptTmp > 60 ? 'var(--rose)' : mpptTmp > 45 ? 'var(--amber)' : 'var(--dim)';
  }

  set('k-total-kwh', fmt(solTot,0));

  // Live Chart Number
  set('c-pv-now', fmt(pvPow,0) + ' W');

  // Hardware Table
  const elOnline = el('r-online');
  if(elOnline) {
   elOnline.textContent = online ? 'Connected' : 'Disconnected';
   elOnline.style.color = online ? 'var(--mint)' : 'var(--rose)';
  }
  set('r-mode', mode);
  set('r-error', mpptErr === 'No Error' || mpptErr === '0' || mpptErr === '-' ? 'Normal' : mpptErr);
  set('r-dc-load', fmt(sv(d,'sensor.smartpowermeter_mppt_load_current'),2) + ' A');
  set('r-wifi', fmt(sv(d,'sensor.smartpowermeter_wifi_signal'),0) + ' dBm');
  set('r-age', fmt(sv(d,'sensor.smartpowermeter_mppt_frame_age'),1) + ' s');


  // ── PROFIL TEGANGAN CHARGING (bar visual) ──
  const cutoff = sv(d,'sensor.smartpowermeter_mppt_discharge_cutoff') || 44;
  const ovTh  = sv(d,'sensor.smartpowermeter_mppt_overvoltage_threshold') || 52;
  const rtbV  = sv(d,'sensor.smartpowermeter_mppt_return_to_bulk_voltage');
  const floatV = sv(d,'sensor.smartpowermeter_mppt_float_voltage');
  const maxChgA= sv(d,'sensor.smartpowermeter_mppt_max_charge_current');
  const span  = (ovTh - cutoff) || 1;
  const posPct = x => Math.max(0, Math.min(100, (x - cutoff) / span * 100));

  set('vb-now', fmt(batVolt,1));
  el('vb-cursor').style.left = posPct(batVolt) + '%';
  if (rtbV)  el('vb-rtb').style.left  = posPct(rtbV) + '%';
  if (floatV) el('vb-float').style.left = posPct(floatV) + '%';
  set('vb-min', fmt(cutoff,1) + ' V');
  set('vb-max', fmt(ovTh,1) + ' V');
  set('vl-cutoff', fmt(cutoff,1) + ' V');
  set('vl-rtb',  fmt(rtbV,1) + ' V');
  set('vl-float', fmt(floatV,1) + ' V');
  set('vl-ov',   fmt(ovTh,1) + ' V');
  set('vl-maxa',  fmt(maxChgA,0) + ' A');
  // zona tegangan
  let zone = 'Normal', zc = 'var(--mint)';
  if (batVolt < cutoff + 1)   { zone = 'KRITIS (dekat cutoff)'; zc = 'var(--rose)'; }
  else if (batVolt >= floatV)  { zone = 'Float / Penuh';     zc = 'var(--mint2)'; }
  else if (batVolt < rtbV)    { zone = 'Bulk (mengisi)';    zc = 'var(--amber)'; }
  el('vb-zone').textContent = zone; el('vb-zone').style.color = zc;
  const vbTag = el('vb-mode-tag'); if (vbTag) vbTag.textContent = mode;

  // ── KESEHATAN PANEL SURYA ──
  const pr  = sv(d,'sensor.pv_performance_ratio');
  const expW = sv(d,'sensor.expected_pv_power');
  const conv = sv(d,'sensor.mppt_conversion_efficiency');
  const elev = sv(d,'sensor.smartpowermeter_sun_elevation');
  const azim = sv(d,'sensor.smartpowermeter_sun_azimuth');
  const pvFlt = ss(d,'binary_sensor.smartpowermeter_pv_fault_daylight') === 'on';

  const prEl = el('sh-pr');
  if (prEl) {
   prEl.textContent = pr > 0 ? fmt(pr,0) + ' %' : '-';
   prEl.style.color = pr <= 0 ? 'var(--dim)' : pr < 70 ? 'var(--rose)' : pr < 85 ? 'var(--amber)' : 'var(--mint)';
  }
  set('sh-act', fmt(pvPow,0) + ' W');
  set('sh-exp', expW > 0 ? fmt(expW,0) + ' W' : '-');
  set('sh-conv', conv > 0 ? fmt(conv,1) + ' %' : '-');
  set('sh-batst', ss(d,'sensor.smartpowermeter_mppt_battery_status'));
  set('sh-sun', `${fmt(elev,1)} elev - ${fmt(azim,0)} az`);
  const fTag = el('pv-fault-tag');
  if (fTag) {
   fTag.textContent = pvFlt ? 'FAULT' : elev < 5 ? 'Malam' : 'OK';
   fTag.style.color = pvFlt ? 'var(--rose)' : 'var(--mint2)';
  }

  // ── DIAGNOSTIK ESP32 ──
  const heapKB = sv(d,'sensor.smartpowermeter_heap_free') / 1024;
  const frag  = sv(d,'sensor.smartpowermeter_heap_fragmentation');
  const loopMs = sv(d,'sensor.smartpowermeter_loop_time');
  el('esp-heap').innerHTML = fmt(heapKB,0) + ' <small>KB</small>';
  el('esp-frag').innerHTML = fmt(frag,0) + ' <small>%</small>';
  el('esp-loop').innerHTML = fmt(loopMs,0) + ' <small>ms</small>';
  el('esp-temp').innerHTML = fmt(sv(d,'sensor.smartpowermeter_esp_temperature'),1) + ' <small>C</small>';
  set('esp-uptime', ss(d,'sensor.smartpowermeter_uptime_human'));
  const espTag = el('esp-tag');
  if (espTag) {
   const bad = frag > 50 || heapKB < 40 || loopMs > 200;
   espTag.textContent = bad ? 'Perhatian' : 'Sehat';
   espTag.style.color = bad ? 'var(--amber)' : 'var(--mint2)';
  }

 } catch(err) { console.error('renderStates error:', err); }
}

// ── FETCH DATA ───────────────────────────────────────────────────
async function fetchChartData() {
 try {
  const [resPow, resEnergy] = await Promise.allSettled([
   fetch('proxy.php?type=influx_mppt_history&range=' + mpptRange),
   fetch('proxy.php?type=influx_energy_7d')
  ]);

  // Render PV Chart (24h)
  if (resPow.status === 'fulfilled' && resPow.value.ok) {
   const raw = await resPow.value.json();
   if (raw && typeof raw === 'object') {

    // Tarik data yang sudah dipisah dari proxy
    const pvData = Array.isArray(raw.pv_in)  ? raw.pv_in.map(r => [new Date(r.time).getTime(), r.value]) : [];
    const chgData = Array.isArray(raw.chg_out) ? raw.chg_out.map(r => [new Date(r.time).getTime(), r.value]) : [];

    // Update grafik dengan dua garis berbeda
    chartPV.updateSeries([
      {name:'PV Power (In)', data: pvData},
      {name:'Charging (Out)', data: chgData}
    ]);
   }
  }

  // Render 7D Energy Bar
  if (resEnergy.status === 'fulfilled' && resEnergy.value.ok) {
   const raw = await resEnergy.value.json();
   if (raw && typeof raw === 'object' && Array.isArray(raw.solar)) {
    const energyData = raw.solar.map(r => ({
     x: new Date(r.time).toLocaleDateString('id-ID',{day:'numeric',month:'short'}),
     y: +(r.value).toFixed(2)
    }));
    chart7D.updateSeries([{ name:'Energi Surya', data: energyData }]);
   }
  }

 } catch(e) { console.warn('Chart error:', e); }
}

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

// Init

// ── RANGE SELECTOR ──
let mpptRange = '24h';
document.getElementById('range-sel').addEventListener('click', e => {
 const b = e.target.closest('.rb'); if(!b) return;
 mpptRange = b.dataset.r;
 document.querySelectorAll('#range-sel .rb').forEach(x=>x.classList.toggle('on', x===b));
 fetchChartData();
});

startLive();
fetchChartData();
setInterval(fetchChartData, 300000);
</script>
</body>
</html>