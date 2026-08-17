<?php
require __DIR__ . '/../_session.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
    session_destroy();
    header('Location: /login.php');
    exit;
}
if (!isset($_SESSION['voltara_auth'])) { header('Location: /login.php'); exit; }

$page_title = 'Voltara EMS v2 - Solar Pulse';
$active     = 'overview';

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
?>

<!-- Top Bar -->
<header class="flex justify-between items-center w-full mb-10">
  <div>
    <p class="text-body-md text-on-surface-variant" id="v2-greeting">Selamat datang</p>
    <h2 class="text-headline-lg font-headline-lg text-on-surface">Sistem Anda berjalan <span class="text-primary" id="v2-status-word">normal</span></h2>
  </div>
  <div class="flex items-center gap-4">
    <div class="flex items-center bg-surface-container-low px-4 py-2 rounded-full w-80">
      <span class="material-symbols-outlined text-outline mr-2">search</span>
      <input class="bg-transparent border-none focus:ring-0 text-label-md w-full placeholder:text-outline-variant" placeholder="Cari sesuatu..." type="text">
    </div>
    <a href="/alarm.php" class="p-2 hover:bg-surface-container-highest/40 rounded-full transition-all active:scale-95 relative">
      <span class="material-symbols-outlined text-on-surface-variant">notifications</span>
      <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-status-error" id="v2-alert-dot" style="display:none"></span>
    </a>
    <div class="p-2 rounded-full">
      <span class="material-symbols-outlined text-on-surface-variant" id="v2-clock-icon">schedule</span>
    </div>
  </div>
</header>

<!-- Stat Grid -->
<div class="grid grid-cols-4 gap-card-gap mb-10">
  <div class="glass-card p-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center">
      <span class="material-symbols-outlined text-primary">bolt</span>
    </div>
    <div>
      <p class="text-label-sm text-on-surface-variant">Daya Sekarang</p>
      <p class="text-title-md font-bold" id="v2-pv-now">- W</p>
      <p class="text-[10px] text-status-success flex items-center" id="v2-pv-now-sub"><span class="material-symbols-outlined text-[12px] mr-1">trending_up</span>Output Live</p>
    </div>
  </div>
  <div class="glass-card p-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-secondary-container/10 flex items-center justify-center">
      <span class="material-symbols-outlined text-secondary">light_mode</span>
    </div>
    <div>
      <p class="text-label-sm text-on-surface-variant">Energi Hari Ini</p>
      <p class="text-title-md font-bold" id="v2-energy-today">- kWh</p>
      <p class="text-[10px] flex items-center" id="v2-energy-today-sub">-</p>
    </div>
  </div>
  <div class="glass-card p-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-tertiary-container/10 flex items-center justify-center">
      <span class="material-symbols-outlined text-tertiary">eco</span>
    </div>
    <div>
      <p class="text-label-sm text-on-surface-variant">CO2 Dihindari (Bulan)</p>
      <p class="text-title-md font-bold" id="v2-co2">- kg</p>
      <p class="text-[10px] text-status-success flex items-center" id="v2-co2-sub">-</p>
    </div>
  </div>
  <div class="glass-card p-6 flex items-center gap-4">
    <div class="w-12 h-12 rounded-2xl bg-status-success/10 flex items-center justify-center">
      <span class="material-symbols-outlined text-status-success">payments</span>
    </div>
    <div>
      <p class="text-label-sm text-on-surface-variant">Hemat Hari Ini</p>
      <p class="text-title-md font-bold" id="v2-savings">Rp -</p>
      <p class="text-[10px] text-status-success flex items-center" id="v2-savings-sub">-</p>
    </div>
  </div>
</div>

<!-- Hero Section -->
<div class="grid grid-cols-12 gap-card-gap mb-10">

  <!-- Left: Hero Visualizer -->
  <div class="col-span-8 glass-card p-8 relative overflow-hidden flex flex-col justify-between h-[450px]">
    <div class="relative z-10">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-title-md font-bold">Ringkasan Sistem</h3>
          <p class="text-label-md text-on-surface-variant">Pelacakan aliran energi real-time</p>
        </div>
        <div class="flex gap-4">
          <div class="text-right">
            <p class="text-label-sm text-on-surface-variant">Sumber Daya</p>
            <p class="text-label-md font-bold text-status-success" id="v2-grid-status">-</p>
          </div>
          <div class="text-right">
            <p class="text-label-sm text-on-surface-variant">Status</p>
            <p class="text-label-md font-bold text-on-surface" id="v2-uptime">-</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Placeholder ilustrasi rumah -->
    <div class="absolute inset-0 flex items-center justify-center p-12">
      <div class="w-full h-full relative flex items-center justify-center">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-fixed/20 via-transparent to-secondary-fixed/15 rounded-3xl"></div>
        <span class="material-symbols-outlined text-primary/15" style="font-size:260px">cottage</span>
        <p class="absolute bottom-0 text-[10px] text-on-surface-variant/50 uppercase tracking-widest">Placeholder - ganti dengan ilustrasi rumah Anda</p>

        <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 800 400">
          <path d="M 400 150 Q 420 200 450 250" fill="none" id="path-roof-battery" stroke="transparent"></path>
          <circle class="flow-dot" id="dot-roof-battery" fill="#2455cc" r="4" style="--path: path('M 400 150 Q 420 200 450 250')"></circle>
          <path d="M 400 150 Q 380 200 350 250" fill="none" id="path-roof-house" stroke="transparent"></path>
          <circle class="flow-dot" id="dot-roof-house" fill="#feb31f" r="4" style="--path: path('M 400 150 Q 380 200 350 250'); animation-delay: 1s;"></circle>
        </svg>
      </div>
    </div>

    <div class="relative z-10 flex gap-8">
      <div class="glass-card bg-white/40 px-6 py-4">
        <p class="text-label-sm text-on-surface-variant">Pasokan Solar</p>
        <p class="text-title-md font-bold text-primary" id="v2-flow-pv">- W</p>
      </div>
      <div class="glass-card bg-white/40 px-6 py-4">
        <p class="text-label-sm text-on-surface-variant">Beban Rumah</p>
        <p class="text-title-md font-bold text-secondary" id="v2-flow-load">- W</p>
      </div>
      <div class="glass-card bg-white/40 px-6 py-4">
        <p class="text-label-sm text-on-surface-variant">Status Baterai</p>
        <p class="text-title-md font-bold text-status-success" id="v2-flow-batt">- %</p>
      </div>
    </div>
  </div>

  <!-- Right: Health Scan -->
  <div class="col-span-4 glass-card p-8 flex flex-col justify-between">
    <div>
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-title-md font-bold">Pemindaian Kesehatan</h3>
        <a href="/historis.php" class="text-primary text-label-sm font-bold">Lihat Detail</a>
      </div>
      <div class="space-y-8">
        <div class="flex items-center gap-6">
          <div class="relative w-16 h-16 flex items-center justify-center">
            <svg class="w-full h-full -rotate-90 gauge-svg" viewBox="0 0 36 36">
              <circle class="stroke-surface-container-highest" cx="18" cy="18" fill="none" r="16" stroke-width="3"></circle>
              <circle class="stroke-primary" id="gauge-autarky" cx="18" cy="18" fill="none" r="16" stroke-dasharray="100" stroke-dashoffset="100" stroke-width="3"></circle>
            </svg>
            <span class="absolute text-[10px] font-bold" id="gauge-autarky-val">-%</span>
          </div>
          <div>
            <p class="text-label-md font-bold">Autarki Hari Ini</p>
            <p class="text-label-sm text-status-success" id="gauge-autarky-sub">Mandiri dari PLN</p>
          </div>
        </div>
        <div class="flex items-center gap-6">
          <div class="relative w-16 h-16 flex items-center justify-center">
            <svg class="w-full h-full -rotate-90 gauge-svg" viewBox="0 0 36 36">
              <circle class="stroke-surface-container-highest" cx="18" cy="18" fill="none" r="16" stroke-width="3"></circle>
              <circle class="stroke-status-success" id="gauge-inverter" cx="18" cy="18" fill="none" r="16" stroke-dasharray="100" stroke-dashoffset="100" stroke-width="3"></circle>
            </svg>
            <span class="absolute text-[10px] font-bold" id="gauge-inverter-val">-%</span>
          </div>
          <div>
            <p class="text-label-md font-bold">Efisiensi MPPT (Solar-Baterai)</p>
            <p class="text-label-sm text-on-surface-variant" id="gauge-inverter-sub">-</p>
          </div>
        </div>
        <div class="flex items-center gap-6">
          <div class="relative w-16 h-16 flex items-center justify-center">
            <svg class="w-full h-full -rotate-90 gauge-svg" viewBox="0 0 36 36">
              <circle class="stroke-surface-container-highest" cx="18" cy="18" fill="none" r="16" stroke-width="3"></circle>
              <circle class="stroke-secondary" id="gauge-battery-health" cx="18" cy="18" fill="none" r="16" stroke-dasharray="100" stroke-dashoffset="100" stroke-width="3"></circle>
            </svg>
            <span class="absolute text-[10px] font-bold" id="gauge-battery-health-val">-%</span>
          </div>
          <div>
            <p class="text-label-md font-bold">Kesehatan Baterai</p>
            <p class="text-label-sm text-on-surface-variant" id="gauge-battery-health-sub">-</p>
          </div>
        </div>
      </div>
    </div>
    <a href="/alarm.php" class="p-4 bg-surface-container-low rounded-xl flex items-center justify-between mt-4 hover:bg-surface-container-high/60 transition-colors">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-primary" id="v2-shield-icon">verified_user</span>
        <p class="text-label-sm font-bold" id="v2-shield-text">Memeriksa alarm...</p>
      </div>
      <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
    </a>
  </div>
</div>

<!-- Mid Section -->
<div class="grid grid-cols-12 gap-card-gap">

  <!-- Left: Energy Generation Chart -->
  <div class="col-span-7 glass-card p-8 h-[400px] flex flex-col">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h3 class="text-title-md font-bold">Produksi Energi</h3>
        <p class="text-label-md text-on-surface-variant">Watt per titik waktu</p>
      </div>
      <select class="bg-surface-container-low border-none rounded-lg text-label-sm focus:ring-0" id="v2-range-select">
        <option value="24h" selected>Harian</option>
        <option value="7d">Mingguan</option>
        <option value="30d">Bulanan</option>
      </select>
    </div>
    <div class="flex-1 w-full relative">
      <div id="chart-generation" style="width:100%; height:100%"></div>
    </div>
  </div>

  <!-- Right: Device Status Table -->
  <div class="col-span-5 glass-card p-8 h-[400px] flex flex-col">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-title-md font-bold">Status Perangkat</h3>
      <span class="material-symbols-outlined text-outline">more_vert</span>
    </div>
    <div class="flex-1 overflow-auto">
      <table class="w-full">
        <thead>
          <tr class="text-label-sm text-outline border-b border-surface-container-highest">
            <th class="text-left py-4 font-medium">Perangkat</th>
            <th class="text-left py-4 font-medium">Saat Ini</th>
            <th class="text-right py-4 font-medium">Status</th>
          </tr>
        </thead>
        <tbody class="text-label-md" id="v2-device-table-body">
          <tr><td colspan="3" class="py-6 text-center text-on-surface-variant">Memuat...</td></tr>
        </tbody>
      </table>
    </div>
    <a href="/alarm.php" class="w-full block text-center py-3 bg-surface-container-low text-primary text-label-md font-bold rounded-xl hover:bg-surface-container-highest transition-colors mt-4">
      Lihat Semua Status
    </a>
  </div>
</div>

</main>

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(v)) ? '-' : Number(v).toFixed(d);
function sv(d, k) { if (!d || !d[k]) return 0; const v = parseFloat(d[k].state); return isNaN(v) ? 0 : v; }
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }
function isOn(d, k) { return ss(d, k) === 'on'; }

function updateClock() {
  const h = new Date().getHours();
  set('v2-greeting', h < 5 ? 'Selamat dini hari' : h < 11 ? 'Selamat pagi' : h < 15 ? 'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam');
}
setInterval(updateClock, 60000); updateClock();

function setGauge(circleId, valId, pct, suffix) {
  const c = el(circleId), v = el(valId);
  const clamped = Math.max(0, Math.min(100, pct));
  if (c) c.setAttribute('stroke-dashoffset', (100 - clamped).toFixed(1));
  if (v) v.textContent = Math.round(clamped) + (suffix || '%');
}

const APEX = { chart:{background:'transparent',toolbar:{show:false},animations:{enabled:false},fontFamily:'Inter,sans-serif'}, grid:{show:false}, tooltip:{theme:'light'} };
const chartGen = new ApexCharts(el('chart-generation'), {
  series: [{name:'Solar (W)', data:[]}],
  chart: {...APEX.chart, type:'area', height:'100%'},
  colors: ['#1c50c7'],
  stroke: {curve:'smooth', width:4},
  fill: {type:'gradient', gradient:{shadeIntensity:1, opacityFrom:.3, opacityTo:0}},
  dataLabels: {enabled:false},
  xaxis: {type:'datetime', labels:{datetimeUTC:false, style:{colors:'#747685'}}, axisBorder:{show:false}, axisTicks:{show:false}},
  yaxis: {show:false},
  grid: {borderColor:'#e1e2eb', strokeDashArray:4, xaxis:{lines:{show:false}}},
  tooltip: {...APEX.tooltip, x:{format:'dd MMM HH:mm'}},
});
chartGen.render();

function renderStates(d) {
  if (!d || typeof d !== 'object') return;
  try {
    const cpf = d.computed?.power_flow;
    const cb  = d.computed?.battery_combined;
    const cps = d.computed?.power_source;
    const ccs = d.computed?.cost_savings;
    const csv = d.computed?.savings;

    const pvPow  = cpf ? cpf.pv_power : sv(d,'sensor.mppt_pv_power');
    const convEff= cpf ? cpf.conversion_efficiency : 0;
    const acLoad = sv(d,'sensor.smartpowermeter_power');
    const soc    = cb ? cb.soc_avg : sv(d,'sensor.huawei_soc');
    const sohMin = cb ? cb.soh_min : 0;
    const srcLbl = cps ? cps.label : '-';
    const autarky= csv ? csv.autarky_today : sv(d,'sensor.autarky_today');

    set('v2-pv-now', fmt(pvPow,0) + ' W');
    set('v2-flow-pv', fmt(pvPow,0) + ' W');
    set('v2-flow-load', fmt(acLoad,0) + ' W');
    set('v2-flow-batt', fmt(soc,0) + '%');
    set('v2-grid-status', srcLbl);

    const sysOnline = isOn(d, 'binary_sensor.voltara_system_online');
    set('v2-uptime', sysOnline ? 'Online' : 'Offline');
    const statusWord = el('v2-status-word');
    if (statusWord) { statusWord.textContent = sysOnline ? 'normal' : 'perlu diperiksa'; statusWord.className = sysOnline ? 'text-primary' : 'text-status-error'; }

    set('v2-energy-today', fmt(sv(d,'sensor.energy_today_kwh'),2) + ' kWh');
    set('v2-co2', fmt(csv ? csv.co2_avoided_month : sv(d,'sensor.co2_avoided_month'),1) + ' kg');
    set('v2-savings', 'Rp ' + new Intl.NumberFormat('id-ID').format(ccs ? ccs.savings_today : 0));
    set('v2-savings-sub', ccs ? `Autarki: ${fmt(ccs.autarky_pct,0)}%` : '-');

    // Gauges
    setGauge('gauge-autarky', 'gauge-autarky-val', autarky);
    set('gauge-autarky-sub', autarky >= 90 ? 'Mandiri penuh dari PLN' : autarky >= 50 ? 'Sebagian mandiri' : 'Masih banyak dari PLN');
    setGauge('gauge-inverter', 'gauge-inverter-val', convEff);
    set('gauge-inverter-sub', convEff >= 90 ? 'Di atas rata-rata' : convEff >= 75 ? 'Normal' : 'Perlu diperiksa');
    setGauge('gauge-battery-health', 'gauge-battery-health-val', sohMin);
    set('gauge-battery-health-sub', sohMin >= 90 ? 'Sangat sehat' : sohMin >= 70 ? 'Sehat' : 'Perlu diperiksa');

    // Flow dots: nyala kalau memang ada aliran
    const battPow = cb ? cb.total_power : 0;
    const dotBatt = el('dot-roof-battery'), dotHouse = el('dot-roof-house');
    if (dotBatt) dotBatt.style.opacity = (battPow > 10) ? '1' : '0';
    if (dotHouse) dotHouse.style.opacity = (pvPow > 20) ? '1' : '0';

    // Shield / alarm status
    const hAlarm = ss(d,'sensor.huawei_has_alarm') === 'True' || ss(d,'sensor.huawei_has_alarm') === 'on';
    const jAlarm = ss(d,'sensor.jikong_has_alarm') === 'True' || ss(d,'sensor.jikong_has_alarm') === 'on';
    const anyAlarm = isOn(d, 'binary_sensor.smartpowermeter_any_alarm_active') || hAlarm || jAlarm;
    set('v2-shield-text', anyAlarm ? 'Ada alarm aktif - cek sekarang' : 'Semua Sistem Aman');
    const shieldIcon = el('v2-shield-icon');
    if (shieldIcon) shieldIcon.className = 'material-symbols-outlined ' + (anyAlarm ? 'text-status-error' : 'text-primary');
    const alertDot = el('v2-alert-dot');
    if (alertDot) alertDot.style.display = anyAlarm ? 'block' : 'none';

    // Device table
    const mpptOn = isOn(d, 'binary_sensor.smartpowermeter_mppt_online') || sysOnline;
    const devices = [
      {name:'Solar Array', current: fmt(pvPow,0)+' W', ok: mpptOn},
      {name:'Inverter Hybrid', current: fmt(acLoad,0)+' W', ok: sysOnline},
      {name:'Baterai Huawei', current: fmt(sv(d,'sensor.huawei_soc'),0)+'%', ok: !hAlarm},
      {name:'Baterai Jikong', current: fmt(sv(d,'sensor.jikong_soc'),0)+'%', ok: !jAlarm},
      {name:'Grid Meter (PZEM)', current: fmt(sv(d,'sensor.smartpowermeter_voltage'),0)+'V', ok: sysOnline},
      {name:'ESP32 Controller', current: ss(d,'sensor.smartpowermeter_wifi_signal')+' dBm', ok: sysOnline},
    ];
    const tbody = el('v2-device-table-body');
    if (tbody) {
      tbody.innerHTML = devices.map(dev => `
        <tr class="border-b border-surface-container-highest hover:bg-surface-container-low/50 transition-colors">
          <td class="py-4 font-bold">${dev.name}</td>
          <td class="py-4">${dev.current}</td>
          <td class="py-4 text-right">
            <span class="px-2 py-1 ${dev.ok ? 'bg-status-success/10 text-status-success' : 'bg-error-container text-error'} rounded text-[10px] font-bold uppercase">${dev.ok ? 'Active' : 'Offline'}</span>
          </td>
        </tr>`).join('');
    }

  } catch (err) { console.error('renderStates error:', err); }
}

async function fetchCo2Promo() {
  try {
    const res = await fetch('/proxy.php?type=ha_states');
    if (!res.ok) return;
    const d = await res.json();
    const co2 = d.computed?.savings?.co2_avoided_month || 0;
    set('v2-co2-promo', `Anda sudah menghindari ${co2.toFixed(0)} kg emisi CO2 bulan ini.`);
  } catch (e) {}
}

let genRange = '24h';
async function fetchGenerationChart() {
  try {
    const res = await fetch('/proxy.php?type=influx_mppt_history&range=' + genRange);
    if (!res.ok) return;
    const raw = await res.json();
    const data = Array.isArray(raw.pv_in) ? raw.pv_in.map(r => [new Date(r.time).getTime(), r.value]) : [];
    chartGen.updateSeries([{name:'Solar (W)', data}]);
  } catch (e) { console.warn('Chart fetch error:', e); }
}
document.getElementById('v2-range-select').addEventListener('change', (e) => {
  genRange = e.target.value;
  fetchGenerationChart();
});

// LIVE DATA: SSE + fallback polling
let pollTimer = null;
let liveEs = null;
let lastLiveTs = Date.now();
async function fetchStatesOnce() {
  try {
    const res = await fetch('/proxy.php?type=ha_states');
    if (res.status === 401) { location.href = '/login.php'; return; }
    if (!res.ok) throw new Error();
    const d = await res.json();
    if (d && !d.error) renderStates(d);
  } catch (e) { console.warn('fetch error', e); }
}
function startPollingFallback() { if (pollTimer) return; fetchStatesOnce(); pollTimer = setInterval(fetchStatesOnce, 15000); }
function stopPollingFallback() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }
function connectSSE() {
  if (!window.EventSource) { startPollingFallback(); return; }
  if (liveEs) { try { liveEs.close(); } catch(e) {} }
  liveEs = new EventSource('/proxy.php?type=sse');
  let sseWorking = false;
  let fallbackTimer = setTimeout(startPollingFallback, 6000);
  liveEs.onmessage = (ev) => {
    sseWorking = true; lastLiveTs = Date.now();
    clearTimeout(fallbackTimer); stopPollingFallback();
    try { const data = JSON.parse(ev.data); if (data && !data.error) renderStates(data); } catch (e) {}
  };
  liveEs.addEventListener('heartbeat', () => { lastLiveTs = Date.now(); });
  liveEs.addEventListener('reconnect', () => {});
  liveEs.onerror = () => {
    if (!sseWorking) {
      fetch('/proxy.php?type=ha_states').then(r => { if (r.status === 401) location.href = '/login.php'; }).catch(() => {});
      startPollingFallback();
    }
  };
}
function startLive() {
  connectSSE();
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
fetchCo2Promo();
fetchGenerationChart();
setInterval(fetchGenerationChart, 300000);
</script>
</body>
</html>