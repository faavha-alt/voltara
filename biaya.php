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

$page_title  = 'Biaya - Voltara EMS';
$active      = 'biaya';
$tb_h1       = 'Biaya & Penghematan';
$tb_sub_init = 'Menghubungkan...';
$tb_pills    = [];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<div class="content" style="padding-top:20px">

  <div class="kpi-row">
    <div class="kpi rose">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(244,63,94,.1);color:var(--rose)"><?= icon('coin', 18) ?></div></div>
      <div class="kpi-lbl">Total Biaya PLN</div>
      <div><span class="kpi-val" id="k-total-cost">-</span><span class="kpi-unit">Rp</span></div>
      <div class="kpi-sub" id="k-total-cost-sub">- hari terakhir</div>
    </div>
    <div class="kpi mint">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(16,185,129,.1);color:var(--mint2)"><?= icon('coin', 18) ?></div></div>
      <div class="kpi-lbl">Total Hemat</div>
      <div><span class="kpi-val" id="k-total-savings">-</span><span class="kpi-unit">Rp</span></div>
      <div class="kpi-sub" id="k-total-savings-sub">Dari solar + baterai</div>
    </div>
    <div class="kpi amber">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(245,158,11,.1);color:var(--amber2)"><?= icon('gauge', 18) ?></div></div>
      <div class="kpi-lbl">Rata-rata Biaya/Hari</div>
      <div><span class="kpi-val" id="k-avg-cost">-</span><span class="kpi-unit">Rp</span></div>
      <div class="kpi-sub">Periode terpilih</div>
    </div>
    <div class="kpi sky">
      <div class="kpi-head"><div class="kpi-ico" style="background:rgba(2,132,199,.1);color:var(--sky)"><?= icon('activity', 18) ?></div></div>
      <div class="kpi-lbl">Tarif Berlaku</div>
      <div><span class="kpi-val" id="k-price" style="font-size:18px">-</span><span class="kpi-unit">/kWh</span></div>
      <div class="kpi-sub">Golongan 2200 VA</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:0">
    <div class="card-hd">
      <div><div class="card-ttl">Biaya PLN vs Hemat</div><div class="card-sub">Per hari</div></div>
      <div class="range-sel" id="range-sel">
        <button class="rb" data-r="7d">7 Hari</button>
        <button class="rb on" data-r="30d">30 Hari</button>
      </div>
    </div>
    <div class="card-bd">
      <div id="chart-cost" style="min-height:260px"></div>
    </div>
  </div>

  <div class="card">
    <div class="card-hd">
      <div><div class="card-ttl">Rincian Harian</div><div class="card-sub">Energi, autarki, biaya, dan hemat per hari</div></div>
    </div>
    <div style="overflow-x:auto">
      <table class="rtable">
        <thead><tr><th>Tanggal</th><th>Konsumsi</th><th>Autarki</th><th>Dari Grid</th><th>Biaya</th><th>Hemat</th></tr></thead>
        <tbody id="cost-table-body">
          <tr><td colspan="6" style="text-align:center;color:var(--muted)">Memuat...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</main>
</div><!-- /layout -->

<script>
const el  = id => document.getElementById(id);
const set = (id, v) => { const e = el(id); if (e) e.textContent = v; };
const fmtRp = v => new Intl.NumberFormat('id-ID').format(Math.round(v || 0));

const APEX_COMMON = {
  chart: { background:'transparent', toolbar:{show:false}, animations:{enabled:false}, fontFamily:'Inter,sans-serif' },
  grid: { borderColor:'#e3e7ef', strokeDashArray:4 },
  tooltip: { theme:'light' },
};

const chartCost = new ApexCharts(el('chart-cost'), {
  series: [
    {name:'Biaya PLN', data:[]},
    {name:'Hemat', data:[]},
  ],
  chart: {...APEX_COMMON.chart, type:'bar', height:260},
  colors: ['#e11d48', '#059669'],
  plotOptions: {bar:{borderRadius:4, columnWidth:'60%'}},
  dataLabels: {enabled:false},
  xaxis: { type:'category', labels:{style:{colors:'#98a0b3', fontSize:'10px'}, rotate:-45}, axisBorder:{show:false}, axisTicks:{show:false} },
  yaxis: { title:{text:'Rupiah', style:{color:'#98a0b3'}}, labels:{style:{colors:'#98a0b3'}, formatter:v=>'Rp'+Math.round(v/1000)+'k'} },
  grid: APEX_COMMON.grid,
  legend: {position:'top', horizontalAlign:'right', labels:{colors:'#5b6178'}},
  tooltip: {...APEX_COMMON.tooltip, y:{formatter:v=>'Rp '+fmtRp(v)}},
});
chartCost.render();

let currentRange = '30d';
async function fetchBiaya() {
  try {
    const res = await fetch('proxy.php?type=influx_cost_history&range=' + currentRange);
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (!data || !Array.isArray(data.days)) return;

    set('k-total-cost', fmtRp(data.total_cost));
    set('k-total-cost-sub', `${data.days.length} hari terakhir`);
    set('k-total-savings', fmtRp(data.total_savings));
    set('k-avg-cost', data.days.length ? fmtRp(data.total_cost / data.days.length) : '-');
    set('k-price', 'Rp ' + fmtRp(data.price_per_kwh));

    const labels = data.days.map(d => new Date(d.date).toLocaleDateString('id-ID', {day:'numeric', month:'short'}));
    chartCost.updateSeries([
      {name:'Biaya PLN', data: data.days.map((d,i) => ({x: labels[i], y: d.cost}))},
      {name:'Hemat',     data: data.days.map((d,i) => ({x: labels[i], y: d.savings}))},
    ]);

    const tbody = el('cost-table-body');
    if (tbody) {
      tbody.innerHTML = data.days.slice().reverse().map(d => `
        <tr>
          <td>${new Date(d.date).toLocaleDateString('id-ID', {weekday:'short', day:'numeric', month:'short'})}</td>
          <td class="val-cell">${d.energy_kwh.toFixed(2)} kWh</td>
          <td class="val-cell">${d.autarky_pct.toFixed(0)}%</td>
          <td class="val-cell">${d.grid_kwh.toFixed(2)} kWh</td>
          <td class="val-cell" style="color:var(--rose)">Rp ${fmtRp(d.cost)}</td>
          <td class="val-cell" style="color:var(--mint2)">Rp ${fmtRp(d.savings)}</td>
        </tr>
      `).join('');
    }
  } catch(e) { console.warn('Biaya fetch error:', e); }
}

document.getElementById('range-sel').addEventListener('click', e => {
  const b = e.target.closest('.rb'); if (!b) return;
  currentRange = b.dataset.r;
  document.querySelectorAll('#range-sel .rb').forEach(x => x.classList.toggle('on', x === b));
  fetchBiaya();
});

fetchBiaya();
setInterval(fetchBiaya, 600000);
</script>
</body>
</html>
