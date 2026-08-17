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

$page_title  = 'Lingkungan - Voltara EMS';
$active      = 'lingkungan';
$tb_h1       = 'Dampak Lingkungan';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="content" style="padding-top:20px">

  <div class="kpi-row">
    <div class="kpi mint">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(16,185,129,.1);color:var(--mint2)"><?= icon('leaf', 18) ?></div></div>
      <div class="kpi-lbl">Total CO2 Dihindari</div>
      <div><span class="kpi-val" id="k-total-co2">-</span><span class="kpi-unit">kg</span></div>
      <div class="kpi-sub" id="k-total-co2-sub">- hari terakhir</div>
    </div>
    <div class="kpi amber">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(245,158,11,.1);color:var(--amber2)"><?= icon('sun', 18) ?></div></div>
      <div class="kpi-lbl">Total Energi Solar</div>
      <div><span class="kpi-val" id="k-total-solar">-</span><span class="kpi-unit">kWh</span></div>
      <div class="kpi-sub">Periode terpilih</div>
    </div>
    <div class="kpi sky">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(2,132,199,.1);color:var(--sky)"><?= icon('mountain', 18) ?></div></div>
      <div class="kpi-lbl">Setara Pohon</div>
      <div><span class="kpi-val" id="k-trees">-</span><span class="kpi-unit">pohon/thn</span></div>
      <div class="kpi-sub">Estimasi serapan CO2</div>
    </div>
    <div class="kpi rose">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(244,63,94,.1);color:var(--rose)"><?= icon('activity', 18) ?></div></div>
      <div class="kpi-lbl">Setara Jarak Mobil</div>
      <div><span class="kpi-val" id="k-km">-</span><span class="kpi-unit">km</span></div>
      <div class="kpi-sub">Emisi yang tidak dihasilkan</div>
    </div>
  </div>

  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">CO2 Dihindari & Energi Solar</div><div class="card-sub">Per hari</div></div>
      <div class="range-sel" id="range-sel">
        <button class="rb" data-r="7d">7 Hari</button>
        <button class="rb on" data-r="30d">30 Hari</button>
      </div>
    </div>
    <div class="card-bd">
      <div id="chart-env" style="min-height:280px"></div>
    </div>
  </div>

  <div class="note-card">
    <?= icon('leaf', 16) ?>
    <div>
      <b>Cara hitung estimasi:</b> 1 pohon dewasa menyerap ~21 kg CO2/tahun (rata-rata umum),
      dan 1 km perjalanan mobil bensin rata-rata menghasilkan ~120 gram CO2. Angka CO2 dihindari
      sendiri berasal dari sensor <code>co2_avoided_today</code> di Home Assistant (perhitungan
      emisi grid PLN yang tidak jadi dipakai karena tertutup solar/baterai).
    </div>
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

const chartEnv = new ApexCharts(el('chart-env'), {
  series: [
    {name:'CO2 Dihindari (kg)', type:'column', data:[]},
    {name:'Energi Solar (kWh)', type:'line',   data:[]},
  ],
  chart: {...APEX_COMMON.chart, type:'line', height:280},
  colors: ['#10b981', '#f59e0b'],
  stroke: {curve:'smooth', width:[0, 2.5]},
  plotOptions: {bar:{borderRadius:4, columnWidth:'55%'}},
  dataLabels: {enabled:false},
  xaxis: { type:'category', labels:{style:{colors:'#98a0b3', fontSize:'10px'}, rotate:-45}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: [
    {title:{text:'kg CO2', style:{color:'#059669'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}},
    {opposite:true, title:{text:'kWh', style:{color:'#f59e0b'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>v.toFixed(1)}},
  ],
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {...APEX_COMMON.tooltip, shared:true},
});
chartEnv.render();

// Faktor konversi estimasi (rata-rata umum, bukan pengukuran presisi)
const CO2_PER_TREE_PER_YEAR_KG = 21;
const CO2_PER_KM_CAR_KG = 0.12;

let currentRange = '30d';
async function fetchLingkungan() {
  try {
    const res = await fetch('proxy.php?type=influx_environment_history&range=' + currentRange);
    if (!res.ok) throw new Error();
    const raw = await res.json();
    const co2Arr   = Array.isArray(raw.co2_today) ? raw.co2_today : [];
    const solarArr = Array.isArray(raw.solar_kwh) ? raw.solar_kwh : [];

    const totalCo2   = co2Arr.reduce((s, r) => s + (r.value || 0), 0);
    const totalSolar = solarArr.reduce((s, r) => s + (r.value || 0), 0);

    set('k-total-co2', totalCo2.toFixed(1));
    set('k-total-co2-sub', `${co2Arr.length} hari terakhir`);
    set('k-total-solar', totalSolar.toFixed(1));
    set('k-trees', (totalCo2 / CO2_PER_TREE_PER_YEAR_KG * (365 / Math.max(co2Arr.length,1))).toFixed(1));
    set('k-km', Math.round(totalCo2 / CO2_PER_KM_CAR_KG).toLocaleString('id-ID'));

    const labels = co2Arr.map(r => new Date(r.time).toLocaleDateString('id-ID', {day:'numeric', month:'short'}));
    chartEnv.updateSeries([
      {name:'CO2 Dihindari (kg)', data: co2Arr.map((r,i) => ({x: labels[i] || '-', y: r.value}))},
      {name:'Energi Solar (kWh)', data: solarArr.map((r,i) => ({x: labels[i] || '-', y: r.value}))},
    ]);
  } catch(e) { console.warn('Lingkungan fetch error:', e); }
}

document.getElementById('range-sel').addEventListener('click', e => {
  const b = e.target.closest('.rb'); if (!b) return;
  currentRange = b.dataset.r;
  document.querySelectorAll('#range-sel .rb').forEach(x => x.classList.toggle('on', x === b));
  fetchLingkungan();
});

fetchLingkungan();
setInterval(fetchLingkungan, 600000);
</script>
</body>
</html>
