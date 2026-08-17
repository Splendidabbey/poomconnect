/**
 * Traps Tab/Shift+Tab focus within `container` while `isActive()` returns true,
 * and returns focus to `returnFocusEl` once the trap is released. Shared by the
 * main site nav drawer and the admin/organizer dashboard drawer.
 */
function createFocusTrap(container, returnFocusEl) {
    const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    let active = false;

    const handleKeydown = (e) => {
        if (!active || e.key !== 'Tab') {
            return;
        }
        const focusable = Array.from(container.querySelectorAll(focusableSelector)).filter((el) => el.offsetParent !== null);
        if (focusable.length === 0) {
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    };

    return {
        activate() {
            active = true;
            document.addEventListener('keydown', handleKeydown);
            const firstFocusable = container.querySelector(focusableSelector);
            firstFocusable?.focus();
        },
        deactivate() {
            active = false;
            document.removeEventListener('keydown', handleKeydown);
            returnFocusEl?.focus();
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const i18n = window.PoomI18n || {};
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navDrawer = document.querySelector('[data-nav-drawer]');
    const navOverlay = document.querySelector('[data-nav-overlay]');
    const navbar = document.querySelector('.navbar');
    let drawerHome = null;
    let overlayHome = null;
    const navFocusTrap = navDrawer && navToggle ? createFocusTrap(navDrawer, navToggle) : null;

    const isMobileNav = () => window.innerWidth <= 1024;

    const mountMobileNav = () => {
        if (!navDrawer || !isMobileNav()) {
            return;
        }
        if (!drawerHome) {
            drawerHome = navDrawer.parentElement;
        }
        if (navOverlay && !overlayHome) {
            overlayHome = navOverlay.parentElement;
        }
        if (navDrawer.parentElement !== document.body) {
            document.body.appendChild(navDrawer);
        }
        if (navOverlay && navOverlay.parentElement !== document.body) {
            document.body.appendChild(navOverlay);
        }
    };

    const restoreMobileNav = () => {
        if (drawerHome && navDrawer && navDrawer.parentElement === document.body) {
            drawerHome.appendChild(navDrawer);
        }
        if (overlayHome && navOverlay && navOverlay.parentElement === document.body) {
            overlayHome.appendChild(navOverlay);
        }
    };

    const setNavOpen = (open) => {
        if (!navDrawer || !navToggle) {
            return;
        }

        if (open && isMobileNav()) {
            mountMobileNav();
        }

        navDrawer.classList.toggle('is-open', open);
        navToggle.classList.toggle('is-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('nav-menu-open', open);
        navbar?.classList.toggle('is-menu-open', open);

        if (navOverlay) {
            navOverlay.classList.toggle('is-visible', open);
            navOverlay.hidden = !open;
        }

        if (!open) {
            document.querySelectorAll('details[data-nav-details][open]').forEach((details) => {
                details.open = false;
            });
        }

        if (open) {
            navFocusTrap?.activate();
        } else {
            navFocusTrap?.deactivate();
        }
    };

    if (navToggle && navDrawer) {
        navToggle.addEventListener('click', () => {
            setNavOpen(!navDrawer.classList.contains('is-open'));
        });

        navOverlay?.addEventListener('click', () => setNavOpen(false));

        navDrawer.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => {
                if (link.getAttribute('href') === '#') {
                    return;
                }
                setNavOpen(false);
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && navDrawer.classList.contains('is-open')) {
                setNavOpen(false);
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                if (navDrawer.classList.contains('is-open')) {
                    setNavOpen(false);
                }
                restoreMobileNav();
            }
        });
    }

    const adminToggle = document.querySelector('[data-admin-nav-toggle]');
    const adminSidebar = document.querySelector('[data-admin-sidebar]');
    const adminOverlay = document.querySelector('[data-admin-nav-overlay]');
    if (adminToggle && adminSidebar) {
        const adminFocusTrap = createFocusTrap(adminSidebar, adminToggle);

        const setAdminNavOpen = (open) => {
            adminSidebar.classList.toggle('is-open', open);
            adminToggle.classList.toggle('is-open', open);
            adminToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (adminOverlay) {
                adminOverlay.classList.toggle('is-open', open);
                adminOverlay.hidden = !open;
            }
            if (open) {
                adminFocusTrap.activate();
            } else {
                adminFocusTrap.deactivate();
            }
        };

        adminToggle.addEventListener('click', () => {
            setAdminNavOpen(!adminSidebar.classList.contains('is-open'));
        });
        adminOverlay?.addEventListener('click', () => setAdminNavOpen(false));
        adminSidebar.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => setAdminNavOpen(false));
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && adminSidebar.classList.contains('is-open')) {
                setAdminNavOpen(false);
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth > 900 && adminSidebar.classList.contains('is-open')) {
                setAdminNavOpen(false);
            }
        });
    }

    document.querySelectorAll('details[data-nav-details]').forEach((details) => {
        details.addEventListener('toggle', () => {
            if (!details.open) {
                return;
            }
            document.querySelectorAll('details[data-nav-details]').forEach((other) => {
                if (other !== details) {
                    other.open = false;
                }
            });
        });
    });

    document.addEventListener('click', (e) => {
        document.querySelectorAll('details[data-nav-details][open]').forEach((details) => {
            if (!details.contains(e.target)) {
                details.open = false;
            }
        });
    });

    const countdownEl = document.querySelector('[data-countdown]');
    if (countdownEl) {
        let seconds = parseInt(countdownEl.dataset.seconds || '300', 10);
        const update = () => {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            countdownEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            if (seconds > 0) {
                seconds--;
            }
        };
        update();
        setInterval(update, 1000);
    }

    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-loading]').forEach((form) => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = i18n.processing || 'Processing...';
            }
        });
    });
});

async function apiPost(url, data = {}) {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));
    if (window.PoomCsrfToken) {
        formData.append('_csrf', window.PoomCsrfToken);
    }

    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
    });

    return response.json();
}
