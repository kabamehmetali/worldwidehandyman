/* Worldwide Handyman — admin panel scripts
   Kept in a file rather than in <script> blocks: the site's
   Content-Security-Policy (script-src 'self') does not run inline scripts. */
(function () {
    'use strict';

    /* Sidebar toggle (mobile) */
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('adminSidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('open')
                && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    /* Confirm before destructive actions */
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* Auto-dismiss alerts after 6s */
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && alert.isConnected) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 6000);
    });

    /* Live character counters (SEO forms) */
    document.querySelectorAll('[data-counter]').forEach(function (field) {
        var out = document.getElementById(field.getAttribute('data-counter'));
        if (!out) return;
        field.addEventListener('input', function () { out.textContent = field.value.length; });
    });

    /* Icon preview (service page form) */
    var icon = document.getElementById('s-icon');
    var preview = document.getElementById('icon-preview');
    if (icon && preview) {
        icon.addEventListener('input', function () { preview.className = icon.value; });
    }

    /* Repeatable rows — FAQs and process steps (SEO forms) */
    [['faq-rows', 'faq-add', '.faq-row', '.faq-remove'],
     ['step-rows', 'step-add', '.step-row', '.step-remove']].forEach(function (set) {
        var wrap = document.getElementById(set[0]);
        var add = document.getElementById(set[1]);
        if (!wrap || !add) return;
        add.addEventListener('click', function () {
            var row = wrap.querySelector(set[2]).cloneNode(true);
            row.querySelectorAll('input, textarea').forEach(function (f) { f.value = ''; });
            wrap.appendChild(row);
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest(set[3]);
            if (btn && wrap.querySelectorAll(set[2]).length > 1) { btn.closest(set[2]).remove(); }
        });
    });
}());
