<?php
require __DIR__ . '/_session.php';
if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }

$page_title  = 'Inverter — Voltara EMS';
$active      = 'inverter';
$tb_h1       = 'FT-S Inverter';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [
    ['id' => 'inv-mode-badge', 'class' => 'pill', 'text' => 'Mode: -'],
    ['id' => 'sys-badge',      'class' => 'pill ok', 'text' => 'Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<style>
.inv-hero { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:16px; }
.inv-stat { background:var(--surface); border:1px solid var(--rim); border-radius:10px; padding:14px 16px; }
.inv-stat-lbl { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
.inv-stat-val { font-size:24px; font-weight:700; color:var(--text); font-family:var(--mono); }
.inv-stat-unit { font-size:13px; color:var(--muted); margin-left:3px; }
.inv-stat-sub  { font-size:11px; color:var(--muted); margin-top:3px; }
.src-chip { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.src-battery { background:rgba(16,185,129,.15); color:#10b981; }
.src-grid    { background:rgba(56,189,248,.15);  color:#38bdf8; }
.src-unknown { background:rgba(100,116,139,.15); color:#94a3b8; }
.status-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:16px; }
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
.kv-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--rim); }
.kv-row:last-child { border-bottom:none; }
.kv-lbl { font-size:12px; color:var(--muted); }
.kv-val { font-size:13px; font-weight:600; color:var(--text); font-family:var(--mono); }
.kv-val.ok  { color:#10b981; }
.kv-val.warn{ color:#f59e0b; }
.kv-val.err { color:#ef4444; }
.range-tabs { display:flex; gap:6px; }
.range-tab  { padding:4px 10px; border-radius:4px; font-size:11px; cursor:pointer; border:1px solid var(--rim); background:transparent; color:var(--muted); }
.range-tab.active { background:var(--sky); color:#fff; border-color:var(--sky); }
@media(max-width:768px){ .inv-hero{grid-template-columns:repeat(2,1fr);} .status-grid,.settings-grid{grid-template-columns:1fr;} }
</style>

<div class="content" style="padding-top:0">

  <!-- HERO STATS -->
  <div class="inv-hero">
    <div class="inv-stat">
      <div class="inv-stat-lbl">Sumber Daya</div>
      <div id="h-src">-</div>
      <div class="inv-stat-sub" id="h-mode">-</div>
    </div>
    <div class="inv-stat">
      <div class="inv-stat-lbl">AC Input</div>
      <div><span class="inv-stat-val" id="h-ac-in">-</span><span class="inv-stat-unit">V</span></div>
      <div class="inv-stat-sub" id="h-ac-in-hz">- Hz</div>
    </div>
    <div class="inv-stat">
      <div class="inv-stat-lbl">AC Output</div>
      <div><span class="inv-stat-val" id="h-ac-out">-</span><span class="inv-stat-unit">V</span></div>
      <div class="inv-stat-sub" id="h-ac-out-hz">- Hz</div>
    </div>
    <div class="inv-stat">
      <div class="inv-stat-lbl">Beban</div>
      <div><span class="inv-stat-val" id="h-load">-</span><span class="inv-stat-unit">%</span></div>
      <div class="inv-stat-sub" id="h-load-w">- W</div>
    </div>
    <div class="inv-stat">
      <div class="inv-stat-lbl">Baterai (Inverter)</div>
      <div><span class="inv-stat-val" id="h-bat">-</span><span class="inv-stat-unit">V</span></div>
      <div class="inv-stat-sub" id="h-soc">SOC: - %</div>
    </div>
    <div class="inv-stat">
      <div class="inv-stat-lbl">Konsumsi Hari Ini</div>
      <div><span class="inv-stat-val" id="h-kwh-today">-</span><span class="inv-stat-unit">kWh</span></div>
      <div class="inv-stat-sub" id="h-kwh-avg">Rata-rata 7 hari: - kWh</div>
    </div>
  </div>

  <!-- STATUS CARDS -->
  <div class="status-grid">

    <!-- AC Detail -->
    <div class="card">
      <div class="card-hd"><div><div class="card-ttl">AC Input / Output</div><div class="card-sub">Dari grid PLN</div></div></div>
      <div class="card-bd">
        <div class="kv-row"><span class="kv-lbl">Input Voltage</span><span class="kv-val" id="s-ac-in-v">- V</span></div>
        <div class="kv-row"><span class="kv-lbl">Input Frequency</span><span class="kv-val" id="s-ac-in-hz">- Hz</span></div>
        <div class="kv-row"><span class="kv-lbl">Output Voltage</span><span class="kv-val" id="s-ac-out-v">- V</span></div>
        <div class="kv-row"><span class="kv-lbl">Output Frequency</span><span class="kv-val" id="s-ac-out-hz">- Hz</span></div>
        <div class="kv-row"><span class="kv-lbl">Load %</span><span class="kv-val" id="s-load-pct">- %</span></div>
        <div class="kv-row"><span class="kv-lbl">Est. Daya Beban</span><span class="kv-val" id="s-load-w">- W</span></div>
      </div>
    </div>

    <!-- Battery & Mode -->
    <div class="card">
      <div class="card-hd"><div><div class="card-ttl">Baterai & Mode</div><div class="card-sub">Dari sensor inverter</div></div></div>
      <div class="card-bd">
        <div class="kv-row"><span class="kv-lbl">Battery Voltage</span><span class="kv-val" id="s-bat-v">- V</span></div>
        <div class="kv-row"><span class="kv-lbl">Battery Capacity (SOC)</span><span class="kv-val" id="s-soc">- %</span></div>
        <div class="kv-row"><span class="kv-lbl">AC Charging</span><span class="kv-val" id="s-chg-pct">- %</span></div>
        <div class="kv-row"><span class="kv-lbl">Power Source</span><span class="kv-val" id="s-src">-</span></div>
        <div class="kv-row"><span class="kv-lbl">Working Mode</span><span class="kv-val" id="s-wmode">-</span></div>
        <div class="kv-row"><span class="kv-lbl">Operating State</span><span class="kv-val" id="s-state">-</span></div>
      </div>
    </div>

    <!-- Settings -->
    <div class="card">
      <div class="card-hd"><div><div class="card-ttl">Setting Tegangan</div><div class="card-sub">Konfigurasi baterai di inverter</div></div></div>
      <div class="card-bd">
        <div class="kv-row"><span class="kv-lbl">Charging Voltage</span><span class="kv-val" id="s-chg-v">- V</span></div>
        <div class="kv-row"><span class="kv-lbl">Cutoff Voltage</span><span class="kv-val err" id="s-cutoff">- V</span></div>
        <div class="kv-row"><span class="kv-lbl">Restore Voltage</span><span class="kv-val ok" id="s-restore">- V</span></div>
        <div class="kv-row" style="border-top:2px solid var(--rim);margin-top:6px;padding-top:10px">
          <span class="kv-lbl">Monitor Register</span>
          <a href="http://<?= $_SERVER['HTTP_HOST'] ?>:5000/inverter" target="_blank"
             style="font-size:11px;color:var(--sky);text-decoration:none">Buka Monitor ↗</a>
        </div>
      </div>
    </div>

  </div>


  <!-- CHART KONSUMSI HARIAN -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-hd">
      <div>
        <div class="card-ttl">Konsumsi Harian</div>
        <div class="card-sub">PZEM (aktual) vs Inverter (estimasi) — kWh per hari</div>
      </div>
      <div class="range-tabs">
        <button class="range-tab active" onclick="loadConsumption(7,this)">7 Hari</button>
        <button class="range-tab" onclick="loadConsumption(14,this)">14 Hari</button>
        <button class="range-tab" onclick="loadConsumption(30,this)">30 Hari</button>
      </div>
    </div>
    <div class="card-bd" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px">
      <div style="text-align:center">
        <div style="font-size:11px;color:var(--muted)">Total</div>
        <div style="font-size:20px;font-weight:700;font-family:var(--mono)" id="cons-total">-</div>
        <div style="font-size:10px;color:var(--muted)" id="cons-period">-</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:11px;color:var(--muted)">Rata-rata/hari</div>
        <div style="font-size:20px;font-weight:700;font-family:var(--mono);color:var(--sky)" id="cons-avg">-</div>
        <div style="font-size:10px;color:var(--muted)">kWh</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:11px;color:var(--muted)">Hari Ini</div>
        <div style="font-size:20px;font-weight:700;font-family:var(--mono);color:#10b981" id="cons-today2">-</div>
        <div style="font-size:10px;color:var(--muted)">kWh</div>
      </div>
    </div>
    <div id="chart-consumption" style="min-height:200px"></div>
  </div>

  <!-- CHART HISTORIS -->
  <div class="card" style="margin-bottom:16px">
    <div class="card-hd">
      <div><div class="card-ttl">Historis Inverter</div><div class="card-sub">Tegangan, Beban, SOC</div></div>
      <div class="range-tabs">
        <button class="range-tab active" onclick="loadChart('24h',this)">24 Jam</button>
        <button class="range-tab" onclick="loadChart('7d',this)">7 Hari</button>
        <button class="range-tab" onclick="loadChart('30d',this)">30 Hari</button>
      </div>
    </div>
    <div class="card-bd">
      <div id="chart-inverter" style="min-height:220px"></div>
    </div>
  </div>

  <!-- CHART AC VOLTAGE -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Tegangan AC Input vs Output</div><div class="card-sub">Kualitas tegangan grid</div></div>
    </div>
    <div class="card-bd">
      <div id="chart-voltage" style="min-height:180px"></div>
    </div>
  </div>

</div>

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toFixed(d);

function sv(d, k) {
  if (!d || !d[k]) return 0;
  const v = parseFloat(d[k].state);
  return isNaN(v) ? 0 : v;
}
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }

// Charts
const APEX = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid:  { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light', x:{format:'HH:mm dd MMM'} },
  xaxis: { type:'datetime', labels:{datetimeUTC:false,style:{colors:'#98a0b3'},datetimeFormatter:{hour:'HH:mm',day:'dd MMM'}}, axisBorder:{show:false}, axisTicks:{show:false} },
};

const chartInv = new ApexCharts(el('chart-inverter'), {
  series: [
    {name:'PZEM (Aktual)',       type:'area', data:[]},
    {name:'Inverter (Estimasi)', type:'line', data:[]},
    {name:'SOC %',               type:'line', data:[]},
  ],
  chart: {...APEX.chart, type:'line', height:220},
  colors: ['#0284c7','#f59e0b','#10b981'],
  stroke: {curve:'smooth', width:[2,2,1.5]},
  fill: {type:['gradient','solid','solid'], gradient:{shadeIntensity:1,opacityFrom:.15,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: APEX.xaxis,
  yaxis: [
    {title:{text:'Watt',style:{color:'#0284c7'}}, min:0, labels:{style:{colors:'#98a0b3'},formatter:v=>Math.round(v)+' W'}},
    {show:false},
    {opposite:true, title:{text:'SOC',style:{color:'#10b981'}}, min:0, max:100, labels:{style:{colors:'#10b981'},formatter:v=>v.toFixed(0)+'%'}},
  ],
  grid: APEX.grid, legend:{position:'top',horizontalAlign:'right',labels:{colors:'#5b6178'},itemMargin:{horizontal:10}},
  tooltip:{...APEX.tooltip, shared:true},
});
chartInv.render();

const chartVolt = new ApexCharts(el('chart-voltage'), {
  series: [
    {name:'AC Input V',  data:[]},
    {name:'AC Output V', data:[]},
  ],
  chart: {...APEX.chart, type:'line', height:180},
  colors: ['#60a5fa','#10b981'],
  stroke: {curve:'smooth', width:[2,2]},
  dataLabels: {enabled:false},
  xaxis: APEX.xaxis,
  yaxis: {labels:{style:{colors:'#98a0b3'},formatter:v=>v.toFixed(0)+' V'}, min:200, max:240},
  grid: APEX.grid, legend:{position:'top',horizontalAlign:'right',labels:{colors:'#5b6178'}},
  tooltip:{...APEX.tooltip, shared:true},
});
chartVolt.render();

async function loadChart(range, btn) {
  document.querySelectorAll('.range-tab').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  try {
    const res = await fetch('proxy.php?type=influx_inverter_history&range=' + range);
    const raw = await res.json();
    const mk = (key) => (raw[key] || []).map(r => [new Date(r.time).getTime(), r.value]);
    chartInv.updateSeries([
      {name:'PZEM (Aktual)',       data: mk('pzem_power')},
      {name:'Inverter (Estimasi)', data: mk('inv_load_w')},
      {name:'SOC %',               data: mk('soc_pct')},
    ]);
    chartVolt.updateSeries([
      {name:'AC Input V',  data: mk('ac_in_v')},
      {name:'AC Output V', data: mk('ac_out_v')},
    ]);
  } catch(e) { console.warn('Chart error:', e); }
}

// Chart konsumsi harian
const chartCons = new ApexCharts(el('chart-consumption'), {
  series: [{name:'PZEM (Aktual)', data:[]},{name:'Inverter (Estimasi)', data:[]}],
  chart: {background:'transparent', toolbar:{show:false}, animations:{enabled:false},
          fontFamily:'Inter,sans-serif', type:'bar', height:200},
  colors: ['#0284c7','#f59e0b'],
  plotOptions: {bar:{borderRadius:4, columnWidth:'55%'}},
  dataLabels: {enabled:true, formatter:v=>v.toFixed(1), style:{fontSize:'10px', colors:['#5b6178']}},
  xaxis: {type:'category', labels:{style:{colors:'#98a0b3', fontSize:'10px'}, rotate:-30},
          axisBorder:{show:false}, axisTicks:{show:false}},
  yaxis: {title:{text:'kWh', style:{color:'#98a0b3'}},
          labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}},
  grid: {borderColor:'#e3e7ef', strokeDashArray:4},
  tooltip: {theme:'light', y:{formatter:v=>v.toFixed(2)+' kWh'}},
});
chartCons.render();

async function loadConsumption(days, btn) {
  document.querySelectorAll('.range-tab').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
  try {
    const res  = await fetch('proxy.php?type=influx_daily_consumption&days=' + days);
    const data = await res.json();
    if (!data || !data.days) return;

    const rows = data.days;
    const total = rows.reduce((s,r) => s + r.kwh, 0);
    const avg   = rows.length ? total / rows.length : 0;
    const todayKwh = data.today_kwh || 0;

    set('cons-total',   total.toFixed(1) + ' kWh');
    set('cons-period',  rows.length + ' hari');
    set('cons-avg',     avg.toFixed(2));
    set('cons-today2',  todayKwh.toFixed(2));
    set('h-kwh-today',  todayKwh.toFixed(2));
    set('h-kwh-avg',    'Rata-rata ' + days + ' hari: ' + avg.toFixed(2) + ' kWh');

        const labels = rows.map(r =>
      new Date(r.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'})
    );
    chartCons.updateSeries([
      {name:'PZEM (Aktual)',       data: rows.map((r,i) => ({x:labels[i], y:r.kwh}))},
      {name:'Inverter (Estimasi)', data: rows.map((r,i) => ({x:labels[i], y:r.inv_kwh||0}))},
    ]);
  } catch(e) { console.warn('Consumption error:', e); }
}
setTimeout(() => loadConsumption(7, null), 2000);
setInterval(() => loadConsumption(7, null), 600000);

loadChart('24h');

// Live data
function renderInverter(d) {
  if (!d) return;
  try {
    const acInV   = sv(d,'sensor.inverter_ac_in_v');
    const acInHz  = sv(d,'sensor.inverter_ac_in_hz');
    const acOutV  = sv(d,'sensor.inverter_ac_out_v');
    const acOutHz = sv(d,'sensor.inverter_ac_out_hz');
    const loadPct = sv(d,'sensor.inverter_load_pct');
    const batV    = sv(d,'sensor.inverter_bat_v');
    const soc     = sv(d,'sensor.inverter_soc');
    const chgPct  = sv(d,'sensor.inverter_chg_current_pct');
    const chgV    = sv(d,'sensor.inverter_chg_voltage_v');
    const cutoff  = sv(d,'sensor.inverter_cutoff_v');
    const restore = sv(d,'sensor.inverter_restore_v');
    const src     = ss(d,'sensor.inverter_power_source');
    const mode    = ss(d,'sensor.inverter_working_mode');
    const state   = ss(d,'sensor.inverter_operating_state');
    const online  = sv(d,'sensor.inverter_online');

    // Est load power (ac_out_v × load% × rated_a)
    const loadW = acOutV > 0 && loadPct > 0 ? Math.round(acOutV * loadPct / 100 * 13.6) : 0;

    // Hero
    const srcCls = src === 'Battery' ? 'src-battery' : src === 'Grid' ? 'src-grid' : 'src-unknown';
    el('h-src').innerHTML = `<span class="src-chip ${srcCls}">${src || '-'}</span>`;
    set('h-mode', mode || '-');
    set('h-ac-in', fmt(acInV, 1));
    set('h-ac-in-hz', fmt(acInHz, 1) + ' Hz');
    set('h-ac-out', fmt(acOutV, 1));
    set('h-ac-out-hz', fmt(acOutHz, 1) + ' Hz');
    set('h-load', fmt(loadPct, 0));
    set('h-load-w', '~' + loadW + ' W');
    set('h-bat', fmt(batV, 1));
    set('h-soc', 'SOC: ' + fmt(soc, 0) + ' %');

    // Status
    set('s-ac-in-v',  fmt(acInV,1) + ' V');
    set('s-ac-in-hz', fmt(acInHz,1) + ' Hz');
    set('s-ac-out-v', fmt(acOutV,1) + ' V');
    set('s-ac-out-hz',fmt(acOutHz,1) + ' Hz');
    set('s-load-pct', fmt(loadPct,0) + ' %');
    set('s-load-w',   '~' + loadW + ' W');
    set('s-bat-v',    fmt(batV,1) + ' V');
    set('s-soc',      fmt(soc,0) + ' %');
    set('s-chg-pct',  fmt(chgPct,0) + ' %');
    set('s-src',      src || '-');
    set('s-wmode',    mode || '-');
    set('s-state',    state || '-');

    // Settings
    set('s-chg-v',    fmt(chgV,1) + ' V');
    set('s-cutoff',   fmt(cutoff,1) + ' V');
    set('s-restore',  fmt(restore,1) + ' V');

    // Topbar badge
    const badge = document.getElementById('inv-mode-badge');
    if (badge) { badge.textContent = 'Mode: ' + (mode || '-');
      badge.className = 'pill ' + (src === 'Battery' ? 'ok' : 'warn'); }
    const sysBadge = document.getElementById('sys-badge');
    if (sysBadge) { sysBadge.textContent = online ? 'Online' : 'Offline';
      sysBadge.className = 'pill ' + (online ? 'ok' : 'err'); }

    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));

  } catch(e) { console.error(e); }
}

// SSE / polling
async function fetchOnce() {
  try {
    const res = await fetch('proxy.php?type=ha_states');
    if (res.status === 401) { location.href = 'login.php'; return; }
    const data = await res.json();
    if (data && !data.error) renderInverter(data);
  } catch(e) {}
}

let es = null;
function connect() {
  if (!window.EventSource) { fetchOnce(); setInterval(fetchOnce, 10000); return; }
  es = new EventSource('proxy.php?type=sse');
  es.onmessage = ev => { try { renderInverter(JSON.parse(ev.data)); } catch(e){} };
  es.onerror   = () => { setTimeout(()=>{ if(es){es.close();es=null;} connect(); }, 5000); };
}
connect();
setInterval(loadChart.bind(null,'24h',null), 300000);
</script>

</body>
</html>