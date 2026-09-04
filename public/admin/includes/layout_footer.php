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

// WhatsApp character-limit counters — any input/textarea with
// data-maxlen="N" gets a live "X/N herufi" hint below it that turns amber
// past 80% and red once over the limit, so an admin typing a service name
// or button label can see the WhatsApp truncation point before saving.
(function () {
    document.querySelectorAll('[data-maxlen]').forEach(function (field) {
        var max = parseInt(field.getAttribute('data-maxlen'), 10);
        if (!max) return;

        var counter = document.createElement('span');
        counter.className = 'char-counter';
        field.insertAdjacentElement('afterend', counter);

        function update() {
            var len = field.value.length;
            counter.textContent = len + '/' + max + ' herufi';
            counter.classList.toggle('warn', len > max * 0.8 && len <= max);
            counter.classList.toggle('over', len > max);
        }

        field.addEventListener('input', update);
        update();
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
