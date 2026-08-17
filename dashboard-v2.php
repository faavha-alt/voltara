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

// -- Konfigurasi partials untuk halaman ini ---------------------
$page_title = 'Voltara EMS (Preview v2)';
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
      <div class="kpi-sub" id="ks-cost">Solar hemat: - Rp</div>
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

    <!-- ENERGY FLOW — gaya node + hub tengah (referensi: dashboard "Origin") -->
    <div class="card">
      <div class="card-hd">
        <div><div class="card-ttl">Power Flow</div><div class="card-sub">Real-time</div></div>
        <div class="pf-legend">
          <span><i class="pf-dot on"></i>Channeled</span>
          <span><i class="pf-dot"></i>Idle</span>
        </div>
      </div>
      <div class="card-bd">
        <div class="pf-scene">
          <svg class="pf-lines" viewBox="0 0 100 100" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path id="pf-line-pv"   d="M20,24 C46,24 46,50 50,50" class="pf-path" stroke="var(--amber)"/>
            <path id="pf-line-grid" d="M80,24 C54,24 54,50 50,50" class="pf-path" stroke="var(--indigo)"/>
            <path id="pf-line-load" d="M20,80 C46,80 46,52 50,50" class="pf-path" stroke="var(--sky)"/>
            <path id="pf-line-batt" d="M80,80 C54,80 54,52 50,50" class="pf-path" stroke="var(--mint)"/>

            <circle id="pf-dot-pv"   r="1.4" fill="var(--amber)"><animateMotion dur="1.6s" repeatCount="indefinite" path="M20,24 C46,24 46,50 50,50"/></circle>
            <circle id="pf-dot-grid" r="1.4" fill="var(--indigo)"><animateMotion dur="1.6s" repeatCount="indefinite" path="M80,24 C54,24 54,50 50,50"/></circle>
            <circle id="pf-dot-load" r="1.4" fill="var(--sky)"><animateMotion dur="1.3s" repeatCount="indefinite" path="M50,50 C46,52 46,80 20,80"/></circle>
            <circle id="pf-dot-batt" r="1.4" fill="var(--mint)"><animateMotion dur="1.3s" repeatCount="indefinite" path="M50,50 C54,52 54,80 80,80"/></circle>
          </svg>

          <div class="pf-node pf-tl">
            <div class="pf-ico" style="background:rgba(245,158,11,.12);color:var(--amber2)"><?= icon('sun', 18) ?></div>
            <div class="pf-lbl">Solar Production</div>
            <div class="pf-val" id="pf-pv">- W</div>
          </div>
          <div class="pf-node pf-tr">
            <div class="pf-ico" style="background:rgba(99,102,241,.12);color:var(--indigo)"><?= icon('plug', 18) ?></div>
            <div class="pf-lbl">Grid</div>
            <div class="pf-val" id="pf-grid">- W</div>
          </div>
          <div class="pf-node pf-bl">
            <div class="pf-ico" style="background:rgba(2,132,199,.12);color:var(--sky)"><?= icon('home', 18) ?></div>
            <div class="pf-lbl">Consumption</div>
            <div class="pf-val" id="pf-load">- W</div>
          </div>
          <div class="pf-node pf-br">
            <div class="pf-ico" style="background:rgba(16,185,129,.12);color:var(--mint2)"><?= icon('battery', 18) ?></div>
            <div class="pf-lbl">Battery</div>
            <div class="pf-val" id="pf-batt">- W</div>
          </div>

          <div class="pf-hub"><?= icon('bolt', 20) ?></div>
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
              <div class="gauge-name">Efisiensi Inverter</div>
              <div class="gauge-desc" id="gd-inverter">DC In ke AC Out</div>
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
// -- HELPERS -----------------------------------------------------
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

// -- CLOCK --------------------------------------------------------
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

// -- APEXCHARTS INIT ----------------------------------------------
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

// -- RENDER STATES ------------------------------------------------

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
    const costDay  = csv ? csv.pln_cost_today : sv(d,'sensor.pln_cost_today');
    const solarVal = csv ? csv.solar_value_today : sv(d,'sensor.solar_value_today');
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
    const solarBadge = el('b-solar');
    if (solarBadge) { solarBadge.textContent = pvPow > 50 ? 'Aktif' : 'Standby';
      solarBadge.className = 'kpi-badge ' + (pvPow > 50 ? 'up' : 'neu'); }

    set('kv-soc', fmt(soc,0)); set('ks-soc', `${fmt(energyRem,2)} kWh tersisa`);
    const battBadge = el('b-batt');
    if (battBadge) { battBadge.textContent = soc >= 80 ? 'Penuh' : soc >= 30 ? 'Normal' : 'Rendah';
      battBadge.className = 'kpi-badge ' + (soc >= 50 ? 'up' : 'neu'); }

    set('kv-energy', fmt(energyDay,2)); set('ks-energy', `Daya: ${fmt(acLoad,0)} W`);
    set('kv-cost', fmtK(costDay)); set('ks-cost', `Solar hemat: Rp ${fmtK(solarVal)}`);

    // Chart topbar
    set('chart-pv-now', fmt(pvPow,0) + ' W');
    set('chart-note', `Mode: ${ss(d,'sensor.smartpowermeter_mppt_charging_mode')} - SOC: ${fmt(soc,0)}% - Bat: ${fmt(batV,1)}V`);

    // Power Flow: isi 4 node (Solar/Grid/Consumption/Battery)
    set('pf-pv',   fmt(pvPow,0) + ' W');
    set('pf-grid', fmt(Math.abs(netGrid),0) + ' W');
    set('pf-load', fmt(acLoad,0) + ' W');
    set('pf-batt', (battPow < -10 ? '-' : battPow > 10 ? '+' : '') + fmt(Math.abs(battPow),0) + ' W');

    // Garis & titik "channeled" (aktif, mengalir jelas) vs "idle" (redup) —
    // sesuai legenda di kartu, dipicu oleh angka daya sungguhan, bukan dekorasi statis.
    const setChannel = (pathId, dotId, active) => {
      const p = el(pathId), dt = dotId ? el(dotId) : null;
      if (p) p.style.opacity = active ? '.85' : '.15';
      if (dt) dt.style.opacity = active ? '1' : '0';
    };
    setChannel('pf-line-pv',   'pf-dot-pv',   pvPow > 20);
    setChannel('pf-line-grid', 'pf-dot-grid', netGrid > 10);
    setChannel('pf-line-load', 'pf-dot-load', acLoad > 20);
    setChannel('pf-line-batt', 'pf-dot-batt', Math.abs(battPow) > 10);

    set('src-val', srcStat);

    // Gauges
    const voltQ = sv(d,'sensor.voltage_quality_score') || 100;
    const invEff= sv(d,'sensor.inverter_efficiency') || 0;
    if (gVoltage)  gVoltage.updateSeries([Math.round(voltQ)]);
    if (gInverter) gInverter.updateSeries([Math.round(invEff)]);
    set('gd-voltage', `V: ${fmt(sv(d,'sensor.smartpowermeter_voltage'),1)} V - ${ss(d,'sensor.smartpowermeter_voltage_status')}`);
    set('gd-inverter', `DC In: ${fmt(chgPow,0)} W ke AC: ${fmt(acLoad,0)} W`);

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

// -- CHART DATA dari Influx ---------------------------------------
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

// -- FETCH STATES (polling 15 detik) ----------------------------
async function fetchStates() {
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

// -- INIT ---------------------------------------------------------
fetchStates();
fetchChartData();
setInterval(fetchStates,   15000);
setInterval(fetchChartData, 300000);
</script>
</body>
</html>