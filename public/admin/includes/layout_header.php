<?php
/** @var string $pageTitle */
/** @var string $activeNav */

require_once __DIR__ . '/../../../app/helpers/Lang.php';

if (isset($_GET['lang'])) {
    Lang::setLocale($_GET['lang']);
    $redirectQuery = $_GET;
    unset($redirectQuery['lang']);
    $redirectUrl = basename($_SERVER['PHP_SELF']) . (($redirectQuery !== []) ? ('?' . http_build_query($redirectQuery)) : '');
    header('Location: ' . $redirectUrl);
    exit;
}

Lang::load();
?>
<!DOCTYPE html>
<html lang="<?= Lang::current() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — KuzaPanel Bot Admin</title>

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
        :root {
            --bg: #f6f7fb;
            --card: #ffffff;
            --border: #e7e9f3;
            --text: #0f172a;
            --text-soft: #64748b;
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-soft: #eef2ff;
            --green: #16a34a;
            --green-soft: #dcfce7;
            --red: #dc2626;
            --red-soft: #fee2e2;
            --amber: #d97706;
            --amber-soft: #fef3c7;
            --sidebar-bg: #0f172a;
            --sidebar-text: #94a3b8;
            --sidebar-active: #1e293b;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        .layout { display: flex; min-height: 100vh; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ---- Hamburger (mobile/tablet only) ---- */
        .hamburger-btn {
            display: none;
            align-items: center;
            justify-content: center;
            width: 38px; height: 38px;
            border-radius: 9px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            font-size: 16px;
            flex-shrink: 0;
            transition: background .15s ease, transform .15s ease;
        }
        .hamburger-btn:hover { background: var(--primary-soft); color: var(--primary); }
        .hamburger-btn:active { transform: scale(0.93); }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.45);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }
        .sidebar-overlay.show { opacity: 1; pointer-events: all; }

        /* ---- Sidebar ---- */
        .sidebar {
            width: 240px;
            background: var(--sidebar-bg);
            color: #fff;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 0;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }
        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 22px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .sidebar .brand .logo {
            width: 34px; height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .sidebar .brand .name { font-size: 15px; font-weight: 700; color: #fff; line-height: 1.2; }
        .sidebar .brand .sub { font-size: 11px; color: var(--sidebar-text); }
        .sidebar nav { padding: 14px 12px; display: flex; flex-direction: column; gap: 2px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            transition: background .15s ease, color .15s ease;
        }
        .sidebar nav a i { width: 18px; text-align: center; font-size: 14px; transition: transform .15s ease; }
        .sidebar nav a:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .sidebar nav a:hover i { transform: scale(1.12); }
        .sidebar nav a.active { background: var(--primary); color: #fff; }
        .sidebar .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            font-size: 11px;
            color: #475569;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* ---- Main ---- */
        .main { flex: 1; padding: 28px 32px; max-width: 100%; min-width: 0; animation: fadeInUp .35s ease; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
            flex-wrap: wrap;
        }
        .topbar .left-group { display: flex; align-items: center; gap: 14px; min-width: 0; }
        .topbar h2 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.01em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .topbar .user { display: flex; align-items: center; gap: 12px; font-size: 14px; color: var(--text-soft); }
        .topbar .lang-switch { display: flex; gap: 2px; background: #f1f2f8; border-radius: 8px; padding: 3px; }
        .topbar .lang-switch a {
            padding: 4px 9px; border-radius: 6px; font-size: 12px; font-weight: 600;
            text-decoration: none; color: var(--text-soft);
        }
        .topbar .lang-switch a.active { background: var(--card); color: var(--text); box-shadow: 0 1px 2px rgba(15,23,42,0.08); }
        .topbar .user .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: var(--primary-soft); color: var(--primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 13px; text-transform: uppercase;
        }
        .topbar .user .uname { font-weight: 600; color: var(--text); }
        .topbar a.logout {
            color: var(--red);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 7px 12px;
            border-radius: 8px;
            background: var(--red-soft);
            transition: filter .15s ease;
        }
        .topbar a.logout:hover { filter: brightness(0.96); }

        /* ---- Cards ---- */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
            margin-bottom: 22px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            transition: box-shadow .2s ease;
            animation: fadeIn .4s ease;
        }
        .card h3 { font-size: 16px; font-weight: 700; margin: 0 0 18px; letter-spacing: -0.01em; }

        /* ---- Table ---- */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; font-size: 13.5px; }
        th { color: var(--text-soft); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; background: transparent; white-space: nowrap; }
        tbody tr { transition: background .12s ease; }
        tbody tr:hover { background: #fafbff; }
        tr:last-child td { border-bottom: none; }

        /* ---- Buttons ---- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 16px;
            border-radius: 9px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: filter .15s ease, transform .05s ease;
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { filter: brightness(1.08); }
        .btn-danger { background: var(--red-soft); color: var(--red); }
        .btn-danger:hover { filter: brightness(0.96); }
        .btn-secondary { background: #f1f2f8; color: var(--text); }
        .btn-secondary:hover { filter: brightness(0.97); }

        /* ---- Badges ---- */
        .badge { padding: 4px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: 0.02em; text-transform: capitalize; }
        .badge-active { background: var(--green-soft); color: var(--green); }
        .badge-inactive { background: var(--red-soft); color: var(--red); }
        .badge-pending { background: var(--amber-soft); color: var(--amber); }

        form.inline { display: inline; }

        /* ---- Forms ---- */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--text); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            background: #fcfcfe;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-soft);
            background: #fff;
        }

        /* ---- Alerts ---- */
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; animation: fadeInUp .25s ease; }
        .alert-error { background: var(--red-soft); color: var(--red); }
        .alert-success { background: var(--green-soft); color: var(--green); }

        /* ---- Stat grid ---- */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 22px; }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: 0 1px 2px rgba(15,23,42,0.04);
        }
        .stat-card .label { font-size: 12.5px; color: var(--text-soft); font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
        .stat-card .value { font-size: 28px; font-weight: 800; margin-top: 8px; letter-spacing: -0.02em; }
        .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(15,23,42,0.08); }

        /* ════════════════════════════════════════════
           RESPONSIVE — Tablet & Mobile
           ════════════════════════════════════════════ */

        /* Tablet: slightly tighter spacing, sidebar still inline */
        @media (max-width: 1024px) {
            .main { padding: 22px; }
            .sidebar { width: 210px; }
        }

        /* Mobile/Tablet-portrait: sidebar becomes an off-canvas drawer */
        @media (max-width: 900px) {
            .hamburger-btn { display: flex; }

            .sidebar {
                position: fixed;
                top: 0; left: 0;
                height: 100vh;
                z-index: 1000;
                transform: translateX(-100%);
                box-shadow: 10px 0 40px rgba(0,0,0,0.35);
            }
            .sidebar.open { transform: translateX(0); }

            .main { padding: 16px; }
            .topbar { margin-bottom: 18px; }
            .topbar .uname { display: none; }
            .card { padding: 18px; border-radius: 12px; }
        }

        @media (max-width: 560px) {
            .topbar h2 { font-size: 18px; max-width: 55vw; }
            .card { padding: 14px; }
            .stat-grid { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
            .stat-card { padding: 14px; }
            .stat-card .value { font-size: 22px; }
            .btn { padding: 8px 12px; font-size: 12.5px; }
            th, td { padding: 9px 10px; font-size: 12.5px; }
        }

        /* Respect users who prefer less motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }
    </style>
</head>
<body>
<div class="layout">
    <div class="sidebar" id="sidebar">
        <div class="brand">
            <div class="logo">K</div>
            <div>
                <div class="name">KuzaPanel</div>
                <div class="sub">Bot Admin</div>
            </div>
        </div>
        <nav>
            <a href="index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> <?= t('nav.dashboard') ?></a>
            <a href="orders.php" class="<?= $activeNav === 'orders' ? 'active' : '' ?>"><i class="fa-solid fa-receipt"></i> <?= t('nav.orders') ?></a>
            <a href="customers.php" class="<?= $activeNav === 'customers' ? 'active' : '' ?>"><i class="fa-solid fa-users"></i> <?= t('nav.customers') ?></a>
            <a href="inbox.php" class="<?= $activeNav === 'inbox' ? 'active' : '' ?>"><i class="fa-solid fa-comments"></i> <?= t('nav.inbox') ?></a>
            <a href="broadcast.php" class="<?= $activeNav === 'broadcast' ? 'active' : '' ?>"><i class="fa-solid fa-bullhorn"></i> <?= t('nav.broadcast') ?></a>
            <a href="reports.php" class="<?= $activeNav === 'reports' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> <?= t('nav.reports') ?></a>
            <a href="providers.php" class="<?= $activeNav === 'providers' ? 'active' : '' ?>"><i class="fa-solid fa-server"></i> <?= t('nav.providers') ?></a>
            <a href="services.php" class="<?= $activeNav === 'services' ? 'active' : '' ?>"><i class="fa-solid fa-layer-group"></i> <?= t('nav.services') ?></a>
            <a href="settings.php" class="<?= $activeNav === 'settings' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> <?= t('nav.settings') ?></a>
        </nav>
        <div class="sidebar-footer">&copy; <?= date('Y') ?> KuzaPanel</div>
    </div>
    <div class="main">
        <div class="topbar">
            <div class="left-group">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Fungua menu" type="button"><i class="fa-solid fa-bars"></i></button>
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="user">
                <div class="lang-switch">
                    <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['lang' => 'sw']))) ?>" class="<?= Lang::current() === 'sw' ? 'active' : '' ?>">🇹🇿 SW</a>
                    <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['lang' => 'en']))) ?>" class="<?= Lang::current() === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
                </div>
                <div class="avatar"><?= htmlspecialchars(substr(Auth::user()['username'], 0, 1)) ?></div>
                <span class="uname"><?= htmlspecialchars(Auth::user()['username']) ?></span>
                <a class="logout" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> <?= t('nav.logout') ?></a>
            </div>
        </div>
