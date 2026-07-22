# HTSMS Android Gateway

Native Kotlin gateway that pairs an Android phone and its SIM cards with an HTSMS workspace. The app stores the device credential with Android Keystore, maintains a foreground connection, leases tenant-scoped work, sends through `SmsManager`, and reports sent/delivered/failure states.

## Requirements

- Android Studio Ladybug or newer
- JDK 17
- Android SDK 35
- Android 8.0+ physical phone with an active SIM

## Development build

1. Open the `android` directory in Android Studio.
2. Sync Gradle and build the `debug` variant.
3. Install on a physical phone and grant Phone, SMS, and Notification permissions.
4. In the HTSMS web dashboard, open **Devices** and create a secure pairing QR.
5. Tap **Scan QR code** in the app and scan it. If needed, enter the displayed 8-character fallback code.

The production server is built into the signed app, so customers cannot accidentally connect to an insecure or mistyped endpoint. Pairing QR codes expire after 10 minutes and can be redeemed only once.

The release build disables cleartext traffic. Use an HTTPS staging endpoint for real-device testing.

## Security notes

- Never distribute a debug APK to customers.
- Generate a dedicated upload/release key outside the repository.
- Configure release signing through local/CI secrets, never `gradle.properties` in source control.
- A revoked device credential is rejected immediately by the backend.
- The app does not log device credentials or SMS message content.
