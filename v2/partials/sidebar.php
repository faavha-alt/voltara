<?php
/**
 * v2/partials/sidebar.php
 * Sidebar kaca (glass) tetap di kiri, 280px, gaya "Luminous Energy
 * Interface". Menggantikan topbar.php dari desain v2 sebelumnya.
 */
if (!isset($active)) { $active = 'overview'; }
$V2_NAV = [
    'overview' => ['index.php', 'dashboard', 'Overview'],
    'sites'    => ['#', 'location_on', 'Lokasi'],
    'analytics'=> ['#', 'bar_chart', 'Analitik'],
    'devices'  => ['#', 'memory', 'Perangkat'],
    'reports'  => ['#', 'description', 'Laporan'],
    'billing'  => ['#', 'payments', 'Biaya'],
];
?>
<aside class="fixed left-0 top-0 h-full w-sidebar-width backdrop-blur-xl bg-glass-fill border-r border-glass-border shadow-[0_8px_32px_rgba(0,0,0,0.04)] flex flex-col py-6 z-50">
  <div class="px-6 mb-10 flex items-center gap-3">
    <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">bolt</span>
    <div>
      <h1 class="text-title-md font-title-md font-bold text-on-surface">Voltara</h1>
      <p class="text-label-sm text-on-surface-variant leading-none">Solar Pulse</p>
    </div>
  </div>
  <nav class="flex-1 space-y-1">
<?php foreach ($V2_NAV as $key => [$href, $ico, $label]): ?>
<?php if ($active === $key): ?>
    <a class="bg-primary-container text-on-primary-container rounded-xl flex items-center gap-3 px-4 py-3 mx-4 transition-transform active:scale-95" href="<?= $href ?>">
      <span class="material-symbols-outlined"><?= $ico ?></span>
      <span class="font-label-md text-label-md"><?= htmlspecialchars($label) ?></span>
    </a>
<?php else: ?>
    <a class="text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/50 flex items-center gap-3 px-4 py-3 mx-4 transition-all rounded-xl" href="<?= $href ?>">
      <span class="material-symbols-outlined"><?= $ico ?></span>
      <span class="font-label-md text-label-md"><?= htmlspecialchars($label) ?></span>
    </a>
<?php endif; ?>
<?php endforeach; ?>
  </nav>
  <div class="mt-auto px-4">
    <div class="glass-card p-4 bg-primary/5 mb-6">
      <div class="w-full h-24 rounded-xl mb-3 bg-gradient-to-br from-primary-container/40 to-secondary-container/30 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary/40" style="font-size:40px">eco</span>
      </div>
      <p class="text-label-sm font-bold mb-1">Energi Bersih,</p>
      <p class="text-label-sm font-bold mb-2">Masa Depan Lebih Cerah</p>
      <p class="text-[10px] text-on-surface-variant leading-tight mb-3" id="v2-co2-promo">Memuat data CO2...</p>
      <a href="/lingkungan.php" class="block w-full py-2 bg-primary text-white text-label-sm rounded-lg hover:bg-primary-container transition-colors text-center">Lihat Dampak &#8594;</a>
    </div>
    <a href="/index.php" class="flex items-center gap-3 px-4 py-3 bg-surface-container-low rounded-2xl hover:bg-surface-container-high/60 transition-colors">
      <div class="w-10 h-10 rounded-full bg-surface-container-highest overflow-hidden flex items-center justify-center text-on-surface-variant">
        <span class="material-symbols-outlined">undo</span>
      </div>
      <div>
        <p class="text-label-md font-bold text-on-surface">Kembali ke v1</p>
        <p class="text-[10px] text-on-surface-variant">Tampilan dashboard klasik</p>
      </div>
    </a>
  </div>
</aside>
<main class="ml-[280px] p-10 min-h-screen">