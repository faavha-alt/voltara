<?php
/**
 * Voltara EMS — Konfigurasi session terpusat.
 * WAJIB di-require paling atas SEBELUM ada output apa pun.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_save_path('/tmp');               // SATU path untuk SEMUA file
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 86400);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        // 'secure' => true,  // aktifkan HANYA kalau selalu via HTTPS sampai ke PHP
    ]);
    session_start();
}