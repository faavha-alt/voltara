<?php
/**
 * v2/partials/topbar.php
 * Nav horizontal + efek kaca (glass/blur), rebranding dari "LuminaEnergy"
 * (contoh Stitch) jadi "Voltara EMS". $active dipakai buat highlight menu.
 */
if (!isset($active)) { $active = 'overview'; }
$V2_NAV = [
    'overview' => ['index.php', 'Dashboard'],
    'energy'   => ['#', 'Energy'],
    'devices'  => ['#', 'Devices'],
    'analytics'=> ['#', 'Analytics'],
    'settings' => ['#', 'Settings'],
];
?>
<header class="fixed top-0 w-full z-50 flex justify-between items-center px-6 md:px-10 max-w-[1440px] left-1/2 -translate-x-1/2 h-20 bg-surface/80 backdrop-blur-xl border-b border-outline-variant/30 shadow-sm">
  <div class="flex items-center gap-8">
    <span class="font-display-lg text-display-lg-mobile tracking-tighter text-primary">Voltara EMS</span>
    <nav class="hidden md:flex gap-6 items-center h-full">
<?php foreach ($V2_NAV as $key => [$href, $label]): ?>
<?php if ($active === $key): ?>
      <a class="text-primary font-bold border-b-2 border-primary pb-1 h-full flex items-center mt-1" href="<?= $href ?>"><?= htmlspecialchars($label) ?></a>
<?php else: ?>
      <a class="text-on-surface-variant hover:text-primary transition-colors flex items-center h-full" href="<?= $href ?>"><?= htmlspecialchars($label) ?></a>
<?php endif; ?>
<?php endforeach; ?>
    </nav>
  </div>
  <div class="flex items-center gap-4">
    <span class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary-container/40 text-on-secondary-container text-label-sm font-bold" id="v2-sys-badge">
      <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span> Online
    </span>
    <button class="p-2 text-on-surface-variant hover:text-primary active:scale-95 transition-all">
      <?= icon('notifications') ?>
    </button>
    <a href="/index.php" class="p-2 text-on-surface-variant hover:text-primary active:scale-95 transition-all" title="Kembali ke tampilan v1">
      <?= icon('undo') ?>
    </a>
  </div>
</header>
<main class="flex-grow pt-28 pb-12 px-6 md:px-10 max-w-[1440px] mx-auto w-full">
