/* Worldwide Handyman — site scripts */
(function () {
    'use strict';

    /* Navbar shrink on scroll */
    const navbar = document.getElementById('siteNavbar');
    const backToTop = document.getElementById('backToTop');
    function onScroll() {
        const y = window.scrollY;
        if (navbar) navbar.classList.toggle('scrolled', y > 60);
        if (backToTop) backToTop.classList.toggle('show', y > 500);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (backToTop) {
        backToTop.addEventListener('click', function (e) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    /* Full-screen mobile navigation */
    const burger = document.getElementById('navBurger');
    const navPanel = document.getElementById('mainNav');
    if (burger && navPanel) {
        const body = document.body;

        const isOpen = function () { return body.classList.contains('nav-open'); };

        const focusables = function () {
            return Array.prototype.filter.call(
                navPanel.querySelectorAll('a[href], button:not([disabled])'),
                function (el) { return el.offsetParent !== null; }
            );
        };

        function openNav() {
            body.classList.add('nav-open');
            burger.setAttribute('aria-expanded', 'true');
            burger.setAttribute('aria-label', 'Close menu');
            const first = focusables()[0];
            if (first) {
                window.setTimeout(function () { first.focus({ preventScroll: true }); }, 260);
            }
        }

        function closeNav(returnFocus) {
            body.classList.remove('nav-open');
            burger.setAttribute('aria-expanded', 'false');
            burger.setAttribute('aria-label', 'Open menu');
            // The burger is the disclosure trigger — send focus back to it
            if (returnFocus) {
                burger.focus({ preventScroll: true });
            }
        }

        burger.addEventListener('click', function () {
            isOpen() ? closeNav(true) : openNav();
        });

        // Tapping a link navigates — close so the panel never lingers
        navPanel.addEventListener('click', function (e) {
            if (isOpen() && e.target.closest('a[href]')) {
                closeNav(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (!isOpen()) return;
            if (e.key === 'Escape') {
                closeNav(true);
                return;
            }
            if (e.key !== 'Tab') return;
            // Keep focus inside the panel while it is open
            const items = focusables();
            items.unshift(burger);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });

        // Never leave the overlay state behind when rotating to a wide viewport
        let resizeTimer = null;
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () {
                if (isOpen() && window.innerWidth >= 992) {
                    closeNav(false);
                }
            }, 120);
        });
    }

    /* Reveal on scroll */
    const revealEls = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && revealEls.length) {
        const io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealEls.forEach(function (el) { io.observe(el); });
    } else {
        revealEls.forEach(function (el) { el.classList.add('visible'); });
    }

    /* Animated counters */
    const counters = document.querySelectorAll('[data-count]');
    if ('IntersectionObserver' in window && counters.length) {
        const cio = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                cio.unobserve(el);
                const target = parseInt(el.getAttribute('data-count'), 10) || 0;
                const suffix = el.getAttribute('data-suffix') || '';
                const duration = 1600;
                const start = performance.now();
                function tick(now) {
                    const p = Math.min((now - start) / duration, 1);
                    const eased = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(target * eased).toLocaleString() + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) { cio.observe(el); });
    }

    /* Gallery lightbox */
    const galleryItems = Array.prototype.slice.call(document.querySelectorAll('.gallery-item'));
    if (galleryItems.length) {
        const lightbox = document.createElement('div');
        lightbox.className = 'ww-lightbox';
        lightbox.innerHTML =
            '<button class="lb-btn lb-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
            '<button class="lb-btn lb-prev" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>' +
            '<img src="" alt="">' +
            '<button class="lb-btn lb-next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>' +
            '<div class="lb-caption"></div>';
        document.body.appendChild(lightbox);

        const lbImg = lightbox.querySelector('img');
        const lbCaption = lightbox.querySelector('.lb-caption');
        let current = 0;

        function show(index) {
            current = (index + galleryItems.length) % galleryItems.length;
            const item = galleryItems[current];
            lbImg.src = item.getAttribute('data-full') || item.querySelector('img').src;
            const title = item.getAttribute('data-title') || '';
            lbCaption.textContent = title;
            lbImg.alt = title;
        }
        function open(index) {
            show(index);
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function close() {
            lightbox.classList.remove('open');
            document.body.style.overflow = '';
        }

        galleryItems.forEach(function (item, i) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                open(i);
            });
        });
        lightbox.querySelector('.lb-close').addEventListener('click', close);
        lightbox.querySelector('.lb-prev').addEventListener('click', function () { show(current - 1); });
        lightbox.querySelector('.lb-next').addEventListener('click', function () { show(current + 1); });
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) close();
        });
        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'Escape') close();
            if (e.key === 'ArrowLeft') show(current - 1);
            if (e.key === 'ArrowRight') show(current + 1);
        });
    }

    /* Bootstrap client-side validation */
    document.querySelectorAll('form.needs-validation').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
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
}());
