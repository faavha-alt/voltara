<?php
/**
 * partials/sidebar.php
 *
 * Sebelum include, halaman WAJIB mengisi:
 *
 *   $active  (string) salah satu: 'overview' | 'mppt' | 'battery' | 'pzem'
 *            Gunakan 'battery' juga untuk huawei.php & jikong.php karena
 *            keduanya adalah halaman detail dari section Baterai.
 *
 *   $accent  (string, opsional) salah satu: 'amber' | 'mint' | 'sky'
 *            Kalau tidak diisi, otomatis diturunkan dari $active:
 *              overview/mppt -> amber, battery -> mint, pzem -> sky
 *
 * Mencakup DUA navigasi:
 *   1. <aside class="sb"> — sidebar kiri, di-render langsung sebagai HTML,
 *      disembunyikan lewat CSS di layar sempit (<=768px).
 *   2. Nav bawah untuk HP — SENGAJA TIDAK di-render sebagai HTML statis.
 *      Elemennya baru dibuat oleh JavaScript, dan HANYA saat lebar layar
 *      memang <=768px (dicek pakai matchMedia). Kalau CSS gagal/telat
 *      load (mis. karena cache lama), di HP paling buruk nav bawah
 *      belum muncul sampai CSS siap — TIDAK PERNAH "bocor" tampil di
 *      desktop dengan style browser default yang berantakan, karena di
 *      desktop elemennya memang tidak pernah dibuat sama sekali.
 */
if (!isset($active)) { $active = 'overview'; }

if (!isset($accent)) {
    $accent = match ($active) {
        'battery' => 'mint',
        'pzem'    => 'sky',
        'inverter' => 'sky',
        'biaya', 'lingkungan' => 'mint',
        'alarm'   => 'sky',
        default   => 'amber',
    };
}

$ACCENT_MAP = [
    'amber' => ['var(--amber)', 'rgba(245,158,11,.08)'],
    'mint'  => ['var(--mint)',  'rgba(16,185,129,.08)'],
    'sky'   => ['var(--sky)',   'rgba(56,189,248,.08)'],
];
[$accentColor, $accentBg] = $ACCENT_MAP[$accent] ?? $ACCENT_MAP['amber'];

// Item nav utama: key => [href, ikon, label]
$NAV_ITEMS = [
    'overview' => ['index.php',   'grid',   'Overview'],
    'mppt'     => ['mppt.php',    'sun',    'Solar'],
    'battery'  => ['battery.php', 'battery','Baterai'],
    'pzem'     => ['pzem.php',    'bolt',   'AC'],
    'inverter' => ['inverter.php', 'plug',   'Inverter'],
];

// Item nav sekunder: key => [href, ikon, label]
$SECONDARY_NAV_ITEMS = [
    'historis'   => ['historis.php',   'chart', 'Historis'],
    'biaya'      => ['biaya.php',      'coin',  'Biaya'],
    'lingkungan' => ['lingkungan.php', 'leaf',  'Lingkungan'],
];
$SYSTEM_NAV_ITEMS = [
    'alarm' => ['alarm.php', 'bell', 'Alarm'],
];

// Bangun HTML nav-bawah di PHP (satu sumber data dgn sidebar desktop),
// tapi TIDAK di-echo langsung ke body — dikirim lewat JSON ke JS di
// bawah, yang baru menyuntikkannya ke DOM saat viewport benar HP.
ob_start();
foreach ($NAV_ITEMS as $key => [$href, $ico, $label]) {
    $onClass = $active === $key ? ' on' : '';
    echo '<a class="bn-a' . $onClass . '" href="' . htmlspecialchars($href) . '">'
       . icon($ico, 20)
       . '<span>' . htmlspecialchars($label) . '</span></a>';
}
$bottomNavItemsHtml = ob_get_clean();
?>
<aside class="sb" style="--accent:<?= $accentColor ?>;--accent-bg:<?= $accentBg ?>">
  <div class="sb-logo">
    <div class="sb-icon"><?= icon('bolt', 18, 'color:#1a1207') ?></div>
    <div>
      <div class="sb-brand">Voltara EMS</div>
      <div class="sb-sub">Energy Monitor</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="nav-sec">Monitor</div>
<?php foreach ($NAV_ITEMS as $key => [$href, $ico, $label]): ?>
    <a class="nav-a<?= $active === $key ? ' on' : '' ?>" href="<?= $href ?>"><span class="nav-ico"><?= icon($ico, 16) ?></span><?= htmlspecialchars($label) ?></a>
<?php endforeach; ?>
    <div class="nav-sec">Analitik</div>
<?php foreach ($SECONDARY_NAV_ITEMS as $key => [$href, $ico, $label]): ?>
    <a class="nav-a<?= $active === $key ? ' on' : '' ?>" href="<?= $href ?>"><span class="nav-ico"><?= icon($ico, 16) ?></span><?= htmlspecialchars($label) ?></a>
<?php endforeach; ?>
    <div class="nav-sec">Sistem</div>
<?php foreach ($SYSTEM_NAV_ITEMS as $key => [$href, $ico, $label]): ?>
    <a class="nav-a<?= $active === $key ? ' on' : '' ?>" href="<?= $href ?>"><span class="nav-ico"><?= icon($ico, 16) ?></span><?= htmlspecialchars($label) ?></a>
<?php endforeach; ?>
    <a class="nav-a" href="?logout=1" onclick="return confirm('Keluar?')"><span class="nav-ico"><?= icon('logout', 16) ?></span>Keluar</a>
  </nav>
  <div class="sb-foot">
    <div class="sys-pill" id="sys-pill">
      <div><span class="dot" id="sys-dot"></span><span class="sys-txt" id="sys-txt">Memeriksa...</span></div>
      <div style="font-size:10px;color:var(--muted);margin-top:3px">voltara.favha.cloud</div>
    </div>
  </div>
</aside>

<main class="main">

<script>
// Nav bawah HP — dibuat murni oleh JS, hanya kalau viewport <=768px.
// Tidak pernah ada di DOM sama sekali di desktop, jadi tidak mungkin
// "bocor" tampil dengan style default browser yang berantakan.
(function () {
  var itemsHtml = <?= json_encode($bottomNavItemsHtml) ?>;
  var accentColor = <?= json_encode($accentColor) ?>;
  var navEl = null;

  function ensureNav() {
    if (navEl) return;
    navEl = document.createElement('nav');
    navEl.className = 'bottom-nav';
    navEl.style.setProperty('--accent', accentColor);
    navEl.innerHTML = itemsHtml;
    document.body.appendChild(navEl);
  }
  function removeNav() {
    if (navEl) { navEl.remove(); navEl = null; }
  }
  function sync(mq) {
    if (mq.matches) ensureNav(); else removeNav();
  }

  var mq = window.matchMedia('(max-width: 768px)');
  sync(mq);
  if (mq.addEventListener) mq.addEventListener('change', sync);
  else if (mq.addListener) mq.addListener(sync); // Safari lama
})();
</script>
