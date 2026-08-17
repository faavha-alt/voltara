<?php
/**
 * Voltara EMS — Login
 */
require __DIR__ . '/_session.php';
require __DIR__ . '/lib/env.php';

// Hash disimpan di .env (VOLTARA_PASSWORD_HASH), lihat .env.example.
// Ganti password: php -r "echo password_hash('password_baru', PASSWORD_DEFAULT);"
define('VOLTARA_PASSWORD_HASH', getenv('VOLTARA_PASSWORD_HASH'));

$error = '';

// Sudah login -> langsung ke panel (cek di ATAS, sebelum proses POST)
if (isset($_SESSION['voltara_auth'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sleep(1); // mitigasi brute-force

    $pass = $_POST['password'] ?? '';

    if (password_verify($pass, VOLTARA_PASSWORD_HASH)) {
        session_regenerate_id(true);   // cegah session fixation
        $_SESSION['voltara_auth'] = true;
        $_SESSION['voltara_time'] = time();
        header('Location: index.php');
        exit;
    }

    $error = 'Password salah atau tidak valid.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Voltara EMS — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
    background:
      linear-gradient(180deg, rgba(210,232,250,.7) 0%, rgba(255,255,255,.85) 55%, #f4f8fb 100%),
      radial-gradient(1200px 600px at 20% -10%, #dbe9ff 0%, transparent 60%);
    padding: 24px;
  }
  .login-card {
    background: rgba(255,255,255,.82);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,.7);
    box-shadow: 0 20px 50px -20px rgba(30,60,100,.25);
    border-radius: 22px; padding: 44px 40px; width: 100%; max-width: 400px; text-align: center;
  }
  .logo { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 30px; }
  .logo-icon {
    width: 46px; height: 46px; border-radius: 13px;
    background: linear-gradient(135deg, #f5a623, #f97316);
    display: flex; align-items: center; justify-content: center; font-size: 22px;
    box-shadow: 0 8px 20px -8px rgba(245,166,35,.6);
  }
  .logo-text { font-size: 22px; font-weight: 800; color: #152233; letter-spacing: -.4px; }
  .logo-sub  { font-size: 10px; color: #8a99ac; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; }
  h2 { color: #0d1826; font-size: 20px; font-weight: 700; margin-bottom: 8px; }
  p  { color: #5b6b7c; font-size: 14px; margin-bottom: 30px; }
  .field { margin-bottom: 16px; text-align: left; }
  .field label { display: block; color: #5b6b7c; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
  .field input {
    width: 100%; background: #fff; border: 1px solid #e0e8f2; border-radius: 12px;
    padding: 13px 16px; color: #152233; font-size: 15px; outline: none;
    font-family: inherit; transition: border-color .2s, box-shadow .2s;
  }
  .field input:focus { border-color: #2f6fed; box-shadow: 0 0 0 4px rgba(47,111,237,.12); }
  .btn {
    width: 100%; padding: 14px; border: none; border-radius: 12px; color: #fff;
    font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 8px; font-family: inherit;
    background: linear-gradient(135deg, #2f6fed, #4f8bff);
    box-shadow: 0 10px 24px -10px rgba(47,111,237,.6);
    transition: opacity .2s, transform .1s;
  }
  .btn:hover  { opacity: .93; }
  .btn:active { transform: scale(.98); }
  .error {
    background: #fde8e8; border: 1px solid #f6c9c9; border-radius: 10px;
    padding: 10px 14px; color: #dc2626; font-size: 13px; margin-bottom: 16px; font-weight: 600;
  }
  .footer { margin-top: 30px; color: #94a3b8; font-size: 12px; font-weight: 500; }
</style>
</head>
<body>
<div class="login-card">
  <div class="logo">
    <div class="logo-icon">⚡</div>
    <div>
      <div class="logo-text">Voltara EMS</div>
      <div class="logo-sub">Energy Management</div>
    </div>
  </div>

  <h2>Selamat datang</h2>
  <p>Masukkan password untuk akses dashboard</p>

  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" autofocus required>
    </div>
    <button class="btn" type="submit">Masuk →</button>
  </form>

  <div class="footer">Voltara EMS · FMIPA UNS · <?= date('Y') ?></div>
</div>
</body>
</html>