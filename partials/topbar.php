<?php
/**
 * partials/topbar.php
 *
 * Sebelum include, halaman WAJIB/OPSIONAL mengisi:
 *
 *   $tb_h1        (string, HTML diizinkan) contoh: "🔋 Monitor Baterai — Gabungan (2 Bank)"
 *   $tb_sub_init  (string) teks awal subtitle sebelum JS overwrite id="tb-sub"
 *   $tb_pills     (array, opsional) daftar pill kustom di kanan, tiap item:
 *                   ['id' => 'src-badge', 'class' => 'pill', 'text' => 'Sumber: —']
 *                 Clock selalu otomatis ditambahkan paling kanan.
 *
 * Contoh di pzem.php:
 *   $tb_h1  = 'Voltara EMS';
 *   $tb_sub_init = 'Menghubungkan...';
 *   $tb_pills = [
 *     ['id' => 'src-badge', 'class' => 'pill',    'text' => 'Sumber: —'],
 *     ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => '● Online'],
 *   ];
 *   include __DIR__ . '/partials/topbar.php';
 */
$tb_h1       = $tb_h1       ?? 'Voltara EMS';
$tb_sub_init = $tb_sub_init ?? 'Menghubungkan...';
$tb_pills    = $tb_pills    ?? [
    ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => '● Online'],
];
?>
<header class="topbar">
  <div class="topbar-l">
    <h1 id="tb-title"><?= $tb_h1 ?></h1>
    <p id="tb-sub"><?= htmlspecialchars($tb_sub_init, ENT_QUOTES, 'UTF-8') ?></p>
  </div>
  <div class="topbar-r">
<?php foreach ($tb_pills as $p): ?>
    <span class="<?= htmlspecialchars($p['class']) ?>" id="<?= htmlspecialchars($p['id']) ?>"><?= htmlspecialchars($p['text'], ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>
    <span class="clock" id="clock">--:--</span>
  </div>
</header>
