    </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script>
(function () {
    var sidebar = document.getElementById('sidebar');
    var btn = document.getElementById('hamburgerBtn');
    var overlay = document.getElementById('sidebarOverlay');

    if (!sidebar || !btn || !overlay) {
        return;
    }

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', function () {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    // Close the drawer automatically once a nav link is tapped (mobile UX).
    sidebar.querySelectorAll('nav a').forEach(function (link) {
        link.addEventListener('click', closeSidebar);
    });
})();

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
