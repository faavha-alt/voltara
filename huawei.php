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

$page_title  = 'Baterai Huawei - Voltara EMS';
$active      = 'battery';
$tb_h1       = 'Monitor Baterai - Huawei ESM-48100U2';
$tb_sub_init = 'Menghubungkan ke BMS...';
$tb_pills    = [
    ['id' => 'batt-status-pill', 'class' => 'pill ok', 'text' => ' Standby'],
];

include __DIR__ . '/partials/head.php';
$tb_h1 = icon('battery', 20) . ' ' . $tb_h1;
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div style="display:flex;align-items:center;gap:14px;padding:14px 24px 0">
  <a href="battery.php" style="color:var(--dim);text-decoration:none;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:4px"><?= icon('chevron-left', 14) ?> Ringkasan</a>
  <div style="display:inline-flex;background:var(--panel);border:1px solid var(--rim);border-radius:10px;overflow:hidden">
    <a href="huawei.php" style="padding:7px 18px;font-size:12px;font-weight:600;text-decoration:none;color:#10b981;background:rgba(16,185,129,.12)">Huawei ESM</a><a href="jikong.php" style="padding:7px 18px;font-size:12px;font-weight:600;text-decoration:none;color:var(--dim)">Jikong JK</a>
  </div>
</div>

<!-- HERO SOC RING -->
<div class="hero-batt">
  <div class="soc-ring-wrap">
    <!-- Ring SVG -->
    <div class="soc-ring-svg">
      <svg viewBox="0 0 160 160" width="160" height="160">
        <circle class="ring-bg" cx="80" cy="80" r="66"/>
        <circle class="ring-fill" id="ring-fill" cx="80" cy="80" r="66"
          stroke-dasharray="414.69" stroke-dashoffset="414.69"
          stroke="var(--mint)"/>
      </svg>
      <div class="ring-center">
        <div class="ring-pct" id="ring-pct">-</div>
        <div class="ring-lbl">SOC</div>
      </div>
    </div>

    <!-- Stats samping ring -->
    <div class="soc-stats">
      <div class="soc-stat">
        <div class="soc-stat-lbl">Daya Sekarang</div>
        <div><span class="soc-stat-val" id="batt-power-val">-</span>
             <span class="soc-stat-unit" id="batt-power-unit"> W</span></div>
        <div class="soc-stat-sub" id="batt-mode-sub">-</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Energi Tersisa</div>
        <div><span class="soc-stat-val" id="batt-rem-val">-</span>
             <span class="soc-stat-unit"> kWh</span></div>
        <div class="soc-stat-sub" id="batt-runtime-sub" style="color:var(--dim)">-</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Mode Operasi</div>
        <div><span class="soc-stat-val" id="batt-mode-val" style="font-size:15px">-</span></div>
        <div class="soc-stat-sub" id="batt-health-sub" style="color:var(--mint)">-</div>
      </div>
    </div>

    <!-- Stats kanan -->
    <div class="soc-stats" style="margin-left:16px">
      <div class="soc-stat">
        <div class="soc-stat-lbl">Charge Hari Ini</div>
        <div><span class="soc-stat-val" id="batt-chg-today">-</span>
             <span class="soc-stat-unit"> kWh</span></div>
        <div class="soc-stat-sub" style="color:var(--mint)">Masuk</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Discharge Hari Ini</div>
        <div><span class="soc-stat-val" id="batt-dchg-today">-</span>
             <span class="soc-stat-unit"> kWh</span></div>
        <div class="soc-stat-sub" style="color:var(--rose)">Keluar</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Round-Trip Eff.</div>
        <div><span class="soc-stat-val" id="batt-rte-val">-</span>
             <span class="soc-stat-unit"> %</span></div>
        <div class="soc-stat-sub" id="batt-cycle-sub" style="color:var(--dim)">- siklus</div>
      </div>
    </div>
  </div>
</div>

<!-- CONTENT -->
<div class="content">

  <!-- STAT CARDS ROW -->
  <div class="grid4">
    <div class="sc mint">
      <div class="sc-ico"><?= icon('thermo', 20) ?></div>
      <div class="sc-lbl">Suhu Sel Maks</div>
      <div><span class="sc-val" id="sc-tmax">-</span><span class="sc-unit">C</span></div>
      <div class="sc-sub" id="sc-tmax-sub">Min: - | Avg: -</div>
    </div>
    <div class="sc amber">
      <div class="sc-ico"><?= icon('bolt', 20) ?></div>
      <div class="sc-lbl">Tegangan Sel Maks</div>
      <div><span class="sc-val" id="sc-vmax">-</span><span class="sc-unit">V</span></div>
      <div class="sc-sub" id="sc-vmax-sub">Min: - | Avg: -</div>
    </div>
    <div class="sc rose">
      <div class="sc-ico"><?= icon('activity', 20) ?></div>
      <div class="sc-lbl">Delta Sel (Imbalance)</div>
      <div><span class="sc-val" id="sc-delta">-</span><span class="sc-unit">mV</span></div>
      <div class="sc-sub" id="sc-delta-sub">-</div>
    </div>
    <div class="sc sky">
      <div class="sc-ico"><?= icon('heart', 20) ?></div>
      <div class="sc-lbl">Health Score (SOH)</div>
      <div><span class="sc-val" id="sc-soh">-</span><span class="sc-unit">%</span></div>
      <div class="sc-sub" id="sc-soh-sub">-</div>
    </div>
  </div>

  <!-- HEATMAP 15 SEL + THERMAL/DIAGNOSTIK -->
  <div class="grid21">
    <div class="card" style="margin-bottom:0">
      <div class="card-hd" style="flex-wrap:wrap;gap:8px">
        <div><div class="card-ttl">Heatmap Tegangan Sel (15S)</div>
          <div style="font-size:11px;color:var(--muted);margin-top:2px">Kesehatan & keseimbangan tiap sel</div></div>
        <div class="heat-legend">
          <span><i style="background:var(--mint2)"></i>Normal</span>
          <span><i style="background:var(--amber2)"></i>Tertinggi</span>
          <span><i style="background:var(--warn)"></i>Terendah</span>
          <span><i style="background:var(--rose)"></i>Kritis</span>
        </div>
      </div>
      <div class="card-bd">
      <div class="heat-stats">
        <span class="heat-stat">Max<b id="hs-max">-</b></span>
        <span class="heat-stat">Avg<b id="hs-avg">-</b></span>
        <span class="heat-stat">Min<b id="hs-min">-</b></span>
        <span class="heat-stat">Delta<b id="hs-delta">-</b></span>
        <span class="heat-stat">Std<b id="hs-std">-</b></span>
        <span class="heat-stat">Balance<b id="hs-bal">-</b></span>
      </div>
      <div class="heat-grid" id="heat-grid"></div>
      </div>
    </div>
    <div class="card" style="margin-bottom:0">
      <div class="card-hd"><div class="card-ttl">Thermal & Diagnostik BMS</div></div>
      <div class="card-bd">
        <div class="diag-row"><span class="dl">Status Thermal</span><span class="dv" id="dg-thermal">-</span></div>
        <div class="diag-row"><span class="dl">Suhu Sel Min/Avg/Max</span><span class="dv" id="dg-celltemp">-</span></div>
        <div class="diag-row"><span class="dl">Suhu MOSFET</span><span class="dv" id="dg-mos">-</span></div>
        <div class="diag-row"><span class="dl">Probe 1 / Probe 2</span><span class="dv" id="dg-probe">-</span></div>
        <div class="diag-row"><span class="dl">Ambient</span><span class="dv" id="dg-amb">-</span></div>
        <div class="diag-row"><span class="dl">Imbalance</span><span class="dv" id="dg-imb">-</span></div>
        <div class="diag-row"><span class="dl">Total Siklus</span><span class="dv" id="dg-cyc">-</span></div>
        <div class="diag-row"><span class="dl">Modbus Read</span><span class="dv" id="dg-read">-</span></div>
      </div>
    </div>
  </div>

  <!-- CHART UTAMA + CELL HEATMAP -->
  <div class="grid21">

    <!-- Chart SOC + Power + Suhu 24jam -->
    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">SOC - Daya - Suhu Sel</div>
             <div class="card-sub">Tiga dimensi kesehatan baterai</div></div>
        <div class="range-sel" id="range-sel">
          <button class="rb on" data-r="24h">24 Jam</button>
          <button class="rb" data-r="7d">7 Hari</button>
          <button class="rb" data-r="30d">30 Hari</button>
        </div>
      </div>
      <div class="card-bd">
        <div id="chart-batt-main" style="min-height:260px"></div>
      </div>
    </div>

    <!-- Cell Heatmap + Alarm -->
    <div style="display:flex;flex-direction:column;gap:14px">

      <!-- Cell Parameter Heatmap -->
      <!-- Status Alarm BMS -->
      <div class="card" style="margin-bottom:0">
        <div class="card-hd"><div class="card-ttl">Status Alarm BMS</div></div>
        <div class="card-bd" style="padding:12px 16px">
          <table class="vtable">
            <tbody>
              <tr><td>Has Alarm</td>     <td class="mono-val" id="al-hasalarm">-</td></tr>
              <tr><td>Health Label</td>  <td class="mono-val" id="al-label">-</td></tr>
              <tr><td>Cycle Count</td>   <td class="mono-val" id="al-cycle">-</td></tr>
              <tr><td>Alarm Active</td>  <td class="mono-val" id="al-active">-</td></tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

  <!-- HEALTH BARS -->
  <div class="grid2">

    <div class="card" style="margin-bottom:0">
      <div class="card-hd"><div class="card-ttl">Indikator Kesehatan</div></div>
      <div class="card-bd">
        <div class="health-bar-row">
          <div class="hb-lbl">SOC</div>
          <div class="hb-track"><div class="hb-fill" id="hb-soc" style="width:0%;background:linear-gradient(90deg,var(--mint),var(--mint2))"></div></div>
          <div class="hb-val" id="hbv-soc">-</div>
        </div>
        <div class="health-bar-row">
          <div class="hb-lbl">SOH (Health Score)</div>
          <div class="hb-track"><div class="hb-fill" id="hb-soh" style="width:0%;background:linear-gradient(90deg,var(--sky),var(--indigo))"></div></div>
          <div class="hb-val" id="hbv-soh">-</div>
        </div>
        <div class="health-bar-row">
          <div class="hb-lbl">Suhu (maks 45C)</div>
          <div class="hb-track"><div class="hb-fill" id="hb-temp" style="width:0%;background:linear-gradient(90deg,var(--mint),var(--warn))"></div></div>
          <div class="hb-val" id="hbv-temp">-</div>
        </div>
        <div class="health-bar-row">
          <div class="hb-lbl">Delta Sel (maks 50mV)</div>
          <div class="hb-track"><div class="hb-fill" id="hb-delta" style="width:0%;background:linear-gradient(90deg,var(--mint),var(--rose))"></div></div>
          <div class="hb-val" id="hbv-delta">-</div>
        </div>
        <div class="health-bar-row">
          <div class="hb-lbl">Round-Trip Eff.</div>
          <div class="hb-track"><div class="hb-fill" id="hb-rte" style="width:0%;background:linear-gradient(90deg,var(--amber),var(--amber2))"></div></div>
          <div class="hb-val" id="hbv-rte">-</div>
        </div>
      </div>
    </div>

    <!-- Chart energi 7 hari -->
    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Charge vs Discharge (7 Hari)</div>
             <div class="card-sub">kWh per hari</div></div>
        <span class="tag">Mingguan</span>
      </div>
      <div class="card-bd">
        <div id="chart-batt-7d" style="min-height:200px"></div>
      </div>
    </div>
  </div>

  <!-- TABEL DETAIL LENGKAP -->
  <!-- KELISTRIKAN & KAPASITAS PACK -->
  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Kelistrikan & Kapasitas Pack</div>
        <div class="card-sub">Huawei ESM-48100U2 - 48V / 100Ah LiFePO4</div></div>
      <span class="tag">Huawei BMS</span>
    </div>
    <div class="card-bd">
      <div class="cell-grid cell-grid-6">
      <div class="cell-box"><div class="cell-lbl">Tegangan Pack</div><div class="cell-val" id="pk-vbat">-</div><div class="cell-unit">V</div></div>
      <div class="cell-box"><div class="cell-lbl">Busbar</div><div class="cell-val" id="pk-vbus">-</div><div class="cell-unit">V</div></div>
      <div class="cell-box"><div class="cell-lbl">Arus Pack</div><div class="cell-val" id="pk-cur">-</div><div class="cell-unit">A</div></div>
      <div class="cell-box"><div class="cell-lbl">SOH</div><div class="cell-val" id="pk-soh">-</div><div class="cell-unit">%</div></div>
      <div class="cell-box"><div class="cell-lbl">Kap. Design</div><div class="cell-val" id="pk-kdes">-</div><div class="cell-unit">kWh</div></div>
      <div class="cell-box"><div class="cell-lbl">Kap. Aktual</div><div class="cell-val" id="pk-kact">-</div><div class="cell-unit">kWh</div></div>
      </div>
    </div>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v ?? '-'; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(Number(v))) ? '-' : Number(v).toFixed(d);
const pct = (v, max) => Math.min(100, Math.max(0, (v / max) * 100));
function sv(d, k) { if (!d || !d[k]) return 0; const v = parseFloat(d[k].state); return isNaN(v) ? 0 : v; }
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }

function statusClass(v, warn, danger, invert=false) {
  if (invert) { if (v >= danger) return 'status-ok'; if (v >= warn) return 'status-warn'; return 'status-bad'; }
  if (v >= danger) return 'status-bad';
  if (v >= warn) return 'status-warn';
  return 'status-ok';
}

// Clock
setInterval(() => {
  set('clock', new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}));
}, 1000);

// ── SOC RING ─────────────────────────────────────────────────────
function updateRing(soc) {
  const circumference = 2 * Math.PI * 66; // 414.69
  const offset = circumference - (soc / 100) * circumference;
  const ring = el('ring-fill');
  if (!ring) return;
  ring.style.strokeDashoffset = offset;
  // Warna ring berdasarkan SOC
  ring.style.stroke = soc >= 60 ? 'var(--mint)' : soc >= 30 ? 'var(--amber)' : 'var(--rose)';
  set('ring-pct', Math.round(soc) + '%');
}

// ── APEXCHARTS ───────────────────────────────────────────────────
const APEX_DARK = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

// Chart utama: SOC + Power + Suhu
const chartMain = new ApexCharts(el('chart-batt-main'), {
  series: [
    {name:'SOC (%)',      type:'area', data:[]},
    {name:'Daya (W)',     type:'line', data:[]},
    {name:'Suhu Maks (C)', type:'line', data:[]},
  ],
  chart: {...APEX_DARK.chart, type:'line', height:260},
  colors: ['#10b981','#6366f1','#f59e0b'],
  stroke: {curve:'smooth', width:[2.5, 2, 2]},
  fill: {type:['gradient','solid','solid'], gradient:{shadeIntensity:1,opacityFrom:.2,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: {
    type:'datetime',
    labels:{datetimeUTC:false, style:{colors:'#98a0b3'}, datetimeFormatter:{hour:'HH:mm'}},
    axisBorder:{show:false}, axisTicks:{show:false},
  },
  yaxis: [
    {title:{text:'SOC (%)', style:{color:'#10b981'}}, min:0, max:100, labels:{style:{colors:'#10b981'}, formatter:v=>Math.round(v)}},
    {opposite:true, title:{text:'Daya (W)', style:{color:'#6366f1'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}},
    {show:false, min:0, max:60},
  ],
  grid: APEX_DARK.grid,
  legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, itemMargin:{horizontal:10}},
  tooltip:{...APEX_DARK.tooltip, shared:true, x:{format:'HH:mm dd MMM'}},
  annotations:{
    yaxis:[
      {y:20, borderColor:'#f43f5e', borderWidth:1, strokeDashArray:3, label:{text:'SOC Rendah',style:{color:'#f43f5e',background:'transparent',fontSize:'10px'}}},
      {y:80, borderColor:'#10b981', borderWidth:1, strokeDashArray:3, label:{text:'SOC Penuh',style:{color:'#10b981',background:'transparent',fontSize:'10px'}}},
    ]
  }
});
chartMain.render();

// Chart 7 hari charge/discharge
const chart7d = new ApexCharts(el('chart-batt-7d'), {
  series: [
    {name:'Charge (kWh)',    data:[]},
    {name:'Discharge (kWh)', data:[]},
  ],
  chart: {...APEX_DARK.chart, type:'bar', height:210},
  colors: ['#10b981','#6366f1'],
  plotOptions: {bar:{borderRadius:5, columnWidth:'55%'}},
  dataLabels: {enabled:false},
  xaxis: {
    type:'category',
    labels:{style:{colors:'#5b6178'}, rotate:0, hideOverlappingLabels:false},
    axisBorder:{show:false}, axisTicks:{show:false},
  },
  yaxis: {labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}},
  grid: APEX_DARK.grid,
  legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip:{...APEX_DARK.tooltip},
});
chart7d.render();

// ── RENDER ───────────────────────────────────────────────────────
function renderStates(d) {
  if (!d || typeof d !== 'object') return;
  try {
    const soc     = sv(d,'sensor.huawei_soc');
    const power   = sv(d,'sensor.huawei_power');
    const rem     = sv(d,'sensor.huawei_kwh_remaining');
    const tmax    = sv(d,'sensor.huawei_cell_temp_max');
    const tmin    = sv(d,'sensor.huawei_cell_temp_min');
    const tavg    = sv(d,'sensor.huawei_cell_temp_avg');
    const vmax    = sv(d,'sensor.huawei_cell_voltage_max');
    const vmin    = sv(d,'sensor.huawei_cell_voltage_min');
    const vavg    = sv(d,'sensor.huawei_cell_voltage_avg');
    const delta   = sv(d,'sensor.huawei_cell_delta_mv');
    const soh     = sv(d,'sensor.huawei_health_score');
    const rte     = sv(d,'sensor.battery_round_trip_efficiency') || sv(d,'sensor.round_trip_efficiency');
    const chgkwh  = sv(d,'sensor.battery_charge_today_kwh');
    const dchgkwh = sv(d,'sensor.battery_discharge_today_kwh');
    const cycle   = sv(d,'sensor.huawei_cycle_count');
    const tte     = sv(d,'sensor.battery_time_to_empty');
    const ttf     = sv(d,'sensor.battery_time_to_full');
    const mode    = ss(d,'sensor.huawei_state_mode');
    const label   = ss(d,'sensor.huawei_health_label');
    const hasAlarm= ss(d,'sensor.huawei_has_alarm');
    const runtime = ss(d,'sensor.battery_runtime_estimate');

    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));

    // Topbar pill
    const pill = el('batt-status-pill');
    if (pill) {
      const isCharging   = power > 20;
      const isDischg     = power < -20;
      const alarm        = hasAlarm === 'True' || hasAlarm === 'on';
      pill.textContent   = alarm ? 'Alarm!' : isCharging ? 'Charging' : isDischg ? 'Discharging' : 'Standby';
      pill.className     = 'pill ' + (alarm ? 'warn' : isCharging ? 'ok' : isDischg ? 'err' : 'ok');
    }

    // Ring SOC
    updateRing(soc);

    // Sidebar sys
    const sysPill = el('sys-pill'); if (sysPill) sysPill.className = 'sys-pill' + (soh > 0 ? '' : ' off');
    const sysDot  = el('sys-dot');  if (sysDot)  sysDot.className  = 'dot' + (soh > 0 ? '' : ' off');
    const sysTxt  = el('sys-txt');  if (sysTxt)  { sysTxt.textContent = soh > 80 ? 'BMS Sehat' : soh > 50 ? 'BMS Perhatian' : 'BMS Kritis'; sysTxt.className = 'sys-txt' + (soh > 50 ? '' : ' off'); }

    // Hero stats
    set('batt-power-val', fmt(Math.abs(power), 0));
    set('batt-power-unit', power > 20 ? ' W (Charging)' : power < -20 ? ' W (Discharging)' : ' W (Idle)');
    const modeEl = el('batt-mode-sub');
    if (modeEl) { modeEl.textContent = power > 20 ? `Charging - ${fmt(power,0)} W masuk` : power < -20 ? `Discharging - ${fmt(Math.abs(power),0)} W keluar` : 'Idle / Standby';
      modeEl.className = 'soc-stat-sub ' + (power > 20 ? 'charge' : power < -20 ? 'discharge' : 'idle'); }
    set('batt-rem-val', fmt(rem, 2));
    set('batt-runtime-sub', runtime !== '-' ? runtime : (power < -20 ? `~ ${fmt(tte,1)} jam ke kosong` : power > 20 ? `~ ${fmt(ttf,1)} jam ke penuh` : '-'));
    set('batt-mode-val', mode);
    set('batt-health-sub', label !== '-' ? label : (soh >= 90 ? 'Excellent' : soh >= 70 ? 'Good' : 'Perhatian'));
    set('batt-chg-today', fmt(chgkwh, 2));
    set('batt-dchg-today', fmt(dchgkwh, 2));
    set('batt-rte-val', fmt(rte, 1));
    set('batt-cycle-sub', cycle > 0 ? `${Math.round(cycle)} siklus` : '- siklus');

    // Stat cards
    set('sc-tmax', fmt(tmax,1)); set('sc-tmax-sub', `Min: ${fmt(tmin,1)}C | Avg: ${fmt(tavg,1)}C`);
    set('sc-vmax', fmt(vmax,3)); set('sc-vmax-sub', `Min: ${fmt(vmin,3)} V | Avg: ${fmt(vavg,3)} V`);
    set('sc-delta', fmt(delta,0));
    const deltaEl = el('sc-delta-sub');
    if (deltaEl) { deltaEl.textContent = delta < 10 ? 'Balance baik' : delta < 30 ? 'Perhatikan balance' : 'Balance kritis'; deltaEl.style.color = delta < 10 ? 'var(--mint)' : delta < 30 ? 'var(--warn)' : 'var(--rose)'; }
    set('sc-soh', fmt(soh,0));
    const sohEl = el('sc-soh-sub');
    if (sohEl) { sohEl.textContent = soh >= 90 ? 'Excellent' : soh >= 70 ? 'Good' : soh >= 50 ? 'Fair' : 'Poor';
      sohEl.style.color = soh >= 90 ? 'var(--mint)' : soh >= 70 ? 'var(--amber)' : 'var(--rose)'; }

    // Cell heatmap
    const setCell = (id, val, warnVal, dangerVal, invert=false) => {
      const box = el(id);
      if (!box) return;
      const v = parseFloat(val);
      if (!isNaN(v)) {
        const cls = invert
          ? (v >= dangerVal ? 'good' : v >= warnVal ? 'warn' : 'danger')
          : (v >= dangerVal ? 'danger' : v >= warnVal ? 'warn' : 'good');
        box.className = 'cell-box ' + cls;
      }
    };
    setCell('cell-vmax', vmax, 3.55, 3.65);
    setCell('cell-vmin', vmin, 3.0, 2.8);
    setCell('cell-delta', delta, 20, 50);
    setCell('cell-tmax', tmax, 40, 50);
    setCell('cell-soh', soh, 70, 50);
    set('cv-vmax', fmt(vmax,3)); set('cv-vmin', fmt(vmin,3)); set('cv-vavg', fmt(vavg,3));
    set('cv-delta', fmt(delta,0)); set('cv-tmax', fmt(tmax,1)); set('cv-tmin', fmt(tmin,1));
    set('cv-tavg', fmt(tavg,1)); set('cv-soh', fmt(soh,0));

    // Alarm
    const alEl = el('al-hasalarm');
    if (alEl) { alEl.textContent = hasAlarm === 'True' || hasAlarm === 'on' ? 'ADA ALARM' : 'Normal';
      alEl.className = 'mono-val ' + (hasAlarm === 'True' || hasAlarm === 'on' ? 'status-bad' : 'status-ok'); }
    set('al-label', label); set('al-cycle', cycle > 0 ? Math.round(cycle) + ' siklus' : '-');
    const alActive = el('al-active');
    if (alActive) { alActive.textContent = ss(d,'binary_sensor.smartpowermeter_any_alarm_active') === 'on' ? 'Aktif' : 'Tidak ada'; }

    // Health bars
    const setBar = (id, w) => { const e = el(id); if (e) e.style.width = Math.min(100, Math.max(0, w)) + '%'; };
    setBar('hb-soc',   soc);  set('hbv-soc',   fmt(soc,0) + '%');
    setBar('hb-soh',   soh);  set('hbv-soh',   fmt(soh,0) + '%');
    setBar('hb-temp',  pct(tmax, 45)); set('hbv-temp', fmt(tmax,1) + 'C');
    setBar('hb-delta', pct(delta, 50)); set('hbv-delta', fmt(delta,0) + ' mV');
    setBar('hb-rte',   rte);  set('hbv-rte',   fmt(rte,1) + '%');

    // Detail table
    set('dt-soc', fmt(soc,0));
    const dstSoc = el('dst-soc');
    if (dstSoc) { dstSoc.textContent = soc >= 60 ? 'Normal' : soc >= 30 ? 'Rendah' : 'Kritis'; dstSoc.className = 'mono-val ' + (soc >= 60 ? 'status-ok' : soc >= 30 ? 'status-warn' : 'status-bad'); }
    set('dt-power', fmt(power,0));
    const dstPow = el('dst-power');
    if (dstPow) { dstPow.textContent = power > 20 ? 'Charging' : power < -20 ? 'Discharging' : 'Idle'; dstPow.className = 'mono-val ' + (power > 20 ? 'status-ok' : 'status-warn'); }
    set('dt-mode', mode); set('dt-rem', fmt(rem,2));

    // ── HEATMAP 15 SEL + warna kesehatan ──
    // Pakai hasil backend (lib/metrics.php) kalau tersedia - algoritma
    // klasifikasi per-sel ini sebelumnya 100% duplikat dgn jikong.php.
    (function(){
      const chm = d.computed?.cell_heatmap?.huawei;
      const std = sv(d,'sensor.huawei_cell_std_dev');
      const bal = sv(d,'sensor.huawei_balance_score');
      const W = v => Math.max(5, Math.min(100, ((v - 3.0)/(3.4 - 3.0))*100)); // LiFePO4 3.0-3.4V

      if (chm && chm.valid) {
        set('hs-max', chm.max.toFixed(3)+' V'); set('hs-avg', chm.avg.toFixed(3)+' V');
        set('hs-min', chm.min.toFixed(3)+' V'); set('hs-delta', chm.delta_mv.toFixed(0)+' mV');
        set('hs-std', (std||0).toFixed(2)+' mV'); set('hs-bal', (bal||0).toFixed(0)+'/100');
        let html = '';
        chm.cells.forEach(c => {
          html += '<div class="heat-cell '+c.class+'"><div class="hc-top">'
                + '<span class="hc-lbl">Sel '+c.cell+'</span>'
                + '<span class="hc-val">'+(c.voltage>0?c.voltage.toFixed(3):'\u2014')+' V</span></div>'
                + '<div class="heat-bar"><i style="width:'+(c.voltage>0?W(c.voltage):0)+'%"></i></div></div>';
        });
        const g = el('heat-grid'); if (g) g.innerHTML = html;
      } else {
        // Fallback: hitung lokal kalau proxy.php lama belum punya "computed"
        const cells = [];
        for (let i=1;i<=15;i++) cells.push(sv(d,'sensor.huawei_cell_'+i+'_voltage'));
        const valid = cells.filter(v => v > 0);
        if (valid.length){
          const mx = Math.max(...valid), mn = Math.min(...valid);
          const av = valid.reduce((a,b)=>a+b,0)/valid.length;
          set('hs-max', mx.toFixed(3)+' V'); set('hs-avg', av.toFixed(3)+' V');
          set('hs-min', mn.toFixed(3)+' V'); set('hs-delta', ((mx-mn)*1000).toFixed(0)+' mV');
          set('hs-std', (std||0).toFixed(2)+' mV'); set('hs-bal', (bal||0).toFixed(0)+'/100');
          let html = '';
          cells.forEach((v,i) => {
            let cls = 'n';
            if (v > 0){
              const dev = (v - av) * 1000;
              if (Math.abs(dev) > 50)      cls = 'cr';
              else if (dev > 25)           cls = 'hi';
              else if (dev < -25)          cls = 'lo';
            }
            html += '<div class="heat-cell '+cls+'"><div class="hc-top">'
                  + '<span class="hc-lbl">Sel '+(i+1)+'</span>'
                  + '<span class="hc-val">'+(v>0?v.toFixed(3):'\u2014')+' V</span></div>'
                  + '<div class="heat-bar"><i style="width:'+(v>0?W(v):0)+'%"></i></div></div>';
          });
          const g = el('heat-grid'); if (g) g.innerHTML = html;
        }
      }
      // ── Thermal & Diagnostik ──
      const tC = k => { const v = sv(d,'sensor.'+k); return v ? v.toFixed(0)+'\u00b0C' : '\u2014'; };
      const txt = k => (d['sensor.'+k] ? d['sensor.'+k].state : '\u2014');
      set('dg-thermal',  txt('huawei_thermal_status'));
      set('dg-celltemp', (tmin?tmin.toFixed(0):'\u2014')+' / '+(tavg?tavg.toFixed(0):'\u2014')+' / '+(tmax?tmax.toFixed(0):'\u2014')+' \u00b0C');
      set('dg-mos',   tC('huawei_mosfet_temp'));
      set('dg-probe', tC('huawei_temp_probe1')+' / '+tC('huawei_temp_probe2'));
      set('dg-amb',   tC('huawei_ambient_temp'));
      set('dg-imb',   txt('huawei_imbalance_level'));
      set('dg-cyc',   sv(d,'sensor.huawei_cycle_count').toFixed(0)+' siklus');
      set('dg-read',  sv(d,'sensor.huawei_modbus_read_time').toFixed(0)+' ms');

      // ── Kelistrikan & Kapasitas Pack ──
      set('pk-vbat', fmt(sv(d,'sensor.huawei_battery_voltage'),2));
      set('pk-vbus', fmt(sv(d,'sensor.huawei_busbar_voltage'),2));
      set('pk-cur',  fmt(sv(d,'sensor.huawei_current'),2));
      set('pk-soh',  fmt(sv(d,'sensor.huawei_soh'),0));
      set('pk-kdes', fmt(sv(d,'sensor.huawei_kwh_design_full'),2));
      set('pk-kact', fmt(sv(d,'sensor.huawei_kwh_actual_full'),2));
    })();
    set('dt-chgkwh', fmt(chgkwh,2)); set('dt-dchgkwh', fmt(dchgkwh,2));
    set('dt-rte', fmt(rte,1));
    const dstRte = el('dst-rte');
    if (dstRte) { dstRte.textContent = rte >= 90 ? 'Excellent' : rte >= 80 ? 'Good' : 'Perhatian'; dstRte.className = 'mono-val ' + (rte >= 90 ? 'status-ok' : rte >= 80 ? 'status-warn' : 'status-bad'); }
    set('dt-tte', fmt(tte,1)); set('dt-ttf', fmt(ttf,1));
    set('dt-tmax', fmt(tmax,1)); set('dt-tmin', fmt(tmin,1)); set('dt-tavg', fmt(tavg,1));
    const dstTmax = el('dst-tmax');
    if (dstTmax) { dstTmax.textContent = tmax < 35 ? 'Normal' : tmax < 45 ? 'Hangat' : 'Panas!'; dstTmax.className = 'mono-val ' + (tmax < 35 ? 'status-ok' : tmax < 45 ? 'status-warn' : 'status-bad'); }
    set('dt-vmax', fmt(vmax,3)); set('dt-vmin', fmt(vmin,3)); set('dt-vavg', fmt(vavg,3));
    set('dt-delta', fmt(delta,0));
    const dstDelta = el('dst-delta');
    if (dstDelta) { dstDelta.textContent = delta < 10 ? 'Balance' : delta < 30 ? 'Perhatian' : 'Imbalance!'; dstDelta.className = 'mono-val ' + (delta < 10 ? 'status-ok' : delta < 30 ? 'status-warn' : 'status-bad'); }
    set('dt-soh', fmt(soh,0)); set('dt-label', label);
    const dstSoh = el('dst-soh');
    if (dstSoh) { dstSoh.textContent = soh >= 90 ? 'Excellent' : soh >= 70 ? 'Good' : soh >= 50 ? 'Fair' : 'Poor'; dstSoh.className = 'mono-val ' + (soh >= 90 ? 'status-ok' : soh >= 70 ? 'status-warn' : 'status-bad'); }
    set('dt-cycle', cycle > 0 ? Math.round(cycle) : '-');
    set('dt-alarm', hasAlarm === 'True' || hasAlarm === 'on' ? 'ADA ALARM' : 'Normal');
    const dstAlarm = el('dst-alarm');
    if (dstAlarm) { dstAlarm.textContent = hasAlarm === 'True' || hasAlarm === 'on' ? 'Aktif' : 'Normal'; dstAlarm.className = 'mono-val ' + (hasAlarm === 'True' || hasAlarm === 'on' ? 'status-bad' : 'status-ok'); }

  } catch(err) { console.error('render error:', err); }
}

// ── CHART DATA ───────────────────────────────────────────────────
async function fetchCharts() {
  try {
    const [res24, res7d] = await Promise.allSettled([
      fetch('proxy.php?type=influx_battery_history&range=' + battRange),
      fetch('proxy.php?type=influx_battery_7d'),
    ]);

    // 24 jam
    if (res24.status === 'fulfilled' && res24.value.ok) {
      const raw = await res24.value.json();
      const toXY = arr => Array.isArray(arr) ? arr.map(r => [new Date(r.time).getTime(), r.value]) : [];
      chartMain.updateSeries([
        {name:'SOC (%)',       data: toXY(raw.soc)},
        {name:'Daya (W)',      data: toXY(raw.power)},
        {name:'Suhu Maks (C)',data: toXY(raw.temp)},
      ]);
    }

    // 7 hari
    if (res7d.status === 'fulfilled' && res7d.value.ok) {
      const raw = await res7d.value.json();
      const lbl = t => new Date(t).toLocaleDateString('id-ID',{day:'numeric',month:'short'});
      const toCat = arr => Array.isArray(arr) ? arr.map(r => ({x: lbl(r.time), y: +(r.value).toFixed(2)})) : [];
      chart7d.updateSeries([
        {name:'Charge (kWh)',    data: toCat(raw.charge_kwh)},
        {name:'Discharge (kWh)', data: toCat(raw.dischg_kwh)},
      ]);
    }
  } catch(e) { console.warn('Chart fetch error:', e); }
}

// ── LIVE DATA: SSE (real-time push), fallback ke polling 15 detik ──
async function fetchStatesOnce() {
  try {
    const res = await fetch('proxy.php?type=ha_states');
    if (res.status === 401) { location.href = 'login.php'; return; }
    if (!res.ok) throw new Error();
    const d = await res.json();
    if (d && !d.error) renderStates(d);
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

// ── INIT ─────────────────────────────────────────────────────────
startLive();

// ── RANGE SELECTOR ──
let battRange = '24h';
document.getElementById('range-sel').addEventListener('click', e => {
  const b = e.target.closest('.rb'); if(!b) return;
  battRange = b.dataset.r;
  document.querySelectorAll('#range-sel .rb').forEach(x=>x.classList.toggle('on', x===b));
  fetchCharts();
});

fetchCharts();
setInterval(fetchCharts, 300000);
</script>
</body>
</html>