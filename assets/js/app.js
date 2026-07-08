document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const navMenu = document.querySelector('[data-nav-menu]');
    const navActions = document.querySelector('[data-nav-actions]');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            if (navActions) {
                navActions.classList.toggle('open');
            }
        });
    }

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
                btn.textContent = 'Processing...';
            }
        });
    });
});

async function apiPost(url, data = {}) {
    const formData = new FormData();
    Object.entries(data).forEach(([key, value]) => formData.append(key, value));

    const response = await fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
    });

    return response.json();
}
