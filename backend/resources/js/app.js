import './bootstrap';
import QRCode from 'qrcode';
import { localizePage } from './translations';

localizePage();

/* --- Theme: follow system by default, allow a persisted manual override ---- */
const THEME_KEY = 'htsms-theme';
const applyTheme = (value) => {
    if (value === 'light' || value === 'dark') {
        document.documentElement.setAttribute('data-theme', value);
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
};
applyTheme(localStorage.getItem(THEME_KEY));
document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const current = document.documentElement.getAttribute('data-theme') || (prefersDark ? 'dark' : 'light');
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_KEY, next);
        applyTheme(next);
    });
});

/* --- Mobile navigation drawer --------------------------------------------- */
const setNav = (open) => {
    document.body.classList.toggle('nav-open', open);
    document.querySelectorAll('[data-nav-toggle]').forEach((b) => b.setAttribute('aria-expanded', String(open)));
};
document.querySelectorAll('[data-nav-toggle]').forEach((b) => b.addEventListener('click', () => setNav(!document.body.classList.contains('nav-open'))));
document.querySelectorAll('[data-nav-close]').forEach((b) => b.addEventListener('click', () => setNav(false)));
document.querySelectorAll('.side-nav a').forEach((a) => a.addEventListener('click', () => setNav(false)));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setNav(false); });

/* --- Flash toasts: manual close + auto-dismiss ---------------------------- */
const dismissToast = (toast) => {
    toast.classList.add('leaving');
    toast.addEventListener('animationend', () => toast.remove(), { once: true });
    window.setTimeout(() => toast.remove(), 400);
};
document.querySelectorAll('.toast').forEach((toast) => {
    toast.querySelector('[data-toast-close]')?.addEventListener('click', () => dismissToast(toast));
    window.setTimeout(() => dismissToast(toast), 6000);
});

/* --- Confirm-on-submit (CSP-safe replacement for inline onsubmit) ---------- */
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

/* --- Reveal a masked value (e.g. SIM phone number) ------------------------ */
document.querySelectorAll('[data-reveal]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.querySelector(button.dataset.reveal);
        if (! target) return;
        const shown = target.dataset.shown === '1';
        target.textContent = shown ? target.dataset.mask : target.dataset.value;
        target.dataset.shown = shown ? '0' : '1';
        button.textContent = shown ? button.dataset.showLabel || 'Reveal' : button.dataset.hideLabel || 'Hide';
    });
});

/* --- Copy-to-clipboard ---------------------------------------------------- */
document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.querySelector(button.dataset.copy);
        if (! target) return;
        try {
            await navigator.clipboard.writeText(target.textContent.trim());
            const original = button.textContent;
            button.textContent = button.dataset.copiedLabel || 'Copied';
            window.setTimeout(() => { button.textContent = original; }, 1800);
        } catch (_) { /* clipboard unavailable; leave the value visible to copy manually */ }
    });
});

/* --- Reveal masked message numbers (messages table) ------------------------ */
document.querySelectorAll('[data-reveal-number]').forEach((button) => {
    button.addEventListener('click', () => {
        const number = button.parentElement?.querySelector('[data-private-number]');
        if (! number) return;
        const revealing = button.dataset.revealed !== 'true';
        if (! number.dataset.masked) number.dataset.masked = number.textContent;
        number.textContent = revealing ? number.dataset.value : number.dataset.masked;
        button.dataset.revealed = revealing ? 'true' : 'false';
        button.textContent = revealing
            ? (document.documentElement.lang === 'fr' ? 'Masquer' : 'Hide')
            : (document.documentElement.lang === 'fr' ? 'Afficher' : 'Reveal');
    });
});

/* --- Pairing QR ----------------------------------------------------------- */
document.querySelectorAll('[data-pairing-qr]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.pairingQr, {
        width: 220,
        margin: 2,
        color: { dark: '#0c1f18', light: '#ffffff' },
        errorCorrectionLevel: 'M',
    }).catch(() => {
        canvas.replaceWith('QR unavailable. Use the pairing code instead.');
    });
});
