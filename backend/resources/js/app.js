import './bootstrap';
import QRCode from 'qrcode';
import { localizePage } from './translations';

localizePage();

// CSP forbids inline handlers, so destructive forms declare data-confirm instead.
document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) event.preventDefault();
    });
});

document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const target = document.querySelector(button.dataset.copy);
        if (! target) return;
        await navigator.clipboard.writeText(target.textContent.trim());
        const original = button.textContent;
        button.textContent = 'Copied';
        window.setTimeout(() => { button.textContent = original; }, 1800);
    });
});

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

document.querySelectorAll('[data-pairing-qr]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.pairingQr, {
        width: 220,
        margin: 2,
        color: { dark: '#10231d', light: '#ffffff' },
        errorCorrectionLevel: 'M',
    }).catch(() => {
        canvas.replaceWith('QR unavailable. Use the pairing code instead.');
    });
});
