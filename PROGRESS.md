# Progress — voltara

Dibuat: 2026-08-26

## Tasks

- [x] Migrasi backend dari Home Assistant ke Flask (BMS) + InfluxDB —
      `proxy.php` header menyatakan "API Proxy v2.0 (Tanpa Home Assistant)",
      semua fetch data live sudah lewat `FLASK_URL`/`INFLUX_URL`, skema
      nama sensor (`sensor.huawei_soc`, dst.) dipertahankan hanya sebagai
      format data, bukan sumbernya lagi.
- [x] Refactor Tahap 1a — partials & CSS gabungan untuk 6 halaman (index,
      mppt, battery, pzem, huawei, jikong) — sesuai `README-REFACTOR.md`
      ("✅ SELESAI 6/6 halaman") dan dikonfirmasi semua 6 halaman memang
      sudah pakai `include partials/head.php|sidebar.php|topbar.php`.
- [x] Refactor Tahap 1b — sentralisasi derived metrics ke `lib/metrics.php`
      (`build_computed_metrics()`) — dikonfirmasi `proxy.php` memanggil
      fungsi ini dan mengirim hasilnya sebagai key `computed`, DAN semua
      halaman (index, mppt, battery, pzem, huawei, jikong) sudah membaca
      `d.computed.*` di JS-nya (bukan hitung ulang manual lagi). Ini
      awalnya disebut "tahap berikutnya" di `README-REFACTOR.md` tapi
      ternyata sudah dikerjakan.
- [ ] Bersihkan style inline lama yang masih tersisa di tiap halaman
      (`README-REFACTOR.md` bagian "Setelah semua halaman bermigrasi"
      minta blok `<style>` inline lama dihapus/dipindah ke
      `_old_backup/` — belum dicek apakah masih ada sisa blok tsb di
      file-file halaman) (perlu konfirmasi user).
- [ ] Selesaikan redesign `v2/` ("Solar Pulse") — baru `v2/index.php`
      yang ada, halaman lain (mppt, battery, pzem, huawei, jikong, dll)
      belum diporting ke struktur/style `v2/partials/` yang baru.
- [ ] Kejelasan status `dashboard-v2.php` di root vs folder `v2/` — dua
      hal yang sepertinya keduanya "preview v2" tapi independen satu
      sama lain (satu masih pakai partial lama, satu pakai partial baru
      Tailwind-style). Perlu diputuskan mana yang dilanjutkan/dibuang
      (perlu konfirmasi user).
- [ ] Rencana alert Telegram / cron berbasis `lib/metrics.php` yang
      disebut sebagai motivasi sentralisasi metrics ("Tahap berikutnya
      BUTUH angka yang identik...") — belum ada file cron/Telegram di
      repo ini, sepertinya belum diimplementasi (perlu konfirmasi user).
- [ ] Fitur di halaman lain (`alarm.php`, `biaya.php`, `historis.php`,
      `lingkungan.php`, `inverter.php`) belum diperiksa detail apakah
      juga sudah 100% pakai partial+metrics terpusat atau masih versi
      lama — hanya 6 halaman di README-REFACTOR yang eksplisit disebut
      "selesai" (perlu konfirmasi user).

## Log sesi

### 2026-08-26
- Ditambahkan ke Project Workspace, dokumentasi diisi berdasarkan
  eksplorasi kode existing (bukan dari rencana asli developer).
- Repo baru berisi satu commit tunggal ("Initial commit: Voltara EMS
  dashboard", 17 Agu 2026) — histori git tidak granular, tidak bisa
  dipakai untuk lacak urutan pengerjaan asli; status task di atas
  disimpulkan dari isi kode + `README-REFACTOR.md`, bukan dari log commit.
- Dicek relasi dengan project `voltara-app` (Laravel migration) — tidak
  ada bukti langsung di repo ini (tidak ada referensi Laravel/migrasi),
  dugaan hubungan legacy→migrasi berdasarkan kesamaan nama & domain saja.
