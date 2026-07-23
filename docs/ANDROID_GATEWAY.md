# Android gateway installation

HTSMS uses a business-owned Android phone and SIM as the carrier gateway. The baseline is Android 8.0 (API 26)+; launch certification still requires the agreed Samsung, Tecno, Infinix, and Xiaomi matrix with MTN and Orange Cameroon SIMs.

1. Download the signed APK only from the HTSMS portal.
2. Verify its SHA-256 checksum and signing-certificate fingerprint.
3. Install on a dedicated phone, open the app, and tap **Scan QR code**. The app explains and requests the Phone, SMS, and Notification permissions it needs.
4. In **Devices**, click **Connect a phone** and scan the one-time QR. The QR carries the pairing code and the HTTPS server origin, so nothing is typed; an 8-character fallback code is shown when the camera is unavailable. The optional phone name defaults to the manufacturer and model.
5. When the app offers **Keep the gateway alive**, allow the battery-optimization exemption so vendor power managers do not stop background sending. The connected screen keeps showing a warning until the exemption is granted.
6. Confirm SIM, battery, connection, version, and online state in the portal, and confirm the app's connected screen shows the server, active SIMs, sent-today count, and last sync.
7. Send a test to a business-owned number and confirm delivery.

Pairing codes expire in ten minutes and work once. Credentials use Android Keystore. Revocation is immediate — from the portal (Devices → Revoke) or on the phone (**Unpair this phone**, which deletes the local credential and stops the service). After a reboot the gateway restarts automatically when paired; where Android 15+ forbids background restart, the app posts a tap-to-resume notification instead of failing silently. Inbound SMS is reconstructed, given a stable event ID, encrypted while offline, and uploaded idempotently.

The verified build gate is `gradlew testDebugUnitTest assembleDebug lintDebug assembleRelease`. Release output is intentionally unsigned: generate and protect the production signing key outside the repository, then record the signed APK checksum and signing-certificate fingerprint before distribution.

Do not use rooted phones, shared personal devices, or devices without screen lock. Never send credentials or pairing codes through ordinary chat/email.
