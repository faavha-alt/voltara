<?php
/**
 * partials/head.php
 *
 * Dipakai oleh semua halaman. Sebelum include ini, halaman WAJIB
 * mengisi variabel berikut:
 *
 *   $page_title  (string) contoh: "Solar MPPT — Voltara EMS"
 *
 * Contoh pemakaian di halaman (mis. mppt.php):
 *
 *   <?php
 *   require __DIR__ . '/_session.php';
 *   if (isset($_GET['logout'])) { ...; header('Location: login.php'); exit; }
 *   if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }
 *
 *   $page_title = 'Solar MPPT — Voltara EMS';
 *   include __DIR__ . '/partials/head.php';
 *   ?>
 */
if (!isset($page_title)) { $page_title = 'Voltara EMS'; }
require_once __DIR__ . '/icons.php';

// Kirim header charset eksplisit — kalau server (Apache/Nginx) punya
// default charset non-UTF-8 (mis. ISO-8859-1), header itu bisa menang
// atas <meta charset="UTF-8"> di bawah, dan semua emoji (?????? dst)
// tampil sebagai "??" di browser. Header dari PHP ini memaksa UTF-8.
// Dibungkus headers_sent() check supaya tidak memicu warning kalau
// ternyata sudah ada output sebelumnya (mis. whitespace di _session.php).
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
<?php
// Cache-busting otomatis: versi diambil dari waktu modifikasi file style.css.
// Jadi setiap file CSS diupdate (kapan pun), browser otomatis mengambil
// versi baru — tidak perlu lagi hard-refresh manual tiap kali ada
// perubahan tampilan.
$cssPath = __DIR__ . '/../assets/style.css';
$cssVer  = file_exists($cssPath) ? filemtime($cssPath) : time();
?>
<link rel="stylesheet" href="assets/style.css?v=<?= $cssVer ?>">
</head>
<body>
<div class="layout">