# voltara

Dashboard web PHP untuk **Voltara EMS** (Energy Management System) — memantau
sistem energi hibrida rumahan (PV/solar + 2 bank baterai + grid PLN) secara
real-time: daya PV, status baterai (Huawei + Jikong, termasuk heatmap 15 sel),
kualitas tegangan AC (PZEM), inverter, biaya/penghematan listrik, histori, dan
alarm. Autentikasi single-user (satu password, bukan multi-tenant).

## Arsitektur

- **Backend**: PHP murni (tanpa framework, tanpa Composer). Tiap halaman
  (`index.php`, `mppt.php`, `battery.php`, `pzem.php`, `huawei.php`,
  `jikong.php`, `inverter.php`, `alarm.php`, `biaya.php`, `historis.php`,
  `lingkungan.php`) adalah file mandiri yang mem-`include` partial bersama.
- **`proxy.php`** — API proxy tunggal (endpoint via `?type=...`) yang menjadi
  satu-satunya sumber data untuk semua halaman lewat fetch/SSE dari browser.
  Menurut komentar di file ini, proxy **sudah dimigrasi dari v1 (Home
  Assistant) ke v2 (tanpa HA)**: data live sekarang diambil dari **Flask API
  internal** (`FLASK_URL`, kemungkinan BMS/baterai) dan **InfluxDB**
  (`INFLUX_URL`, sensor PZEM/MPPT), plus **Open-Meteo** untuk estimasi
  produksi PV dari radiasi matahari (lihat `compute_pv_forecast` di
  `lib/metrics.php`). Nama-nama sensor (`sensor.huawei_soc`,
  `sensor.smartpowermeter_*`, dst.) masih memakai konvensi entity Home
  Assistant sebagai skema data, walau backend HA sudah tidak dipakai lagi.
- **`lib/metrics.php`** — semua perhitungan turunan (power flow, kualitas
  tegangan, gabungan 2 bank baterai, heatmap sel, deteksi sumber daya
  aktif dari neraca energi, biaya PLN vs hemat, estimasi PV) dipusatkan di
  sini (fungsi `compute_*` + entry point `build_computed_metrics()`),
  dipanggil oleh `proxy.php` dan hasilnya dikirim ke browser sebagai key
  `computed` di response `ha_states`. Ini pengganti logic yang sebelumnya
  duplikat sebagai JS terpisah di beberapa halaman (lihat komentar di
  awal file untuk alasan detail, termasuk beberapa keputusan desain
  penting seperti kenapa "Grid PLN" dihitung dari neraca energi, bukan
  dari sensor mentah).
- **`lib/env.php`** — loader `.env` sederhana tanpa dependency (baca
  `.env`, expose lewat `getenv()`/`$_ENV`).
- **`partials/`** (`head.php`, `sidebar.php`, `topbar.php`, `icons.php`) —
  boilerplate HTML bersama hasil refactor dari 6 halaman yang tadinya
  duplikat (lihat `README-REFACTOR.md`). Tiap halaman men-set variabel
  (`$page_title`, `$active`, `$tb_h1`, `$tb_pills`, dst.) sebelum
  `include` partial ini.
- **`assets/style.css`** — CSS gabungan hasil dedupe dari style inline
  6 halaman (731 baris dari ~1122 baris asli).
- **`v2/`** — eksperimen redesign UI terpisah ("Solar Pulse"), pakai
  Tailwind-style utility classes + Material Symbols, arsitektur partial
  sendiri (`v2/partials/`). Baru ada `v2/index.php` — halaman lain belum
  diporting ke sini. Statusnya prototipe/preview, belum menggantikan UI
  utama.
- **`dashboard-v2.php`** — file preview terpisah di root (bukan bagian
  dari folder `v2/`), masih pakai partial lama (`partials/`), title
  "Voltara EMS (Preview v2)" — tampaknya eksperimen/preview lain yang
  independen dari folder `v2/`.
- **Auth**: `login.php` + `_session.php`, password tunggal di-hash lewat
  `VOLTARA_PASSWORD_HASH` (env var), `password_verify()`, session
  regenerate on login, sleep(1) anti brute-force.
- **Config**: semua via `.env` (lihat `.env.example`) — URL Flask/Influx,
  koordinat & kapasitas PV, harga listrik PLN per kWh, hash password.

## Konvensi

- Tanpa build step, tanpa dependency manager — PHP disajikan langsung
  (cocok untuk deploy sederhana, kemungkinan lewat Apache/Nginx + PHP-FPM).
- Pola tiap halaman baru: set variabel konfigurasi (`$page_title`,
  `$active`, `$tb_h1`, `$tb_sub_init`, `$tb_pills`) → `include` 3 partial
  (`head.php`, `sidebar.php`, `topbar.php`) → konten unik → script JS
  inline yang fetch dari `proxy.php`. Lihat `README-REFACTOR.md` untuk
  contoh before/after lengkap dan tabel nilai `$active` per halaman.
- Perhitungan derived metrics HARUS ditambahkan di `lib/metrics.php`
  (bukan JS halaman) supaya angka konsisten antara dashboard dan
  kebutuhan server-side lain (cron, alert Telegram — disebut sebagai
  rencana tahap berikut di komentar `lib/metrics.php`).
- File backup/debug (`*.bak`, `*.bak.*`, `debug_test.php`, dll) diabaikan
  git — kemungkinan ada residu file semacam ini di server produksi asli
  yang tidak ikut ter-clone.
- Bahasa UI & komentar kode: Bahasa Indonesia.

## Hubungan dengan project lain

Ada project terpisah `voltara-app` (`/ai/projects/voltara-app`,
deskripsi: "Voltara EMS - Laravel migration (multi-tenant, API,
mobile-ready)") yang kemungkinan besar adalah **rewrite/migrasi dari
project ini ke Laravel**, berdasarkan nama, domain (EMS), dan tenggat
kesamaan istilah "Voltara EMS". **Namun tidak ditemukan bukti eksplisit
di dalam repo `voltara` ini sendiri** (tidak ada referensi ke Laravel,
tidak ada file migrasi/dokumentasi yang menyebut `voltara-app`) — repo
ini murni PHP vanilla, single-tenant, auth password tunggal (bukan
multi-tenant seperti disebut di deskripsi `voltara-app`). Anggap repo
ini sebagai **versi legacy/awal** dan `voltara-app` sebagai
**versi migrasi/next-gen**, tapi hubungan pastinya **perlu konfirmasi
user**.

## Status

Lihat `PROGRESS.md` untuk progres detail dan riwayat sesi kerja.
