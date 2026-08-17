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

$page_title  = 'Historis - Voltara EMS';
$active      = 'historis';
$tb_h1       = 'Data Historis';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="content" style="padding-top:20px">

  <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
    <div class="range-sel" id="range-sel">
      <button class="rb" data-r="24h">24 Jam</button>
      <button class="rb" data-r="7d">7 Hari</button>
      <button class="rb on" data-r="30d">30 Hari</button>
    </div>
  </div>

  <!-- RINGKASAN PERIODE -->
  <div class="kpi-row">
    <div class="kpi amber">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(245,158,11,.1);color:var(--amber2)"><?= icon('sun', 18) ?></div></div>
      <div class="kpi-lbl">Total Solar Dihasilkan</div>
      <div><span class="kpi-val" id="rk-solar">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub" id="rk-solar-sub">Puncak: - W</div>
    </div>
    <div class="kpi sky">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(2,132,199,.1);color:var(--sky)"><?= icon('bolt', 18) ?></div></div>
      <div class="kpi-lbl">Total Konsumsi</div>
      <div><span class="kpi-val" id="rk-load">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub" id="rk-load-sub">Puncak: - W</div>
    </div>
    <div class="kpi rose">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(244,63,94,.1);color:var(--rose)"><?= icon('plug', 18) ?></div></div>
      <div class="kpi-lbl">Total dari Grid</div>
      <div><span class="kpi-val" id="rk-grid">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub" id="rk-grid-sub">Rata-rata autarki: -%</div>
    </div>
    <div class="kpi mint">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(16,185,129,.1);color:var(--mint2)"><?= icon('battery', 18) ?></div></div>
      <div class="kpi-lbl">Rentang SOC Baterai</div>
      <div><span class="kpi-val" id="rk-soc" style="font-size:20px">-</span></div>
      <div class="kpi-sub" id="rk-soc-sub">Min - Maks</div>
    </div>
  </div>

  <!-- PV, BEBAN & GRID -->
  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">PV, Beban &amp; Grid</div><div class="card-sub">Zona merah = periode grid dipakai</div></div>
    </div>
    <div class="card-bd">
      <div id="chart-power" style="min-height:260px"></div>
      <div class="hstats">
        <div><div class="hstat-lbl">PV Rata-rata</div><div class="hstat-val" id="st-pv-avg">- <small>W</small></div></div>
        <div><div class="hstat-lbl">PV Puncak</div><div class="hstat-val" id="st-pv-max">- <small>W</small></div></div>
        <div><div class="hstat-lbl">Beban Rata-rata</div><div class="hstat-val" id="st-load-avg">- <small>W</small></div></div>
        <div><div class="hstat-lbl">Beban Puncak</div><div class="hstat-val" id="st-load-max">- <small>W</small></div></div>
      </div>
    </div>
  </div>

  <!-- KESEHATAN BATERAI -->
  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Kesehatan Baterai (2 Bank)</div><div class="card-sub">SOC, daya, suhu, dan keseimbangan sel dari waktu ke waktu</div></div>
    </div>
    <div class="card-bd">
      <div class="health-grid" style="grid-template-columns:1fr">
        <div>
          <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:600;margin-bottom:6px">SOC Gabungan</div>
          <div id="chart-soc" style="min-height:200px"></div>
        </div>
      </div>
    </div>
    <div class="card-bd" style="border-top:1px solid var(--rim);display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:600;margin-bottom:6px">Suhu Sel Maksimum</div>
        <div id="chart-batt-temp" style="min-height:180px"></div>
      </div>
      <div>
        <div style="font-size:11px;color:var(--muted);text-transform:uppercase;font-weight:600;margin-bottom:6px">Delta Sel (Imbalance)</div>
        <div id="chart-batt-delta" style="min-height:180px"></div>
      </div>
    </div>
  </div>

  <!-- KUALITAS DAYA -->
  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Kualitas Daya AC</div><div class="card-sub">Tegangan &amp; frekuensi jaringan</div></div>
    </div>
    <div class="card-bd">
      <div id="chart-quality" style="min-height:220px"></div>
      <div class="hstats">
        <div><div class="hstat-lbl">Tegangan Rata-rata</div><div class="hstat-val" id="st-v-avg">- <small>V</small></div></div>
        <div><div class="hstat-lbl">Rentang Tegangan</div><div class="hstat-val" id="st-v-range">- <small>V</small></div></div>
        <div><div class="hstat-lbl">Frekuensi Rata-rata</div><div class="hstat-val" id="st-hz-avg">- <small>Hz</small></div></div>
        <div><div class="hstat-lbl">Rentang Frekuensi</div><div class="hstat-val" id="st-hz-range">- <small>Hz</small></div></div>
      </div>
    </div>
  </div>

  <!-- LINK KE ANALISIS LAIN -->
  <div class="grid2">
    <a href="biaya.php" class="card card-hover" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:16px;padding:20px;margin-bottom:0">
      <div class="kpi-ico" style="background:rgba(244,63,94,.1);color:var(--rose);width:44px;height:44px"><?= icon('coin', 22) ?></div>
      <div style="flex:1"><div style="font-weight:700">Analisis Biaya &amp; Hemat</div><div style="font-size:12px;color:var(--dim)">Rincian harian biaya PLN vs penghematan</div></div>
      <?= icon('chevron-right', 20) ?>
    </a>
    <a href="lingkungan.php" class="card card-hover" style="text-decoration:none;color:inherit;display:flex;align-items:center;gap:16px;padding:20px;margin-bottom:0">
      <div class="kpi-ico" style="background:rgba(16,185,129,.1);color:var(--mint2);width:44px;height:44px"><?= icon('leaf', 22) ?></div>
      <div style="flex:1"><div style="font-weight:700">Dampak Lingkungan</div><div style="font-size:12px;color:var(--dim)">CO2 dihindari, setara pohon &amp; jarak mobil</div></div>
      <?= icon('chevron-right', 20) ?>
    </a>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };

const APEX_COMMON = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

// ── Chart 1: PV, Beban, Grid (zona bayangan) ──
const chartPower = new ApexCharts(el('chart-power'), {
  series: [{name:'PV Power (W)', type:'area', data:[]}, {name:'Beban AC (W)', type:'line', data:[]}],
  chart: {...APEX_COMMON.chart, type:'line', height:260},
  colors: ['#f59e0b', '#0284c7'],
  stroke: {curve:'smooth', width:[2, 2.5]},
  fill: {type:['gradient','solid'], gradient:{shadeIntensity:1, opacityFrom:.25, opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: { title:{text:'Watt', style:{color:'#98a0b3'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:0 },
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {...APEX_COMMON.tooltip, shared:true, x:{format:'dd MMM HH:mm'}},
});
chartPower.render();

// ── Chart 2: SOC gabungan ──
const chartSoc = new ApexCharts(el('chart-soc'), {
  series: [{name:'Huawei SOC (%)', data:[]}, {name:'Jikong SOC (%)', data:[]}],
  chart: {...APEX_COMMON.chart, type:'area', height:200},
  colors: ['#10b981', '#0284c7'],
  stroke: {curve:'smooth', width:2.5},
  fill: {type:'gradient', gradient:{shadeIntensity:1, opacityFrom:.2, opacityTo:.02}},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: { min:0, max:100, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)} },
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {...APEX_COMMON.tooltip, shared:true, x:{format:'dd MMM HH:mm'}},
});
chartSoc.render();

// ── Chart 3: Suhu sel (2 bank) ──
const chartBattTemp = new ApexCharts(el('chart-batt-temp'), {
  series: [{name:'Huawei (C)', data:[]}, {name:'Jikong (C)', data:[]}],
  chart: {...APEX_COMMON.chart, type:'line', height:180},
  colors: ['#f59e0b', '#e11d48'],
  stroke: {curve:'smooth', width:2},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3', fontSize:'10px'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: { labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)} },
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, fontSize:'11px'},
  tooltip: {...APEX_COMMON.tooltip, shared:true},
});
chartBattTemp.render();

// ── Chart 4: Delta sel / imbalance (2 bank) ──
const chartBattDelta = new ApexCharts(el('chart-batt-delta'), {
  series: [{name:'Huawei (mV)', data:[]}, {name:'Jikong (mV)', data:[]}],
  chart: {...APEX_COMMON.chart, type:'line', height:180},
  colors: ['#6366f1', '#059669'],
  stroke: {curve:'smooth', width:2},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3', fontSize:'10px'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: { labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)} },
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}, fontSize:'11px'},
  tooltip: {...APEX_COMMON.tooltip, shared:true},
});
chartBattDelta.render();

// ── Chart 5: Tegangan & Frekuensi ──
const chartQuality = new ApexCharts(el('chart-quality'), {
  series: [{name:'Tegangan (V)', data:[]}, {name:'Frekuensi (Hz)', data:[]}],
  chart: {...APEX_COMMON.chart, type:'line', height:220},
  colors: ['#10b981', '#f59e0b'],
  stroke: {curve:'smooth', width:2},
  dataLabels: {enabled:false},
  xaxis: { type:'datetime', labels:{datetimeUTC:false, style:{colors:'#98a0b3'}}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: [
    {seriesName:'Tegangan (V)', title:{text:'Volt', style:{color:'#059669'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>Math.round(v)}, min:180, max:250},
    {seriesName:'Frekuensi (Hz)', opposite:true, title:{text:'Hz', style:{color:'#d97706'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}, min:48, max:52},
  ],
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {...APEX_COMMON.tooltip, shared:true, x:{format:'dd MMM HH:mm'}},
});
chartQuality.render();

function computeGridZones(points, threshold = 30) {
  const zones = [];
  let start = null;
  for (let i = 0; i < points.length; i++) {
    const t = new Date(points[i].time).getTime();
    if (points[i].grid > threshold) { if (start === null) start = t; }
    else if (start !== null) { zones.push({ x: start, x2: t }); start = null; }
  }
  if (start !== null) zones.push({ x: start, x2: new Date(points[points.length-1].time).getTime() });
  return zones;
}

function statsOf(arr) {
  const vals = arr.map(p => p[1]).filter(n => !isNaN(n));
  if (!vals.length) return {avg:0, max:0, min:0};
  return { avg: vals.reduce((s,n)=>s+n,0)/vals.length, max: Math.max(...vals), min: Math.min(...vals) };
}

let currentRange = '30d';

async function fetchHistoris() {
  try {
    const [resFlow, resHBatt, resJBatt, resQuality] = await Promise.allSettled([
      fetch('proxy.php?type=influx_flow_history&range=' + currentRange),
      fetch('proxy.php?type=influx_battery_history&range=' + currentRange),
      fetch('proxy.php?type=influx_battery_jikong_history&range=' + currentRange),
      fetch('proxy.php?type=influx_history&range=' + currentRange),
    ]);
    const toXY = arr => Array.isArray(arr) ? arr.map(r => [new Date(r.time).getTime(), r.value]) : [];

    // PV / Beban / Grid
    let pvData = [], loadData = [], gridZones = [];
    let totalSolarKwh = 0, totalLoadKwh = 0, totalGridKwh = 0;
    if (resFlow.status === 'fulfilled' && resFlow.value.ok) {
      const raw = await resFlow.value.json();
      if (Array.isArray(raw.points) && raw.points.length) {
        pvData = raw.points.map(p => [new Date(p.time).getTime(), p.pv]);
        loadData = raw.points.map(p => [new Date(p.time).getTime(), p.load]);
        gridZones = computeGridZones(raw.points);
        totalSolarKwh = raw.day_production_kwh || 0;
        totalGridKwh = raw.grid_kwh || 0;
      }
    }
    chartPower.updateSeries([{name:'PV Power (W)', data:pvData}, {name:'Beban AC (W)', data:loadData}]);
    chartPower.updateOptions({ annotations: { xaxis: gridZones.map(z => ({
      x:z.x, x2:z.x2, fillColor:'#e11d48', opacity:0.12,
      label:{text:'Grid', style:{color:'#e11d48', background:'transparent', fontSize:'10px', fontWeight:700}}
    })) } }, false, false);

    const pvStats = statsOf(pvData), loadStats = statsOf(loadData);
    set('st-pv-avg', Math.round(pvStats.avg)); set('st-pv-max', Math.round(pvStats.max));
    set('st-load-avg', Math.round(loadStats.avg)); set('st-load-max', Math.round(loadStats.max));
    set('rk-solar', totalSolarKwh.toFixed(1)); set('rk-solar-sub', `Puncak: ${Math.round(pvStats.max)} W`);
    set('rk-load-sub', `Puncak: ${Math.round(loadStats.max)} W`);
    set('rk-grid', totalGridKwh.toFixed(1));

    // Baterai: SOC, Suhu, Delta - satu response per bank berisi semuanya
    let hSoc = [], jSoc = [], hTemp = [], jTemp = [], hDelta = [], jDelta = [];
    if (resHBatt.status === 'fulfilled' && resHBatt.value.ok) {
      const raw = await resHBatt.value.json();
      hSoc = toXY(raw.soc); hTemp = toXY(raw.temp); hDelta = toXY(raw.delta);
    }
    if (resJBatt.status === 'fulfilled' && resJBatt.value.ok) {
      const raw = await resJBatt.value.json();
      jSoc = toXY(raw.soc); jTemp = toXY(raw.temp); jDelta = toXY(raw.delta);
    }
    chartSoc.updateSeries([{name:'Huawei SOC (%)', data:hSoc}, {name:'Jikong SOC (%)', data:jSoc}]);
    chartBattTemp.updateSeries([{name:'Huawei (C)', data:hTemp}, {name:'Jikong (C)', data:jTemp}]);
    chartBattDelta.updateSeries([{name:'Huawei (mV)', data:hDelta}, {name:'Jikong (mV)', data:jDelta}]);

    const allSoc = [...hSoc, ...jSoc].map(p => p[1]).filter(n => !isNaN(n));
    if (allSoc.length) { set('rk-soc', `${Math.round(Math.min(...allSoc))}% - ${Math.round(Math.max(...allSoc))}%`); }

    // Kualitas daya
    if (resQuality.status === 'fulfilled' && resQuality.value.ok) {
      const raw = await resQuality.value.json();
      const vData = toXY(raw.voltage), hzData = toXY(raw.frequency);
      chartQuality.updateSeries([{name:'Tegangan (V)', data:vData}, {name:'Frekuensi (Hz)', data:hzData}]);
      const vStats = statsOf(vData), hzStats = statsOf(hzData);
      set('st-v-avg', vStats.avg.toFixed(1));
      set('st-v-range', `${Math.round(vStats.min)}-${Math.round(vStats.max)}`);
      set('st-hz-avg', hzStats.avg.toFixed(2));
      set('st-hz-range', `${hzStats.min.toFixed(1)}-${hzStats.max.toFixed(1)}`);
    }

    // Autarki rata-rata dari cost_history (proxy)
    try {
      const resCost = await fetch('proxy.php?type=influx_cost_history&range=' + (currentRange === '24h' ? '7d' : currentRange));
      if (resCost.ok) {
        const cd = await resCost.json();
        if (Array.isArray(cd.days) && cd.days.length) {
          const avgAutarky = cd.days.reduce((s,d)=>s+d.autarky_pct,0) / cd.days.length;
          set('rk-grid-sub', `Rata-rata autarki: ${avgAutarky.toFixed(0)}%`);
          const totalLoad = cd.days.reduce((s,d)=>s+d.energy_kwh,0);
          set('rk-load', totalLoad.toFixed(1));
        }
      }
    } catch(e) {}

  } catch(e) { console.warn('Historis fetch error:', e); }
}

document.getElementById('range-sel').addEventListener('click', e => {
  const b = e.target.closest('.rb'); if (!b) return;
  currentRange = b.dataset.r;
  document.querySelectorAll('#range-sel .rb').forEach(x => x.classList.toggle('on', x === b));
  fetchHistoris();
});

fetchHistoris();
setInterval(fetchHistoris, 300000);
</script>
</body>
</html>