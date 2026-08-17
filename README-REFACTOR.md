# Refactor Tahap 1a — Partials & CSS Gabungan ✅ SELESAI (6/6 halaman)

## Yang sudah dibuat

```
voltara/
├── assets/
│   └── style.css       ← CSS gabungan dari 6 halaman (dedupe otomatis, 731 baris dari ~1122 baris asli)
├── partials/
│   ├── head.php          ← <head> + buka <body><div class="layout">
│   ├── sidebar.php        ← <aside> sidebar + buka <main class="main">
│   └── topbar.php         ← <header class="topbar">
├── index.php               ← ✅ dimigrasi
├── mppt.php                 ← ✅ dimigrasi
├── battery.php               ← ✅ dimigrasi
├── pzem.php                    ← ✅ dimigrasi
├── huawei.php                   ← ✅ dimigrasi
├── jikong.php                     ← ✅ dimigrasi
├── proxy.php                       ← tidak diubah (backend, disalin apa adanya)
└── README-REFACTOR.md               ← file ini
```

**Semua 6 halaman sudah dimigrasi.** Isi HTML/JS unik tiap halaman
disalin apa adanya — tidak ada logic yang diubah, hanya boilerplate
(head/sidebar/topbar/CSS) yang sekarang dipakai bersama.

## ⚠️ File yang HARUS Anda salin sendiri sebelum upload ke server

File berikut **tidak ada** di upload Anda ke saya, jadi tidak saya
sertakan di paket ini — ambil dari project lama Anda dan taruh di folder
yang sama (root `voltara/`):

- `_session.php` — session handler yang di-`require` semua halaman
- `login.php` — halaman login yang dituju saat redirect

Tanpa 2 file ini, semua halaman akan gagal load (fatal error require).

## Perbedaan kecil vs versi asli (sengaja disederhanakan)

- **`sb-foot` sidebar**: teks kecil di bawah status sistem sekarang selalu
  `voltara.favha.cloud` di semua halaman. Versi asli menampilkan teks
  berbeda-beda per halaman (`2 Bank · Huawei + Jikong` di battery.php,
  `Huawei ESM-48100U2` di huawei.php, dst). Kalau Anda ingin
  mengembalikan teks kustom ini, tambahkan parameter `$sb_foot_note` di
  `partials/sidebar.php` — beri tahu saya kalau mau saya buatkan.

## Cara migrasi 1 halaman (pola yang sama persis untuk semua)

**Sebelum** (contoh lama `mppt.php`):
```php
<?php require __DIR__.'/_session.php'; ...auth check... ?>
<!DOCTYPE html>
<html>...
<style> /* ratusan baris CSS duplikat */ </style>
</head>
<body>
<div class="layout">
  <aside class="sb">...sidebar sama persis...</aside>
  <main class="main">
    <header class="topbar">...</header>
    <!-- konten unik mppt -->
```

**Sesudah**:
```php
<?php
require __DIR__ . '/_session.php';
if (isset($_GET['logout'])) { /* ...logout... */ }
if (!isset($_SESSION['voltara_auth'])) { header('Location: login.php'); exit; }

$page_title  = 'Solar MPPT — Voltara EMS';
$active      = 'mppt';                 // penting: untuk highlight sidebar + accent warna
$tb_h1       = 'Solar MPPT';
$tb_sub_init = 'Menghubungkan ke MPPT...';
$tb_pills    = [
    ['id' => 'sys-badge', 'class' => 'pill ok', 'text' => '● Online'],
];

include __DIR__ . '/partials/head.php';
include __DIR__ . '/partials/sidebar.php';
include __DIR__ . '/partials/topbar.php';
?>

<!-- konten unik mppt (hero, chart, kpi, dsb — SALIN APA ADANYA dari file lama) -->
<div class="content">
  ...
</div>

</main>
</div><!-- /layout -->

<script>
  // JS unik mppt — SALIN APA ADANYA dari file lama, tidak perlu diubah
</script>
</body>
</html>
```

## Nilai `$active` per halaman

| File           | `$active`   | Accent otomatis |
|----------------|-------------|------------------|
| index.php      | `overview`  | amber            |
| mppt.php       | `mppt`      | amber            |
| battery.php    | `battery`   | mint             |
| huawei.php     | `battery`   | mint (detail bank 1) |
| jikong.php     | `battery`   | mint (detail bank 2) |
| pzem.php       | `pzem`      | sky              |

huawei.php dan jikong.php tetap pakai `$active = 'battery'` supaya menu
"Baterai" di sidebar tetap ter-highlight, karena keduanya halaman detail
dari battery.php (link "Detail →").

## `$tb_pills` per halaman (sesuaikan dengan topbar asli tiap file)

- **index.php**: `src-badge` (pill biasa) + `sys-badge` (pill ok)
- **mppt.php**: `sys-badge` saja
- **pzem.php**: `src-badge` + `sys-badge`
- **battery.php**: 1 pill custom `combo-pill` — pakai:
  ```php
  $tb_pills = [['id'=>'combo-pill','class'=>'pill ok','text'=>'● —']];
  ```
- **huawei.php / jikong.php**: cek file asli masing-masing untuk id pill yang dipakai JS-nya, lalu deklarasikan di `$tb_pills` dengan id yang SAMA (supaya `document.getElementById()` di script lama tidak perlu diubah sama sekali).

## Yang TIDAK diubah

Sengaja **tidak** menyentuh:
- Logic JS tiap halaman (`renderStates()`, chart init, dll) — itu tahap **1b** (lib/metrics.php) berikutnya.
- `proxy.php` dan file backend lain.

Supaya migrasi ini murni soal struktur/duplikasi, risiko bug minimal, dan
bisa di-test halaman per halaman secara independen.

## Testing setelah migrasi tiap halaman

1. Buka halaman di browser, pastikan sidebar menu yang benar ter-highlight dengan warna accent yang benar.
2. Cek console browser — pastikan tidak ada error `Cannot read property of null` (tanda ada `id` yang hilang/berubah).
3. Bandingkan tampilan dengan screenshot file lama sebelum dihapus.

## Setelah semua halaman bermigrasi

Hapus (atau pindahkan ke folder `_old_backup/`) blok `<style>` inline yang
lama — sudah tidak dipakai karena sudah digantikan `assets/style.css`.
