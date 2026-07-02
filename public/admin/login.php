<?php

require_once __DIR__ . '/../../app/helpers/Auth.php';
require_once __DIR__ . '/../../app/helpers/Lang.php';

Auth::start();

if (isset($_GET['lang'])) {
    Lang::setLocale($_GET['lang']);
    header('Location: login.php');
    exit;
}

Lang::load();

if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($username, $password)) {
        header('Location: index.php');
        exit;
    }

    $error = t('login.invalid');
}
?>
<!DOCTYPE html>
<html lang="<?= Lang::current() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('login.title') ?></title>

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <link rel="icon" href="/assets/pwa/icon-192.png">
    <link rel="apple-touch-icon" href="/assets/pwa/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="KP Bot Admin">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: radial-gradient(120% 100% at 50% 0%, #1e293b 0%, #0f172a 60%);
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        @keyframes loginIn {
            from { opacity: 0; transform: translateY(14px) scale(.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-box {
            background: #fff;
            border-radius: 18px;
            padding: 36px 32px;
            width: 100%;
            max-width: 340px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
            animation: loginIn .4s cubic-bezier(.4,0,.2,1);
        }
        @media (max-width: 380px) {
            .login-box { padding: 28px 22px; border-radius: 14px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; transition-duration: .001ms !important; }
        }
        .login-box .logo {
            width: 46px; height: 46px;
            border-radius: 13px;
            background: linear-gradient(135deg, #4f46e5, #818cf8);
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; font-weight: 800; color: #fff;
            margin: 0 auto 16px;
        }
        .login-box h1 { font-size: 17px; font-weight: 700; margin: 0 0 4px; text-align: center; color: #0f172a; }
        .login-box .sub { font-size: 13px; color: #64748b; text-align: center; margin: 0 0 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #0f172a; }
        .form-group input {
            width: 100%; padding: 11px 12px;
            border: 1px solid #e7e9f3; border-radius: 9px;
            font-size: 14px; box-sizing: border-box;
            font-family: inherit;
            background: #fcfcfe;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .form-group input:focus {
            outline: none; border-color: #4f46e5;
            box-shadow: 0 0 0 3px #eef2ff;
            background: #fff;
        }
        button {
            width: 100%; padding: 12px;
            background: #4f46e5; color: #fff;
            border: none; border-radius: 9px;
            font-size: 14px; font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: filter .15s ease, transform .05s ease;
        }
        button:hover { filter: brightness(1.08); }
        button:active { transform: scale(.98); }
        .alert-error {
            background: #fee2e2; color: #dc2626;
            padding: 11px 14px; border-radius: 9px;
            font-size: 13px; font-weight: 500;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }
        .lang-switch { display: flex; justify-content: center; gap: 2px; background: #f1f2f8; border-radius: 8px; padding: 3px; margin: 0 auto 18px; width: fit-content; }
        .lang-switch a { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; color: #64748b; }
        .lang-switch a.active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(15,23,42,0.08); }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="lang-switch">
            <a href="?lang=sw" class="<?= Lang::current() === 'sw' ? 'active' : '' ?>">🇹🇿 SW</a>
            <a href="?lang=en" class="<?= Lang::current() === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
        </div>
        <div class="logo">K</div>
        <h1><?= t('login.heading') ?></h1>
        <p class="sub"><?= t('login.subtitle') ?></p>
        <?php if ($error !== null): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label><?= t('login.username') ?></label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label><?= t('login.password') ?></label>
                <input type="password" name="password" required>
            </div>
            <button type="submit"><?= t('login.submit') ?></button>
        </form>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js').catch(function (err) {
                    console.warn('[PWA] Service worker registration failed:', err);
                });
            });
        }
    </script>
</body>
</html>
