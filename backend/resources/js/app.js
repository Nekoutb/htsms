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
