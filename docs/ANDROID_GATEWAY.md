# Android gateway installation

HTSMS uses a business-owned Android phone and SIM as the carrier gateway. The baseline is Android 8.0 (API 26)+; launch certification still requires the agreed Samsung, Tecno, Infinix, and Xiaomi matrix with MTN and Orange Cameroon SIMs.

1. Download the signed APK only from the HTSMS portal.
2. Verify its SHA-256 checksum and signing-certificate fingerprint.
3. Install on a dedicated phone and allow Phone, SMS, Notification, and background-service permissions.
4. Disable vendor battery optimization for HTSMS Gateway.
5. In **Devices**, create a one-time code and enter it with the HTTPS server URL and phone name.
6. Confirm SIM, battery, connection, version, and online state in the portal.
7. Send a test to a business-owned number and confirm delivery.

Pairing codes expire in ten minutes and work once. Credentials use Android Keystore. Revocation is immediate. Inbound SMS is reconstructed, given a stable event ID, encrypted while offline, and uploaded idempotently.

The verified build gate is `gradlew testDebugUnitTest assembleDebug lintDebug assembleRelease`. Release output is intentionally unsigned: generate and protect the production signing key outside the repository, then record the signed APK checksum and signing-certificate fingerprint before distribution.

Do not use rooted phones, shared personal devices, or devices without screen lock. Never send credentials or pairing codes through ordinary chat/email.
