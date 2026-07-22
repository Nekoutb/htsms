package com.cmea.htsms.gateway

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.os.Build
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.SimRepository
import com.cmea.htsms.gateway.gateway.GatewayService
import com.cmea.htsms.gateway.network.GatewayApi
import com.cmea.htsms.gateway.security.CredentialVault
import com.google.zxing.client.android.Intents
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import java.util.Locale
import java.util.concurrent.Executors

class MainActivity : AppCompatActivity() {
    private lateinit var preferences: GatewayPreferences
    private lateinit var status: TextView
    private lateinit var detail: TextView
    private lateinit var code: EditText
    private lateinit var name: EditText
    private lateinit var scan: Button
    private lateinit var pair: Button
    private var pendingCode: String? = null
    private val executor = Executors.newSingleThreadExecutor()
    private val permissions = registerForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) {
        if (hasGatewayPermissions()) pendingCode?.let(::pairDevice) else detail.text = "Phone and SMS permissions are required for this phone to send and receive business messages."
    }
    private val notifications = registerForActivityResult(ActivityResultContracts.RequestPermission()) { render() }
    private val scanner = registerForActivityResult(ScanContract()) { result ->
        result.contents?.let(::acceptPairingValue) ?: run { detail.text = "Scan cancelled. You can scan again or enter the 8-character code." }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        preferences = GatewayPreferences(this)
        preferences.apiUrl = BuildConfig.DEFAULT_API_URL
        setContentView(content())
        scan.setOnClickListener { scanQrCode() }
        pair.setOnClickListener { acceptPairingValue(code.text.toString()) }
        render()
        handlePairingIntent(intent)
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        handlePairingIntent(intent)
    }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }

    private fun content(): LinearLayout {
        fun label(value: String) = TextView(this).apply { text = value; textSize = 12f; setTextColor(Color.rgb(90, 108, 100)); setPadding(0, 22, 0, 7) }
        return LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(48, 64, 48, 48)
            setBackgroundColor(Color.rgb(244, 242, 235))
            addView(TextView(context).apply { text = "HTSMS"; textSize = 28f; setTextColor(Color.rgb(16, 35, 29)); setTypeface(typeface, Typeface.BOLD) })
            addView(TextView(context).apply { textSize = 22f; gravity = Gravity.CENTER; setPadding(0, 48, 0, 10); setTextColor(Color.rgb(16, 35, 29)) }.also { status = it })
            addView(TextView(context).apply { textSize = 13f; gravity = Gravity.CENTER; setTextColor(Color.rgb(100, 115, 109)); setPadding(0, 0, 0, 22) }.also { detail = it })
            addView(Button(context).apply { text = "Scan QR code"; isAllCaps = false; setTextColor(Color.WHITE); setBackgroundColor(Color.rgb(16, 35, 29)); layoutParams = fullWidth(8) }.also { scan = it })
            addView(label("Or enter the 8-character code")); addView(input("ABCD2345").also { code = it })
            addView(label("Phone name")); addView(input("${Build.MANUFACTURER} ${Build.MODEL}").also { name = it })
            addView(Button(context).apply { text = "Connect securely"; isAllCaps = false; setTextColor(Color.WHITE); setBackgroundColor(Color.rgb(16, 35, 29)); layoutParams = fullWidth(22) }.also { pair = it })
            addView(TextView(context).apply { text = "Why permissions? Camera scans the QR. Phone identifies active SIMs. SMS sends and receives your business messages. Notifications keep the gateway visible while running. HTSMS uses HTTPS only and protects its credential with Android Keystore."; textSize = 10f; gravity = Gravity.CENTER; setTextColor(Color.rgb(100, 115, 109)); setPadding(10, 26, 10, 0) })
        }
    }

    private fun fullWidth(topMargin: Int) = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT).apply { this.topMargin = topMargin }

    private fun input(hintValue: String) = EditText(this).apply {
        hint = hintValue
        textSize = 14f
        setSingleLine()
        setPadding(18, 12, 18, 12)
        setBackgroundColor(Color.WHITE)
        layoutParams = fullWidth(0)
    }

    private fun scanQrCode() {
        scanner.launch(ScanOptions().apply {
            setDesiredBarcodeFormats(ScanOptions.QR_CODE)
            setPrompt("Scan the secure QR shown in your HTSMS Devices page")
            setBeepEnabled(false)
            setOrientationLocked(false)
            addExtra(Intents.Scan.SCAN_TYPE, Intents.Scan.MIXED_SCAN)
        })
    }

    private fun handlePairingIntent(intent: Intent) {
        if (intent.action != Intent.ACTION_VIEW || intent.data?.scheme != "htsms" || intent.data?.host != "pair") return
        intent.data?.getQueryParameter("code")?.let(::acceptPairingValue)
    }

    private fun acceptPairingValue(value: String) {
        val candidate = if (value.startsWith("htsms://")) android.net.Uri.parse(value).takeIf { it.scheme == "htsms" && it.host == "pair" }?.getQueryParameter("code") else value
        val normalized = candidate?.trim()?.uppercase(Locale.ROOT).orEmpty()
        if (!PAIRING_CODE.matches(normalized)) {
            detail.text = "That QR or code is not a valid HTSMS pairing request. Create a new secure QR in the Devices page."
            return
        }
        pendingCode = normalized
        if (hasGatewayPermissions()) pairDevice(normalized) else explainAndRequestPermissions()
    }

    private fun explainAndRequestPermissions() {
        AlertDialog.Builder(this)
            .setTitle("Allow gateway access")
            .setMessage("HTSMS needs Phone access to identify your SIM cards and SMS access to send and receive messages you authorize. It does not read contacts, photos, location, microphone, or call history.")
            .setPositiveButton("Continue") { _, _ -> permissions.launch(GATEWAY_PERMISSIONS) }
            .setNegativeButton("Not now", null)
            .show()
    }

    private fun pairDevice(pairingCode: String) {
        val deviceName = name.text.toString().trim().ifBlank { "${Build.MANUFACTURER} ${Build.MODEL}" }
        scan.isEnabled = false
        pair.isEnabled = false
        detail.text = "Connecting securely…"
        executor.execute {
            runCatching { GatewayApi(this).pair("htsms_pair_$pairingCode", deviceName) }
                .onSuccess { result ->
                    CredentialVault(this).store(result.credential)
                    preferences.deviceId = result.deviceId
                    pendingCode = null
                    runOnUiThread {
                        requestNotificationPermission()
                        startGateway()
                        render()
                    }
                }
                .onFailure { error -> runOnUiThread { scan.isEnabled = true; pair.isEnabled = true; detail.text = error.message ?: "Pairing failed. Create a new code and try again." } }
        }
    }

    private fun render() {
        val paired = preferences.isPaired && CredentialVault(this).read() != null
        status.text = if (paired) "Gateway connected" else "Connect this phone"
        val simCount = if (hasGatewayPermissions()) SimRepository(this).active().size else 0
        detail.text = if (paired) "$simCount active SIM${if (simCount == 1) "" else "s"} · background gateway enabled" else "Scan one QR code. No server address or long secret required."
        listOf(code, name, scan, pair).forEach { it.visibility = if (paired) View.GONE else View.VISIBLE }
        if (paired) startGateway()
    }

    private fun requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= 33 && ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) notifications.launch(Manifest.permission.POST_NOTIFICATIONS)
    }

    private fun startGateway() {
        if (hasGatewayPermissions()) ContextCompat.startForegroundService(this, Intent(this, GatewayService::class.java))
    }

    private fun hasGatewayPermissions(): Boolean = GATEWAY_PERMISSIONS.all { ContextCompat.checkSelfPermission(this, it) == PackageManager.PERMISSION_GRANTED }

    companion object {
        private val PAIRING_CODE = Regex("^[A-HJ-NP-Z2-9]{8}$")
        private val GATEWAY_PERMISSIONS = arrayOf(Manifest.permission.READ_PHONE_STATE, Manifest.permission.READ_PHONE_NUMBERS, Manifest.permission.SEND_SMS, Manifest.permission.RECEIVE_SMS)
    }
}
