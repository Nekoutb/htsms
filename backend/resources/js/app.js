import './bootstrap';
import QRCode from 'qrcode';
import { localizePage } from './translations';

localizePage();

/* --- Mobile navigation drawer ---------------------------------------------
   Drives the marketing header nav and the portal sidebar; both collapse into
   the same off-canvas panel below 720px (see .nav-toggle in app.css). */
const navPanel = () => document.querySelector('.site-header nav, .sidebar');
const setNav = (open) => {
    document.body.classList.toggle('nav-open', open);
    document.querySelectorAll('[data-nav-toggle]').forEach((b) => b.setAttribute('aria-expanded', String(open)));
    document.querySelectorAll('.nav-scrim').forEach((s) => { s.hidden = ! open; });
    if (open) navPanel()?.querySelector('a, button')?.focus();
};
document.querySelectorAll('[data-nav-toggle]').forEach((b) => b.addEventListener('click', () => setNav(! document.body.classList.contains('nav-open'))));
document.querySelectorAll('[data-nav-close]').forEach((b) => b.addEventListener('click', () => setNav(false)));
document.querySelectorAll('.side-nav a, .site-header nav a').forEach((a) => a.addEventListener('click', () => setNav(false)));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setNav(false); });
window.matchMedia('(min-width: 721px)').addEventListener('change', (e) => { if (e.matches) setNav(false); });

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
