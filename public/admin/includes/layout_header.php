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
            --sb-bg: #ffffff;
            --sb-text: #64748b;
            --sb-text-hover: #0f172a;
            --sb-hover-bg: #f6f7fb;
            --sb-active-bg: #eef2ff;
            --sb-border: #eceef5;
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
        .layout { display: flex; min-height: 100vh; align-items: flex-start; }

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

        /* ---- Sidebar (light/clean — Linear/Notion style) ---- */
        .sidebar {
            width: 248px;
            background: var(--sb-bg);
            color: var(--text);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            padding: 0;
            border-right: 1px solid var(--sb-border);
            transition: transform .3s cubic-bezier(.4,0,.2,1);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow: hidden;
        }
        .sidebar .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 20px 18px;
        }
        .sidebar .brand .logo {
            width: 32px; height: 32px;
            border-radius: 9px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .sidebar .brand .name { font-size: 14.5px; font-weight: 700; color: var(--text); line-height: 1.2; letter-spacing: -0.01em; }
        .sidebar .brand .sub { font-size: 11px; color: var(--text-soft); }
        .sidebar nav {
            padding: 10px 12px;
            display: flex;
            flex-direction: column;
            gap: 1px;
            overflow-y: auto;
            flex: 1;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 9px 12px;
            color: var(--sb-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-radius: 8px;
            transition: background .13s ease, color .13s ease;
        }
        .sidebar nav a svg {
            width: 18px; height: 18px; flex-shrink: 0;
            color: #b0b7c6;
            transition: color .13s ease;
        }
        .sidebar nav a:hover { background: var(--sb-hover-bg); color: var(--sb-text-hover); }
        .sidebar nav a:hover svg { color: var(--text-soft); }
        .sidebar nav a.active { background: var(--sb-active-bg); color: var(--primary); font-weight: 600; }
        .sidebar nav a.active svg { color: var(--primary); }
        .sidebar .sidebar-footer {
            padding: 14px 20px;
            font-size: 11px;
            color: #b0b7c6;
            border-top: 1px solid var(--sb-border);
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

        /* ---- Mini stats (compact icon+number+label row, e.g. services/providers/health pages) ---- */
        .mini-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .mini-stat { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
        .mini-stat .icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
        .mini-stat .icon.total { background: var(--primary-soft); color: var(--primary); }
        .mini-stat .icon.active { background: var(--green-soft); color: var(--green); }
        .mini-stat .icon.inactive { background: var(--red-soft); color: var(--red); }
        .mini-stat .icon.info,
        .mini-stat .icon.platforms { background: var(--amber-soft); color: var(--amber); }
        .mini-stat .num { font-size: 20px; font-weight: 800; line-height: 1.1; }
        .mini-stat .lbl { font-size: 12px; color: var(--text-soft); font-weight: 600; }

        /* ---- Toolbar (search + filters row) ---- */
        .toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 18px; }
        .toolbar input[type=text], .toolbar select {
            padding: 10px 12px; border: 1px solid var(--border); border-radius: 9px;
            font-size: 14px; font-family: inherit; background: #fcfcfe;
        }
        .toolbar input[type=text] { flex: 1; min-width: 180px; }
        .toolbar .spacer { flex: 1; }

        /* ---- Modal ---- */
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(15,23,42,0.55);
            display: none; align-items: flex-start; justify-content: center;
            z-index: 2000; padding: 40px 16px; overflow-y: auto;
        }
        .modal-backdrop.open { display: flex; }
        .modal-box {
            background: var(--card); border-radius: var(--radius); width: 100%; max-width: 640px;
            box-shadow: 0 20px 60px rgba(15,23,42,0.25); animation: fadeInUp .25s ease;
        }
        .modal-head { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .modal-head h3 { margin: 0; font-size: 17px; font-weight: 700; }
        .modal-close { background: #f1f2f8; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; color: var(--text-soft); font-size: 14px; }
        .modal-close:hover { background: var(--red-soft); color: var(--red); }
        .modal-body { padding: 22px 24px; max-height: 70vh; overflow-y: auto; }
        .modal-foot { padding: 16px 24px; border-top: 1px solid var(--border); display: flex; gap: 10px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        .form-grid .form-group.full { grid-column: 1 / -1; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }

        /* ---- Item card grid (services/providers/etc.) ---- */
        .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
        .item-card {
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 14px;
            display: flex; flex-direction: column; gap: 8px; transition: box-shadow .15s ease, border-color .15s ease;
            position: relative;
        }
        .item-card:hover { box-shadow: 0 4px 14px rgba(15,23,42,0.08); border-color: #d8dcf0; }
        .item-card.selected { border-color: var(--primary); box-shadow: 0 0 0 2px var(--primary-soft); }
        .item-card.inactive { opacity: .62; }
        .item-card-top { display: flex; align-items: flex-start; gap: 8px; }
        .item-card-check { margin-top: 2px; flex-shrink: 0; width: 16px; height: 16px; cursor: pointer; }
        .item-card-title { font-size: 13.5px; font-weight: 700; line-height: 1.35; flex: 1; word-break: break-word; }
        .item-card-meta { display: flex; flex-wrap: wrap; gap: 6px; font-size: 11.5px; color: var(--text-soft); }
        .item-card-meta span { background: #f5f6fb; padding: 2px 8px; border-radius: 6px; font-weight: 600; }
        .item-card-price { font-size: 15px; font-weight: 800; color: var(--primary); }
        .item-card-price small { font-size: 11px; color: var(--text-soft); font-weight: 600; }
        .item-card-bottom { display: flex; align-items: center; justify-content: space-between; margin-top: auto; padding-top: 6px; }
        .item-card-actions { display: flex; gap: 6px; }
        .item-card-actions button, .item-card-actions a {
            border: none; background: #f1f2f8; color: var(--text-soft); width: 28px; height: 28px;
            border-radius: 7px; display: flex; align-items: center; justify-content: center; cursor: pointer;
            font-size: 12px; text-decoration: none;
        }
        .item-card-actions a:hover { background: var(--primary-soft); color: var(--primary); }
        .item-card-actions .del:hover { background: var(--red-soft); color: var(--red); }
        .item-card-actions button:disabled { opacity: .3; cursor: default; }

        /* ---- Bulk action bar ---- */
        .bulk-bar {
            position: sticky; bottom: 14px; z-index: 50; margin-top: 16px;
            background: var(--sidebar-bg); color: #fff; border-radius: 12px; padding: 12px 18px;
            display: none; align-items: center; gap: 14px; flex-wrap: wrap;
            box-shadow: 0 10px 30px rgba(15,23,42,0.3);
        }
        .bulk-bar.show { display: flex; }
        .bulk-bar .count { font-weight: 700; font-size: 13.5px; }
        .bulk-bar .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-left: auto; }
        .bulk-bar button { border: none; padding: 8px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; }
        .bulk-bar .b-activate { background: var(--green-soft); color: var(--green); }
        .bulk-bar .b-deactivate { background: var(--amber-soft); color: var(--amber); }
        .bulk-bar .b-price { background: var(--primary-soft); color: var(--primary); }
        .bulk-bar .b-delete { background: var(--red-soft); color: var(--red); }
        .bulk-bar .b-clear { background: rgba(255,255,255,0.12); color: #fff; }

        #noResultsMsg { display: none; color: var(--text-soft); text-align: center; padding: 30px 0; }

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
        <?php
            // Lucide-style line icons (stroke="currentColor") — matches the
            // sidebar nav link's own color instead of Font Awesome's fixed
            // glyph weight, so hover/active states recolor the icon for free.
            $navIcons = [
                'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
                'orders' => '<path d="M6 3h9l3 3v15a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M15 3v3a1 1 0 0 0 1 1h3"/><path d="M9 12h6"/><path d="M9 16h6"/>',
                'customers' => '<circle cx="9" cy="8" r="3.25"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 8.5a3.25 3.25 0 1 1 3 3.24"/><path d="M15.5 14.5c3 .3 5 2 5.5 5.5"/>',
                'inbox' => '<path d="M3.5 12h4.2l1.4 3h5.8l1.4-3h4.2"/><path d="M5.2 6.2 3.5 12v6a1.3 1.3 0 0 0 1.3 1.3h14.4A1.3 1.3 0 0 0 20.5 18v-6l-1.7-5.8A1.5 1.5 0 0 0 17.4 5H6.6a1.5 1.5 0 0 0-1.4 1.2Z"/>',
                'sessions' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
                'health' => '<path d="M3.5 12h3.2l2-5 3.4 10 2.3-7 1.6 2h4"/>',
                'broadcast' => '<path d="M4.5 9.5v5a1 1 0 0 0 1 1h1.4l5 3.3V5.2l-5 3.3H5.5a1 1 0 0 0-1 1Z"/><path d="M15.5 9.5a3.4 3.4 0 0 1 0 5"/><path d="M18 7.5a6.5 6.5 0 0 1 0 9"/>',
                'reports' => '<path d="M4 20V10"/><path d="M11 20V4"/><path d="M18 20v-7"/><path d="M3 20h18"/>',
                'providers' => '<rect x="3.5" y="4" width="17" height="6" rx="1.5"/><rect x="3.5" y="14" width="17" height="6" rx="1.5"/><circle cx="7.5" cy="7" r=".75" fill="currentColor" stroke="none"/><circle cx="7.5" cy="17" r=".75" fill="currentColor" stroke="none"/>',
                'services' => '<path d="m12 3 8.5 4.8v8.4L12 21l-8.5-4.8V7.8Z"/><path d="M12 3v9"/><path d="m3.5 7.8 8.5 4.9 8.5-4.9"/>',
                'settings' => '<circle cx="12" cy="12" r="2.75"/><path d="M12 4.5v2M12 17.5v2M6.3 6.3l1.4 1.4M16.3 16.3l1.4 1.4M4.5 12h2M17.5 12h2M6.3 17.7l1.4-1.4M16.3 7.7l1.4-1.4"/>',
            ];

            function navIcon(string $key, array $icons): string
            {
                $paths = $icons[$key] ?? '';

                return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
            }
        ?>
        <nav>
            <a href="index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>"><?= navIcon('dashboard', $navIcons) ?> <?= t('nav.dashboard') ?></a>
            <a href="orders.php" class="<?= $activeNav === 'orders' ? 'active' : '' ?>"><?= navIcon('orders', $navIcons) ?> <?= t('nav.orders') ?></a>
            <a href="customers.php" class="<?= $activeNav === 'customers' ? 'active' : '' ?>"><?= navIcon('customers', $navIcons) ?> <?= t('nav.customers') ?></a>
            <a href="inbox.php" class="<?= $activeNav === 'inbox' ? 'active' : '' ?>"><?= navIcon('inbox', $navIcons) ?> <?= t('nav.inbox') ?></a>
            <a href="sessions.php" class="<?= $activeNav === 'sessions' ? 'active' : '' ?>"><?= navIcon('sessions', $navIcons) ?> <?= t('nav.sessions') ?></a>
            <a href="health.php" class="<?= $activeNav === 'health' ? 'active' : '' ?>"><?= navIcon('health', $navIcons) ?> <?= t('nav.health') ?></a>
            <a href="broadcast.php" class="<?= $activeNav === 'broadcast' ? 'active' : '' ?>"><?= navIcon('broadcast', $navIcons) ?> <?= t('nav.broadcast') ?></a>
            <a href="reports.php" class="<?= $activeNav === 'reports' ? 'active' : '' ?>"><?= navIcon('reports', $navIcons) ?> <?= t('nav.reports') ?></a>
            <a href="providers.php" class="<?= $activeNav === 'providers' ? 'active' : '' ?>"><?= navIcon('providers', $navIcons) ?> <?= t('nav.providers') ?></a>
            <a href="services.php" class="<?= $activeNav === 'services' ? 'active' : '' ?>"><?= navIcon('services', $navIcons) ?> <?= t('nav.services') ?></a>
            <a href="settings.php" class="<?= $activeNav === 'settings' ? 'active' : '' ?>"><?= navIcon('settings', $navIcons) ?> <?= t('nav.settings') ?></a>
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
