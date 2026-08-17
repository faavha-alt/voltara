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

$page_title  = 'Baterai Jikong - Voltara EMS';
$active      = 'battery';
$tb_h1       = 'Monitor Baterai - Jikong JK-BMS';
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
    <a href="huawei.php" style="padding:7px 18px;font-size:12px;font-weight:600;text-decoration:none;color:var(--dim)">Huawei ESM</a><a href="jikong.php" style="padding:7px 18px;font-size:12px;font-weight:600;text-decoration:none;color:#10b981;background:rgba(16,185,129,.12)">Jikong JK</a>
  </div>
</div>

<!-- HERO SOC RING -->
<div class="hero-batt">
  <div class="soc-ring-wrap">
    <div class="soc-ring-svg">
      <svg viewBox="0 0 160 160" width="160" height="160">
        <circle class="ring-bg" cx="80" cy="80" r="66"/>
        <circle class="ring-fill" id="ring-fill" cx="80" cy="80" r="66"
          stroke-dasharray="414.69" stroke-dashoffset="414.69" stroke="var(--mint)"/>
      </svg>
      <div class="ring-center">
        <div class="ring-pct" id="ring-pct">-</div>
        <div class="ring-lbl">SOC</div>
      </div>
    </div>

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
        <div class="soc-stat-sub" id="batt-remah-sub" style="color:var(--dim)">-</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Mode Operasi</div>
        <div><span class="soc-stat-val" id="batt-mode-val" style="font-size:15px">-</span></div>
        <div class="soc-stat-sub" id="batt-health-sub" style="color:var(--mint)">-</div>
      </div>
    </div>

    <div class="soc-stats" style="margin-left:16px">
      <div class="soc-stat">
        <div class="soc-stat-lbl">Tegangan Pack</div>
        <div><span class="soc-stat-val" id="batt-vbat">-</span>
             <span class="soc-stat-unit"> V</span></div>
        <div class="soc-stat-sub" style="color:var(--dim)">Total 15S</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Kapasitas Sisa</div>
        <div><span class="soc-stat-val" id="batt-remah">-</span>
             <span class="soc-stat-unit"> Ah</span></div>
        <div class="soc-stat-sub" style="color:var(--dim)">Remaining capacity</div>
      </div>
      <div class="soc-stat">
        <div class="soc-stat-lbl">Arus Pack <span class="tag" style="background:rgba(245,158,11,.15);color:var(--amber2);font-size:9px">tentatif</span></div>
        <div><span class="soc-stat-val" id="batt-cur">-</span>
             <span class="soc-stat-unit"> A</span></div>
        <div class="soc-stat-sub" id="batt-cur-sub" style="color:var(--dim)">-</div>
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
      <div class="sc-lbl">Health Score</div>
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
        <div class="diag-row"><span class="dl">Suhu CMOS (MOSFET)</span><span class="dv" id="dg-mos">-</span></div>
        <div class="diag-row"><span class="dl">Probe T1 / T2</span><span class="dv" id="dg-probe">-</span></div>
        <div class="diag-row"><span class="dl">Imbalance</span><span class="dv" id="dg-imb">-</span></div>
        <div class="diag-row"><span class="dl">Total Siklus <span class="tag" style="background:rgba(245,158,11,.15);color:var(--amber2);font-size:9px">tentatif</span></span><span class="dv" id="dg-cyc">-</span></div>
        <div class="diag-row"><span class="dl">Jumlah Sel</span><span class="dv" id="dg-ncell">-</span></div>
        <div class="diag-row"><span class="dl">Modbus Read</span><span class="dv" id="dg-read">-</span></div>
      </div>
    </div>
  </div>

  <!-- CHART UTAMA + ALARM -->
  <div class="grid21">
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

    <div style="display:flex;flex-direction:column;gap:14px">
      <div class="card" style="margin-bottom:0">
        <div class="card-hd"><div class="card-ttl">Status Alarm BMS</div></div>
        <div class="card-bd" style="padding:12px 16px">
          <table class="vtable">
            <tbody>
              <tr><td>Has Alarm</td>     <td class="mono-val" id="al-hasalarm">-</td></tr>
              <tr><td>Health Label</td>  <td class="mono-val" id="al-label">-</td></tr>
              <tr><td>Cycle Count</td>   <td class="mono-val" id="al-cycle">-</td></tr>
              <tr><td>Warning</td>       <td class="mono-val" id="al-active">-</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- HEALTH BARS + RESISTANSI SEL -->
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
          <div class="hb-lbl">Health Score</div>
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
          <div class="hb-lbl">Balance Score</div>
          <div class="hb-track"><div class="hb-fill" id="hb-bal" style="width:0%;background:linear-gradient(90deg,var(--amber),var(--amber2))"></div></div>
          <div class="hb-val" id="hbv-bal">-</div>
        </div>
      </div>
    </div>

    <!-- Resistansi Sel (eksklusif JK) -->
    <div class="card" style="margin-bottom:0">
      <div class="card-hd">
        <div><div class="card-ttl">Resistansi Sel (15S)</div>
             <div class="card-sub">mOhm per sel - indikator kesehatan koneksi</div></div>
        <span class="tag">JK Exclusive</span>
      </div>
      <div class="card-bd">
        <div id="chart-res" style="min-height:200px"></div>
      </div>
    </div>
  </div>

  <!-- KELISTRIKAN & KAPASITAS PACK -->
  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Kelistrikan & Kapasitas Pack</div>
        <div class="card-sub">Jikong JK-BMS - 15S LiFePO4</div></div>
      <span class="tag">Jikong BMS</span>
    </div>
    <div class="card-bd">
      <div class="cell-grid cell-grid-6">
      <div class="cell-box"><div class="cell-lbl">Tegangan Pack</div><div class="cell-val" id="pk-vbat">-</div><div class="cell-unit">V</div></div>
      <div class="cell-box"><div class="cell-lbl">Arus Pack</div><div class="cell-val" id="pk-cur">-</div><div class="cell-unit">A - tentatif</div></div>
      <div class="cell-box"><div class="cell-lbl">SOC</div><div class="cell-val" id="pk-soc">-</div><div class="cell-unit">%</div></div>
      <div class="cell-box"><div class="cell-lbl">Health</div><div class="cell-val" id="pk-soh">-</div><div class="cell-unit">%</div></div>
      <div class="cell-box"><div class="cell-lbl">Kap. Design</div><div class="cell-val" id="pk-kdes">-</div><div class="cell-unit">kWh</div></div>
      <div class="cell-box"><div class="cell-lbl">Sisa Kapasitas</div><div class="cell-val" id="pk-remah">-</div><div class="cell-unit">Ah</div></div>
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

// Clock
setInterval(() => {
  set('clock', new Date().toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'}));
}, 1000);

// SOC RING
function updateRing(soc) {
  const c = 2 * Math.PI * 66;
  const ring = el('ring-fill'); if (!ring) return;
  ring.style.strokeDashoffset = c - (soc / 100) * c;
  ring.style.stroke = soc >= 60 ? 'var(--mint)' : soc >= 30 ? 'var(--amber)' : 'var(--rose)';
  set('ring-pct', Math.round(soc) + '%');
}

const APEX_DARK = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

// Chart utama: SOC + Power + Suhu
const chartMain = new ApexCharts(el('chart-batt-main'), {
  series: [
    {name:'SOC (%)',        type:'area', data:[]},
    {name:'Daya (W)',       type:'line', data:[]},
    {name:'Suhu Maks (C)', type:'line', data:[]},
  ],
  chart: {...APEX_DARK.chart, type:'line', height:260},
  colors: ['#10b981','#6366f1','#f59e0b'],
  stroke: {curve:'smooth', width:[2.5, 2, 2]},
  fill: {type:['gradient','solid','solid'], gradient:{shadeIntensity:1,opacityFrom:.2,opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}, datetimeFormatter:{hour:'HH:mm'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: [
    {title:{text:'SOC (%)', style:{color:'#10b981'}}, min:0, max:100, labels:{style:{colors:'#10b981'}, formatter:v=>Math.round(v)}},
    {opposite:true, title:{text:'Daya (W)', style:{color:'#6366f1'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}},
    {show:false, min:0, max:60},
  ],
  grid: APEX_DARK.grid,
  legend:{position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, itemMargin:{horizontal:10}},
  tooltip:{...APEX_DARK.tooltip, shared:true, x:{format:'HH:mm dd MMM'}},
  annotations:{ yaxis:[
    {y:20, borderColor:'#f43f5e', borderWidth:1, strokeDashArray:3, label:{text:'SOC Rendah',style:{color:'#f43f5e',background:'transparent',fontSize:'10px'}}},
    {y:80, borderColor:'#10b981', borderWidth:1, strokeDashArray:3, label:{text:'SOC Penuh',style:{color:'#10b981',background:'transparent',fontSize:'10px'}}},
  ]}
});
chartMain.render();

// Chart resistansi 15 sel (eksklusif JK)
const chartRes = new ApexCharts(el('chart-res'), {
  series: [{name:'Resistansi (mOhm)', data: []}],
  chart: {...APEX_DARK.chart, type:'bar', height:210},
  colors: ['#38bdf8'],
  plotOptions: {bar:{borderRadius:4, columnWidth:'62%', distributed:false}},
  dataLabels: {enabled:false},
  xaxis: { categories: Array.from({length:15},(_,i)=>'S'+(i+1)),
           labels:{style:{colors:'#5b6178', fontSize:'10px'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: {labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}, title:{text:'mOhm', style:{color:'#98a0b3'}}},
  grid: APEX_DARK.grid,
  legend:{show:false},
  tooltip:{...APEX_DARK.tooltip, y:{formatter:v=>v.toFixed(2)+' mOhm'}},
});
chartRes.render();

// RENDER
function renderStates(d) {
  if (!d || typeof d !== 'object') return;
  try {
    const soc     = sv(d,'sensor.jikong_soc');
    const power   = sv(d,'sensor.jikong_power');
    const rem     = sv(d,'sensor.jikong_kwh_remaining');
    const remAh   = sv(d,'sensor.jikong_remaining_ah');
    const vbat    = sv(d,'sensor.jikong_battery_voltage');
    const cur     = sv(d,'sensor.jikong_current');
    const tmax    = sv(d,'sensor.jikong_cell_temp_max');
    const tmin    = sv(d,'sensor.jikong_cell_temp_min');
    const tavg    = sv(d,'sensor.jikong_cell_temp_avg');
    const vmax    = sv(d,'sensor.jikong_cell_voltage_max');
    const vmin    = sv(d,'sensor.jikong_cell_voltage_min');
    const vavg    = sv(d,'sensor.jikong_cell_voltage_avg');
    const delta   = sv(d,'sensor.jikong_cell_delta_mv');
    const soh     = sv(d,'sensor.jikong_health_score');
    const bal     = sv(d,'sensor.jikong_balance_score');
    const cycle   = sv(d,'sensor.jikong_cycle_count');
    const ncell   = sv(d,'sensor.jikong_cell_count');
    const kdes    = sv(d,'sensor.jikong_kwh_design_full');
    const mode    = ss(d,'sensor.jikong_state_mode');
    const label   = ss(d,'sensor.jikong_health_label');
    const hasAlarm= ss(d,'sensor.jikong_has_alarm');
    const hasWarn = ss(d,'sensor.jikong_has_warning');

    set('tb-sub', 'Diperbarui ' + new Date().toLocaleTimeString('id-ID'));

    // Topbar pill (arus tentatif → pakai daya utk arah)
    const pill = el('batt-status-pill');
    if (pill) {
      const isCharging = power > 20, isDischg = power < -20;
      const alarm = hasAlarm === 'True' || hasAlarm === 'on';
      pill.textContent = alarm ? 'Alarm!' : isCharging ? 'Charging' : isDischg ? 'Discharging' : 'Standby';
      pill.className   = 'pill ' + (alarm ? 'warn' : isCharging ? 'ok' : isDischg ? 'err' : 'ok');
    }

    updateRing(soc);

    // Sidebar sys
    const sysPill = el('sys-pill'); if (sysPill) sysPill.className = 'sys-pill' + (soh > 0 ? '' : ' off');
    const sysDot  = el('sys-dot');  if (sysDot)  sysDot.className  = 'dot' + (soh > 0 ? '' : ' off');
    const sysTxt  = el('sys-txt');  if (sysTxt)  { sysTxt.textContent = soh > 80 ? 'BMS Sehat' : soh > 50 ? 'BMS Perhatian' : soh > 0 ? 'BMS Kritis' : 'BMS Offline'; sysTxt.className = 'sys-txt' + (soh > 50 ? '' : ' off'); }

    // Hero
    set('batt-power-val', fmt(Math.abs(power), 0));
    set('batt-power-unit', power > 20 ? ' W (Charging)' : power < -20 ? ' W (Discharging)' : ' W (Idle)');
    const modeEl = el('batt-mode-sub');
    if (modeEl) { modeEl.textContent = power > 20 ? `Charging - ${fmt(power,0)} W masuk` : power < -20 ? `Discharging - ${fmt(Math.abs(power),0)} W keluar` : 'Idle / Standby';
      modeEl.className = 'soc-stat-sub ' + (power > 20 ? 'charge' : power < -20 ? 'discharge' : 'idle'); }
    set('batt-rem-val', fmt(rem, 2));
    set('batt-remah-sub', remAh > 0 ? `${fmt(remAh,1)} Ah tersisa` : '-');
    set('batt-mode-val', mode);
    set('batt-health-sub', label !== '-' ? label : (soh >= 90 ? 'Excellent' : soh >= 70 ? 'Good' : 'Perhatian'));
    set('batt-vbat', fmt(vbat, 2));
    set('batt-remah', fmt(remAh, 1));
    set('batt-cur', fmt(Math.abs(cur), 2));
    const curSub = el('batt-cur-sub');
    if (curSub) curSub.textContent = cur > 0.05 ? 'Mengisi' : cur < -0.05 ? 'Beban' : 'Idle';

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

    // Alarm
    const alEl = el('al-hasalarm');
    if (alEl) { const a = hasAlarm === 'True' || hasAlarm === 'on'; alEl.textContent = a ? 'ADA ALARM' : 'Normal'; alEl.className = 'mono-val ' + (a ? 'status-bad' : 'status-ok'); }
    set('al-label', label);
    set('al-cycle', cycle > 0 ? Math.round(cycle) + ' siklus' : '-');
    const alActive = el('al-active');
    if (alActive) { const w = hasWarn === 'True' || hasWarn === 'on'; alActive.textContent = w ? 'Ada warning' : 'Tidak ada'; alActive.className = 'mono-val ' + (w ? 'status-warn' : 'status-ok'); }

    // Health bars
    const setBar = (id, w) => { const e = el(id); if (e) e.style.width = Math.min(100, Math.max(0, w)) + '%'; };
    setBar('hb-soc',   soc);  set('hbv-soc',   fmt(soc,0) + '%');
    setBar('hb-soh',   soh);  set('hbv-soh',   fmt(soh,0) + '%');
    setBar('hb-temp',  pct(tmax, 45)); set('hbv-temp', fmt(tmax,1) + 'C');
    setBar('hb-delta', pct(delta, 50)); set('hbv-delta', fmt(delta,0) + ' mV');
    setBar('hb-bal',   bal);  set('hbv-bal',   fmt(bal,0) + '/100');

    // HEATMAP 15 SEL - pakai hasil backend (lib/metrics.php) kalau
    // tersedia; algoritma ini sebelumnya 100% duplikat dgn huawei.php.
    (function(){
      const chm = d.computed?.cell_heatmap?.jikong;
      const std = sv(d,'sensor.jikong_cell_std_dev');
      const W = v => Math.max(5, Math.min(100, ((v - 3.0)/(3.4 - 3.0))*100));

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
        for (let i=1;i<=15;i++) cells.push(sv(d,'sensor.jikong_cell_'+i+'_voltage'));
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
            if (v > 0){ const dev = (v - av) * 1000;
              if (Math.abs(dev) > 50) cls = 'cr'; else if (dev > 25) cls = 'hi'; else if (dev < -25) cls = 'lo'; }
            html += '<div class="heat-cell '+cls+'"><div class="hc-top">'
                  + '<span class="hc-lbl">Sel '+(i+1)+'</span>'
                  + '<span class="hc-val">'+(v>0?v.toFixed(3):'\u2014')+' V</span></div>'
                  + '<div class="heat-bar"><i style="width:'+(v>0?W(v):0)+'%"></i></div></div>';
          });
          const g = el('heat-grid'); if (g) g.innerHTML = html;
        }
      }
    })();

    // RESISTANSI 15 SEL → update chart (nilai HA dalam Ω → tampilkan mΩ)
    const res = [];
    for (let i=1;i<=15;i++){ const r = sv(d,'sensor.jikong_cell_'+i+'_resistance'); res.push(+( (r<1? r*1000 : r) ).toFixed(2)); }
    if (res.some(x=>x>0)) chartRes.updateSeries([{name:'Resistansi (mOhm)', data: res}]);

    // Thermal & Diagnostik
    const tC = k => { const v = sv(d,'sensor.'+k); return v ? v.toFixed(1)+'\u00b0C' : '\u2014'; };
    const txt = k => (d['sensor.'+k] ? d['sensor.'+k].state : '\u2014');
    set('dg-thermal',  txt('jikong_thermal_status'));
    set('dg-celltemp', (tmin?tmin.toFixed(1):'\u2014')+' / '+(tavg?tavg.toFixed(1):'\u2014')+' / '+(tmax?tmax.toFixed(1):'\u2014')+' \u00b0C');
    set('dg-mos',   tC('jikong_cmos_temp'));
    set('dg-probe', tC('jikong_temp_t1')+' / '+tC('jikong_temp_t2'));
    set('dg-imb',   txt('jikong_imbalance_level'));
    set('dg-cyc',   cycle > 0 ? cycle.toFixed(0)+' siklus' : '\u2014');
    set('dg-ncell', ncell > 0 ? ncell.toFixed(0)+' sel' : '15 sel');
    set('dg-read',  sv(d,'sensor.jikong_modbus_read_time').toFixed(0)+' ms');

    // Kelistrikan & Kapasitas Pack
    set('pk-vbat', fmt(vbat,2));
    set('pk-cur',  fmt(cur,2));
    set('pk-soc',  fmt(soc,0));
    set('pk-soh',  fmt(soh,0));
    set('pk-kdes', fmt(kdes,2));
    set('pk-remah',fmt(remAh,1));

  } catch(err) { console.error('render error:', err); }
}

// CHART DATA
async function fetchCharts() {
  try {
    const res24 = await fetch('proxy.php?type=influx_battery_jikong_history&range=' + battRange);
    if (res24.ok) {
      const raw = await res24.json();
      const toXY = arr => Array.isArray(arr) ? arr.map(r => [new Date(r.time).getTime(), r.value]) : [];
      chartMain.updateSeries([
        {name:'SOC (%)',        data: toXY(raw.soc)},
        {name:'Daya (W)',       data: toXY(raw.power)},
        {name:'Suhu Maks (C)', data: toXY(raw.temp)},
      ]);
    }
  } catch(e) { console.warn('Chart fetch error:', e); }
}

// LIVE DATA: SSE (real-time push), fallback ke polling 15 detik
async function fetchStatesOnce() {
  try {
    const res = await fetch('proxy.php?type=ha_states');
    if (res.status === 401) { location.href = 'login.php'; return; }
    if (!res.ok) throw new Error();
    const d = await res.json();
    if (d && !d.error) renderStates(d);
  } catch(e) { set('tb-sub', 'Gagal memuat - ' + new Date().toLocaleTimeString('id-ID')); }
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

// INIT
let battRange = '24h';
startLive();
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