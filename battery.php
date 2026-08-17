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

$page_title  = 'Baterai - Voltara EMS';
$active      = 'battery';
$tb_h1       = 'Monitor Baterai - Gabungan (2 Bank)';
$tb_sub_init = 'Menghubungkan ke BMS...';
$tb_pills    = [
    ['id' => 'combo-pill', 'class' => 'pill ok', 'text' => 'Standby'],
];

include __DIR__ . '/partials/head.php';
$tb_h1 = icon('battery', 20) . ' ' . $tb_h1;
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<!-- HERO GABUNGAN -->
<div class="combo-hero">
  <div class="combo-stat">
    <div class="cs-lbl">SOC Rata-rata</div>
    <div><span class="cs-val" id="c-soc">-</span><span class="cs-unit"> %</span></div>
    <div class="cs-sub" id="c-soc-sub">2 bank paralel</div>
  </div>
  <div class="combo-stat">
    <div class="cs-lbl">Total Daya</div>
    <div><span class="cs-val" id="c-pwr">-</span><span class="cs-unit"> W</span></div>
    <div class="cs-sub" id="c-pwr-sub">Huawei + Jikong</div>
  </div>
  <div class="combo-stat">
    <div class="cs-lbl">Total Energi Tersisa</div>
    <div><span class="cs-val" id="c-rem">-</span><span class="cs-unit"> kWh</span></div>
    <div class="cs-sub" id="c-rem-sub">-</div>
  </div>
  <div class="combo-stat">
    <div class="cs-lbl">Total Kapasitas</div>
    <div><span class="cs-val" id="c-cap">-</span><span class="cs-unit"> kWh</span></div>
    <div class="cs-sub">Design gabungan</div>
  </div>
</div>

<div class="content">

  <!-- DUA KARTU BANK -->
  <div class="bank-grid">

    <div class="bank-card">
      <div class="bank-hd">
        <div><div class="bank-name">Huawei ESM-48100U2</div><div class="bank-spec">48V / 100Ah LiFePO4</div></div>
        <a class="bank-detail" href="huawei.php">Detail <?= icon('chevron-right', 12) ?></a>
      </div>
      <div class="bank-bd">
        <div class="bank-soc-row">
          <div class="bank-ring">
            <svg viewBox="0 0 96 96" width="96" height="96">
              <circle cx="48" cy="48" r="40" fill="none" stroke="var(--rim)" stroke-width="8"/>
              <circle id="h-ring" cx="48" cy="48" r="40" fill="none" stroke="var(--mint)" stroke-width="8"
                stroke-linecap="round" stroke-dasharray="251.3" stroke-dashoffset="251.3" style="transition:stroke-dashoffset 1s ease,stroke .3s"/>
            </svg>
            <div class="rc"><b id="h-soc">-</b><small>SOC</small></div>
          </div>
          <div class="bank-pwr">
            <div class="bp-val" id="h-pwr">-</div>
            <div class="bp-lbl" id="h-pwr-lbl">Daya</div>
            <div class="bank-status" style="margin-top:10px">
              <span class="bs-l">Status</span>
              <span class="pill ok" id="h-pill"> -</span>
            </div>
          </div>
        </div>
        <div class="bank-metrics">
          <div class="bm"><div class="bm-lbl">Tegangan</div><div class="bm-val" id="h-vbat">-</div><div class="bm-unit">V</div></div>
          <div class="bm"><div class="bm-lbl">Suhu Maks</div><div class="bm-val" id="h-tmax">-</div><div class="bm-unit">C</div></div>
          <div class="bm"><div class="bm-lbl">Delta Sel</div><div class="bm-val" id="h-delta">-</div><div class="bm-unit">mV</div></div>
          <div class="bm"><div class="bm-lbl">Health</div><div class="bm-val" id="h-soh">-</div><div class="bm-unit">%</div></div>
          <div class="bm"><div class="bm-lbl">Energi</div><div class="bm-val" id="h-rem">-</div><div class="bm-unit">kWh</div></div>
          <div class="bm"><div class="bm-lbl">Mode</div><div class="bm-val" id="h-mode" style="font-size:12px">-</div><div class="bm-unit">&nbsp;</div></div>
        </div>
      </div>
    </div>

    <div class="bank-card">
      <div class="bank-hd">
        <div><div class="bank-name">Jikong JK-BMS</div><div class="bank-spec">15S LiFePO4</div></div>
        <a class="bank-detail" href="jikong.php">Detail <?= icon('chevron-right', 12) ?></a>
      </div>
      <div class="bank-bd">
        <div class="bank-soc-row">
          <div class="bank-ring">
            <svg viewBox="0 0 96 96" width="96" height="96">
              <circle cx="48" cy="48" r="40" fill="none" stroke="var(--rim)" stroke-width="8"/>
              <circle id="j-ring" cx="48" cy="48" r="40" fill="none" stroke="var(--sky)" stroke-width="8"
                stroke-linecap="round" stroke-dasharray="251.3" stroke-dashoffset="251.3" style="transition:stroke-dashoffset 1s ease,stroke .3s"/>
            </svg>
            <div class="rc"><b id="j-soc">-</b><small>SOC</small></div>
          </div>
          <div class="bank-pwr">
            <div class="bp-val" id="j-pwr">-</div>
            <div class="bp-lbl" id="j-pwr-lbl">Daya</div>
            <div class="bank-status" style="margin-top:10px">
              <span class="bs-l">Status</span>
              <span class="pill ok" id="j-pill"> -</span>
            </div>
          </div>
        </div>
        <div class="bank-metrics">
          <div class="bm"><div class="bm-lbl">Tegangan</div><div class="bm-val" id="j-vbat">-</div><div class="bm-unit">V</div></div>
          <div class="bm"><div class="bm-lbl">Suhu Maks</div><div class="bm-val" id="j-tmax">-</div><div class="bm-unit">C</div></div>
          <div class="bm"><div class="bm-lbl">Delta Sel</div><div class="bm-val" id="j-delta">-</div><div class="bm-unit">mV</div></div>
          <div class="bm"><div class="bm-lbl">Health</div><div class="bm-val" id="j-soh">-</div><div class="bm-unit">%</div></div>
          <div class="bm"><div class="bm-lbl">Energi</div><div class="bm-val" id="j-rem">-</div><div class="bm-unit">kWh</div></div>
          <div class="bm"><div class="bm-lbl">Mode</div><div class="bm-val" id="j-mode" style="font-size:12px">-</div><div class="bm-unit">&nbsp;</div></div>
        </div>
      </div>
    </div>
  </div>

  <!-- CHART SOC GABUNGAN -->
  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Perbandingan SOC - 24 Jam</div>
           <div class="card-sub">Kedua bank baterai</div></div>
      <div class="range-sel" id="range-sel">
        <button class="rb on" data-r="24h">24 Jam</button>
        <button class="rb" data-r="7d">7 Hari</button>
        <button class="rb" data-r="30d">30 Hari</button>
      </div>
    </div>
    <div class="card-bd"><div id="chart-combo" style="min-height:280px"></div></div>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v ?? '-'; };
const fmt = (v, d=1) => (v === null || v === undefined || isNaN(Number(v))) ? '-' : Number(v).toFixed(d);
function sv(d, k) { if (!d || !d[k]) return 0; const v = parseFloat(d[k].state); return isNaN(v) ? 0 : v; }
function ss(d, k) { return (d && d[k]) ? (d[k].state ?? '-') : '-'; }

setInterval(() => set('clock', new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'})), 1000);

function ringSet(id, soc){
  const c = 2*Math.PI*40; // 251.3
  const r = el(id); if(!r) return;
  r.style.strokeDashoffset = c - (soc/100)*c;
  r.style.stroke = soc>=60?'var(--mint)':soc>=30?'var(--amber)':'var(--rose)';
}

function bankRender(pref, d, e){
  const soc = sv(d, e.soc), pwr = sv(d, e.pwr), vbat = sv(d, e.vbat);
  const tmax= sv(d, e.tmax), delta= sv(d, e.delta), soh = sv(d, e.soh);
  const rem = sv(d, e.rem), mode = ss(d, e.mode), alarm = ss(d, e.alarm);
  ringSet(pref+'-ring', soc);
  set(pref+'-soc', Math.round(soc)+'%');
  const pv = el(pref+'-pwr');
  if(pv){ pv.textContent = fmt(Math.abs(pwr),0)+' W';
    pv.style.color = pwr>20?'var(--mint2)':pwr<-20?'#fb7185':'var(--body)'; }
  set(pref+'-pwr-lbl', pwr>20?'Charging \u2191':pwr<-20?'Discharging \u2193':'Idle');
  set(pref+'-vbat', fmt(vbat,2));
  set(pref+'-tmax', fmt(tmax,1));
  set(pref+'-delta', fmt(delta,0));
  set(pref+'-soh', fmt(soh,0));
  set(pref+'-rem', fmt(rem,2));
  set(pref+'-mode', mode);
  const pill = el(pref+'-pill');
  if(pill){ const a = alarm==='True'||alarm==='on';
    pill.textContent = a?'\u26a0 Alarm':pwr>20?'\u2191 Charging':pwr<-20?'\u2193 Discharging':'\u25cf Standby';
    pill.className = 'pill '+(a?'warn':pwr>20?'ok':pwr<-20?'err':'ok'); }
  return {soc, pwr, rem, soh, active: soc>0};
}

const H = {soc:'sensor.huawei_soc',pwr:'sensor.huawei_power',vbat:'sensor.huawei_battery_voltage',
  tmax:'sensor.huawei_cell_temp_max',delta:'sensor.huawei_cell_delta_mv',soh:'sensor.huawei_health_score',
  rem:'sensor.huawei_kwh_remaining',mode:'sensor.huawei_state_mode',alarm:'sensor.huawei_has_alarm',
  kdes:'sensor.huawei_kwh_design_full'};
const J = {soc:'sensor.jikong_soc',pwr:'sensor.jikong_power',vbat:'sensor.jikong_battery_voltage',
  tmax:'sensor.jikong_cell_temp_max',delta:'sensor.jikong_cell_delta_mv',soh:'sensor.jikong_health_score',
  rem:'sensor.jikong_kwh_remaining',mode:'sensor.jikong_state_mode',alarm:'sensor.jikong_has_alarm',
  kdes:'sensor.jikong_kwh_design_full'};

function renderStates(d){
  if(!d || typeof d!=='object') return;
  try{
    set('tb-sub','Diperbarui '+new Date().toLocaleTimeString('id-ID'));
    const h = bankRender('h', d, H);
    const j = bankRender('j', d, J);

    // Gabungan: pakai hasil backend (lib/metrics.php) kalau tersedia -
    // supaya identik dengan angka yang nanti dipakai cron alert (tahap 2).
    const cb = d.computed?.battery_combined;
    const banksActive = cb ? cb.banks_active : [h,j].filter(b=>b.active).length;
    const socAvg = cb ? cb.soc_avg : (banksActive ? ([h,j].filter(b=>b.active).reduce((a,b)=>a+b.soc,0)/banksActive) : 0);
    const totPwr = cb ? cb.total_power : (h.pwr + j.pwr);
    const totRem = cb ? cb.total_remaining_kwh : (h.rem + j.rem);
    const totCap = cb ? cb.total_capacity_kwh : (sv(d,H.kdes) + sv(d,J.kdes));
    const sohMin = cb ? cb.soh_min : (banksActive ? Math.min(...[h,j].filter(b=>b.active).map(b=>b.soh)) : 0);

    set('c-soc', fmt(socAvg,0));
    set('c-soc-sub', banksActive===2?'2 bank aktif':banksActive===1?'1 bank aktif':'offline');
    set('c-pwr', fmt(Math.abs(totPwr),0));
    set('c-pwr-sub', totPwr>20?'\u2191 Net charging':totPwr<-20?'\u2193 Net discharging':'Idle');
    set('c-rem', fmt(totRem,2));
    set('c-rem-sub', totCap>0? '\u2248 '+fmt(totRem/totCap*100,0)+'% dari total':'\u2014');
    set('c-cap', fmt(totCap,2));

    const cp = el('combo-pill');
    if(cp){ cp.textContent = totPwr>20?'\u2191 Charging':totPwr<-20?'\u2193 Discharging':'\u25cf Standby';
      cp.className='pill '+(totPwr>20?'ok':totPwr<-20?'err':'ok'); }

    // sidebar
    const st=el('sys-txt'), sd=el('sys-dot'), sp=el('sys-pill');
    const ok = banksActive>0;
    if(st){ st.textContent = ok?(sohMin>80?'2 Bank Sehat':'Perhatian'):'Offline'; st.className='sys-txt'+(ok?'':' off'); }
    if(sd) sd.className='dot'+(ok?'':' off');
    if(sp) sp.className='sys-pill'+(ok?'':' off');
  }catch(err){ console.error('render error:',err); }
}

// Chart SOC gabungan
const APEX_DARK={chart:{background:'transparent',toolbar:{show:false},animations:{enabled:false},fontFamily:'Inter,sans-serif'},grid:{borderColor:'#e3e7ef',strokeDashArray:4},tooltip:{theme:'light'}};
const chartCombo = new ApexCharts(el('chart-combo'), {
  series:[{name:'Huawei SOC (%)',data:[]},{name:'Jikong SOC (%)',data:[]}],
  chart:{...APEX_DARK.chart,type:'area',height:280},
  colors:['#10b981','#38bdf8'],
  stroke:{curve:'smooth',width:2.5},
  fill:{type:'gradient',gradient:{shadeIntensity:1,opacityFrom:.2,opacityTo:.02}},
  dataLabels:{enabled:false},
  xaxis:{type:'datetime',labels:{datetimeUTC:false,style:{colors:'#98a0b3'},datetimeFormatter:{hour:'HH:mm'}},axisBorder:{show:false},axisTicks:{show:false}},
  yaxis:{min:0,max:100,title:{text:'SOC (%)',style:{color:'#5b6178'}},labels:{style:{colors:'#98a0b3'},formatter:v=>Math.round(v)}},
  grid:APEX_DARK.grid,
  legend:{position:'top',horizontalAlign:'right',labels:{colors:'#5b6178'}},
  tooltip:{...APEX_DARK.tooltip,shared:true,x:{format:'HH:mm dd MMM'}},
});
chartCombo.render();

let battRange='24h';
async function fetchCharts(){
  try{
    const [rH,rJ]=await Promise.allSettled([
      fetch('proxy.php?type=influx_battery_history&range='+battRange),
      fetch('proxy.php?type=influx_battery_jikong_history&range='+battRange),
    ]);
    const toXY=arr=>Array.isArray(arr)?arr.map(r=>[new Date(r.time).getTime(),r.value]):[];
    let hs=[], js=[];
    if(rH.status==='fulfilled'&&rH.value.ok){ const raw=await rH.value.json(); hs=toXY(raw.soc); }
    if(rJ.status==='fulfilled'&&rJ.value.ok){ const raw=await rJ.value.json(); js=toXY(raw.soc); }
    chartCombo.updateSeries([{name:'Huawei SOC (%)',data:hs},{name:'Jikong SOC (%)',data:js}]);
  }catch(e){ console.warn('Chart error:',e); }
}

async function fetchStatesOnce(){
  try{
    const res=await fetch('proxy.php?type=ha_states');
    if(res.status===401){ location.href='login.php'; return; }
    if(!res.ok) throw new Error();
    const d=await res.json();
    if(d && !d.error) renderStates(d);
  }catch(e){ set('tb-sub','Gagal memuat - '+new Date().toLocaleTimeString('id-ID')); }
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
document.getElementById('range-sel').addEventListener('click',e=>{
  const b=e.target.closest('.rb'); if(!b) return;
  battRange=b.dataset.r;
  document.querySelectorAll('#range-sel .rb').forEach(x=>x.classList.toggle('on',x===b));
  fetchCharts();
});
fetchCharts();
setInterval(fetchCharts,300000);
</script>
</body>
</html>