# HTSMS

HTSMS is a proprietary subscription platform that lets a business connect its own Android phones and SIM cards and use them as programmable SMS gateways.

## Status

Development began on 21 July 2026. The current focus is the clean-room product specification, backend foundation, multi-tenant security model, and device/message protocol.

## Repository layout

- `backend/` — Laravel 12 API, queue workers, administration, and billing foundation
- `docs/` — product, architecture, security, clean-room, and API documentation
- `HTSMS_PROJECT_TIMELINE.md` — dated delivery baseline and launch gates
- `index.html`, `styles.css`, `app.js` — existing web prototype assets

## Local backend

Requirements: PHP 8.3+, Composer 2.9+, and SQLite for the current development test environment.

```powershell
Set-Location backend
composer install
php artisan key:generate
php artisan test
```

Do not copy, adapt, translate, decompile, or use source code from httpSMS/httpsms. See `docs/CLEAN_ROOM_POLICY.md`.

