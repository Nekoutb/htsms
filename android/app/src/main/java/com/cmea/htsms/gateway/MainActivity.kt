package com.cmea.htsms.gateway

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.PowerManager
import android.provider.Settings
import android.text.format.DateUtils
import android.view.Gravity
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.ScrollView
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.SimRepository
import com.cmea.htsms.gateway.gateway.GatewayService
import com.cmea.htsms.gateway.network.GatewayApi
import com.cmea.htsms.gateway.network.GatewayApiException
import com.cmea.htsms.gateway.security.CredentialVault
import com.google.zxing.client.android.Intents
import com.google.android.material.switchmaterial.SwitchMaterial
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import java.io.IOException
import java.util.concurrent.Executors

class MainActivity : AppCompatActivity() {
    private lateinit var preferences: GatewayPreferences
    private lateinit var status: TextView
    private lateinit var detail: TextView
    private lateinit var code: EditText
    private lateinit var name: EditText
    private lateinit var scan: Button
    private lateinit var pair: Button
    private lateinit var codeLabel: TextView
    private lateinit var nameLabel: TextView
    private lateinit var permissionsNote: TextView
    private lateinit var dashboard: LinearLayout
    private var showingSettings = false
    private var pendingLink: PairingLink? = null
    private val executor = Executors.newSingleThreadExecutor()
    private val permissions = registerForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) {
        if (hasGatewayPermissions()) pendingLink?.let(::pairDevice)
        else detail.text = "Phone and SMS permissions are required for this phone to send and receive business messages."
    }
    private val notifications = registerForActivityResult(ActivityResultContracts.RequestPermission()) { render() }
    private val scanner = registerForActivityResult(ScanContract()) { result ->
        result.contents?.let(::acceptPairingValue) ?: run { detail.text = "Scan cancelled. You can scan again or enter the 8-character code." }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        preferences = GatewayPreferences(this)
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

    override fun onResume() { super.onResume(); render() }

    override fun onDestroy() { executor.shutdownNow(); super.onDestroy() }

    private fun content(): View {
        fun label(value: String) = TextView(this).apply { text = value; textSize = 12f; setTextColor(MUTED); setPadding(0, 22, 0, 7) }
        val root = LinearLayout(this).apply {
            orientation = LinearLayout.VERTICAL
            gravity = Gravity.CENTER_HORIZONTAL
            setPadding(38, 52, 38, 54)
            setBackgroundColor(BACKGROUND)
            addView(LinearLayout(context).apply {
                orientation = LinearLayout.VERTICAL
                val density = resources.displayMetrics.density
                fun bar(widthDp: Int, gapDp: Int) = View(context).apply {
                    background = rounded(RED, 2f)
                    layoutParams = LinearLayout.LayoutParams((widthDp * density).toInt(), (9 * density).toInt()).apply { topMargin = (gapDp * density).toInt() }
                }
                addView(bar(64, 0)); addView(bar(42, 5)); addView(bar(64, 5))
            })
            addView(TextView(context).apply { text = "ELITE ADVISORS"; textSize = 12f; letterSpacing = .18f; setTextColor(Color.WHITE); setTypeface(typeface, Typeface.BOLD); setPadding(0, 14, 0, 0) })
            addView(TextView(context).apply { text = "HTSMS GATEWAY"; textSize = 19f; letterSpacing = .08f; setTextColor(Color.WHITE); setTypeface(typeface, Typeface.BOLD); setPadding(0, 4, 0, 0) })
            addView(TextView(context).apply { textSize = 22f; gravity = Gravity.CENTER; setPadding(0, 32, 0, 10); setTextColor(Color.WHITE); setTypeface(typeface, Typeface.BOLD) }.also { status = it })
            addView(TextView(context).apply { textSize = 13f; gravity = Gravity.CENTER; setTextColor(MUTED); setPadding(0, 0, 0, 22) }.also { detail = it })
            addView(primaryButton("Scan QR code").also { scan = it })
            addView(label("Or enter the 8-character code").also { codeLabel = it }); addView(input("ABCD2345").also { code = it })
            addView(label("Phone name (optional)").also { nameLabel = it }); addView(input("${Build.MANUFACTURER} ${Build.MODEL}").also { name = it })
            addView(primaryButton("Connect securely").apply { layoutParams = fullWidth(22) }.also { pair = it })
            addView(LinearLayout(context).apply { orientation = LinearLayout.VERTICAL; layoutParams = fullWidth(6) }.also { dashboard = it })
            addView(TextView(context).apply { text = "Why permissions? Camera scans the QR. Phone identifies active SIMs. SMS sends and receives your business messages. Notifications keep the gateway visible while running. EA HTSMS uses HTTPS only and protects its credential with Android Keystore."; textSize = 10f; gravity = Gravity.CENTER; setTextColor(MUTED); setPadding(10, 26, 10, 0) }.also { permissionsNote = it })
        }
        return ScrollView(this).apply { isFillViewport = true; setBackgroundColor(BACKGROUND); addView(root) }
    }

    private fun fullWidth(topMargin: Int) = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT).apply { this.topMargin = topMargin }

    private fun input(hintValue: String) = EditText(this).apply {
        hint = hintValue
        textSize = 14f
        setSingleLine()
        setPadding(18, 12, 18, 12)
        setTextColor(Color.WHITE)
        setHintTextColor(MUTED)
        background = rounded(SURFACE, 12f)
        layoutParams = fullWidth(0)
    }

    private fun primaryButton(label: String) = Button(this).apply {
        text = label
        isAllCaps = false
        setTextColor(Color.WHITE)
        setTypeface(typeface, Typeface.BOLD)
        background = rounded(RED, 28f)
        layoutParams = fullWidth(8)
        minHeight = 54
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
        acceptPairingValue(intent.data.toString())
    }

    private fun acceptPairingValue(value: String) {
        val link = PairingLink.parse(value)
        if (link == null) {
            detail.text = "That QR or code is not a valid HTSMS pairing request. Create a new secure QR in the Devices page."
            return
        }
        pendingLink = link
        if (hasGatewayPermissions()) pairDevice(link) else explainAndRequestPermissions()
    }

    private fun explainAndRequestPermissions() {
        AlertDialog.Builder(this)
            .setTitle("Allow gateway access")
            .setMessage("HTSMS needs Phone access to identify your SIM cards and SMS access to send and receive messages you authorize. It does not read contacts, photos, location, microphone, or call history.")
            .setPositiveButton("Continue") { _, _ -> permissions.launch(GATEWAY_PERMISSIONS) }
            .setNegativeButton("Not now", null)
            .show()
    }

    private fun pairDevice(link: PairingLink) {
        link.host?.let { preferences.apiUrl = it }
        val deviceName = name.text.toString().trim().ifBlank { "${Build.MANUFACTURER} ${Build.MODEL}" }
        scan.isEnabled = false
        pair.isEnabled = false
        detail.text = "Connecting securely…"
        executor.execute {
            runCatching { GatewayApi(this).pair("htsms_pair_${link.code}", deviceName) }
                .onSuccess { result ->
                    CredentialVault(this).store(result.credential)
                    preferences.deviceId = result.deviceId
                    preferences.deviceName = deviceName
                    pendingLink = null
                    runOnUiThread {
                        scan.isEnabled = true
                        pair.isEnabled = true
                        requestNotificationPermission()
                        startGateway()
                        render()
                        offerBatteryProtection()
                    }
                }
                .onFailure { error ->
                    runOnUiThread {
                        scan.isEnabled = true
                        pair.isEnabled = true
                        detail.text = friendlyError(error)
                    }
                }
        }
    }

    private fun friendlyError(error: Throwable): String = when (error) {
        is GatewayApiException -> error.userMessage()
        is IOException -> "Could not reach the HTSMS server. Check this phone's internet connection and try again."
        else -> "Pairing failed. Create a new code in the Devices page and try again."
    }

    private fun render() {
        val paired = preferences.isPaired && CredentialVault(this).read() != null
        status.text = if (paired) "Gateway connected" else "Connect this phone"
        detail.text = if (paired) "This phone sends and receives messages for your workspace. Keep it charged and online."
        else "Scan the QR code from your HTSMS Devices page. No server address or long secret required."
        listOf(code, name, scan, pair, codeLabel, nameLabel, permissionsNote).forEach { it.visibility = if (paired) View.GONE else View.VISIBLE }
        dashboard.visibility = if (paired) View.VISIBLE else View.GONE
        if (paired) {
            if (showingSettings) renderSettings() else renderGatewayHome()
            startGateway()
        }
    }

    private fun renderLegacyDashboard(simCount: Int) {
        dashboard.removeAllViews()
        dashboard.addView(card().also { cardView ->
            cardView.addView(row("Phone", preferences.deviceName ?: "${Build.MANUFACTURER} ${Build.MODEL}"))
            cardView.addView(row("Server", preferences.apiUrl.removePrefix("https://")))
            cardView.addView(row("Active SIMs", if (simCount > 0) simCount.toString() else "None detected"))
            cardView.addView(row("Sent today", preferences.sentToday.toString()))
            cardView.addView(row("Last sync", lastSyncLabel()))
        })
        if (!hasGatewayPermissions()) {
            dashboard.addView(actionRow("Permissions missing — messages cannot send. Tap to fix.") { explainAndRequestPermissions() })
        }
        if (!isIgnoringBatteryOptimizations()) {
            dashboard.addView(actionRow("Battery optimization may stop the gateway at night. Tap to protect it.") { offerBatteryProtection() })
        }
        dashboard.addView(Button(this).apply {
            text = "Unpair this phone"
            isAllCaps = false
            setTextColor(Color.rgb(140, 45, 30))
            setBackgroundColor(Color.rgb(238, 232, 223))
            layoutParams = fullWidth(20)
            setOnClickListener { confirmUnpair() }
        })
    }

    private fun card() = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL
        setBackgroundColor(Color.WHITE)
        setPadding(30, 18, 30, 22)
        layoutParams = fullWidth(8)
    }

    private fun row(labelText: String, valueText: String): LinearLayout = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL
        setPadding(0, 10, 0, 10)
        addView(TextView(context).apply { text = labelText; textSize = 12f; setTextColor(Color.rgb(100, 115, 109)); layoutParams = LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f) })
        addView(TextView(context).apply { text = valueText; textSize = 12f; setTextColor(Color.rgb(16, 35, 29)); setTypeface(typeface, Typeface.BOLD); gravity = Gravity.END })
    }

    private fun actionRow(message: String, onTap: () -> Unit) = TextView(this).apply {
        text = message
        textSize = 12f
        setTextColor(Color.rgb(140, 92, 20))
        setBackgroundColor(Color.rgb(250, 243, 224))
        setPadding(26, 18, 26, 18)
        layoutParams = fullWidth(8)
        setOnClickListener { onTap() }
    }

    private fun renderGatewayHome() {
        dashboard.removeAllViews()
        status.text = "Gateway connected"
        detail.text = "Your SIMs are ready to send and receive business messages."
        val sims = if (hasGatewayPermissions()) SimRepository(this).active() else emptyList()
        if (sims.isEmpty()) dashboard.addView(actionRow("No active SIM detected. Tap to review permissions.") { explainAndRequestPermissions() })
        val simRepository = SimRepository(this)
        sims.forEach { sim ->
            dashboard.addView(gatewayCard().also { cardView ->
                cardView.addView(TextView(this).apply {
                    text = simRepository.numberOf(sim) ?: "SIM ${sim.simSlotIndex + 1} · Number unavailable"
                    textSize = 22f
                    setTextColor(Color.WHITE)
                    setTypeface(typeface, Typeface.BOLD)
                })
                cardView.addView(TextView(this).apply {
                    text = "${sim.carrierName ?: "Mobile network"} · ${lastSyncLabel()}"
                    textSize = 12f
                    setTextColor(MUTED)
                    setPadding(0, 8, 0, 0)
                })
                cardView.addView(TextView(this).apply {
                    val enabled = preferences.incomingEnabled(sim.simSlotIndex) && preferences.outgoingEnabled(sim.simSlotIndex)
                    text = if (enabled) "●  Incoming and outgoing enabled" else "●  Limited in App settings"
                    textSize = 11f
                    setTextColor(if (enabled) SUCCESS else WARNING)
                    setPadding(0, 12, 0, 0)
                })
            })
        }
        if (!isIgnoringBatteryOptimizations()) {
            dashboard.addView(primaryButton("Disable battery optimization").apply {
                setOnClickListener { offerBatteryProtection() }
            })
        }
        dashboard.addView(TextView(this).apply {
            text = "${preferences.apiUrl.removePrefix("https://")}  ·  ${preferences.deviceId?.takeLast(8)}"
            textSize = 11f
            gravity = Gravity.CENTER
            setTextColor(MUTED)
            setPadding(0, 20, 0, 40)
        })
        dashboard.addView(Button(this).apply {
            text = "⚙  App settings"
            isAllCaps = false
            setTextColor(Color.WHITE)
            setTypeface(typeface, Typeface.BOLD)
            background = rounded(Color.BLACK, 28f)
            layoutParams = fullWidth(8)
            minHeight = 54
            setOnClickListener { showingSettings = true; render() }
        })
    }

    private fun renderSettings() {
        dashboard.removeAllViews()
        status.text = "App settings"
        detail.text = "Choose how each SIM participates in your EA HTSMS gateway."
        dashboard.addView(Button(this).apply {
            text = "←  Back to gateway"
            isAllCaps = false
            setTextColor(Color.WHITE)
            gravity = Gravity.START or Gravity.CENTER_VERTICAL
            background = null
            setOnClickListener { showingSettings = false; render() }
        })
        val sims = if (hasGatewayPermissions()) SimRepository(this).active() else emptyList()
        val simRepository = SimRepository(this)
        sims.forEach { sim ->
            dashboard.addView(TextView(this).apply {
                text = "SIM ${sim.simSlotIndex + 1}"
                textSize = 11f
                setTextColor(RED)
                setTypeface(typeface, Typeface.BOLD)
                setPadding(0, 24, 0, 6)
            })
            dashboard.addView(gatewayCard().also { cardView ->
                cardView.addView(settingsRow("Phone number", simRepository.numberOf(sim) ?: "Unavailable"))
                cardView.addView(settingsRow("Network", sim.carrierName?.toString() ?: "Unknown"))
            })
            dashboard.addView(settingSwitch("Enable outgoing messages", preferences.outgoingEnabled(sim.simSlotIndex)) {
                preferences.setOutgoingEnabled(sim.simSlotIndex, it)
            })
            dashboard.addView(settingSwitch("Enable incoming messages", preferences.incomingEnabled(sim.simSlotIndex)) {
                preferences.setIncomingEnabled(sim.simSlotIndex, it)
            })
        }
        dashboard.addView(settingSwitch("Enable diagnostic logs", preferences.debugLogsEnabled) {
            preferences.debugLogsEnabled = it
        }.apply { setPadding(0, 28, 0, 10) })
        dashboard.addView(TextView(this).apply {
            text = "Diagnostic logs never include message content, phone numbers or credentials."
            textSize = 10f
            setTextColor(MUTED)
            setPadding(0, 0, 0, 24)
        })
        dashboard.addView(Button(this).apply {
            text = "↪  Log out and unpair"
            isAllCaps = false
            setTextColor(Color.WHITE)
            background = null
            setTypeface(typeface, Typeface.BOLD)
            layoutParams = fullWidth(18)
            setOnClickListener { confirmUnpair() }
        })
    }

    private fun gatewayCard() = LinearLayout(this).apply {
        orientation = LinearLayout.VERTICAL
        background = rounded(SURFACE, 14f)
        setPadding(28, 22, 28, 22)
        layoutParams = fullWidth(10)
    }

    private fun settingsRow(labelText: String, valueText: String): LinearLayout = LinearLayout(this).apply {
        orientation = LinearLayout.HORIZONTAL
        setPadding(0, 10, 0, 10)
        addView(TextView(context).apply { text = labelText; textSize = 12f; setTextColor(MUTED); layoutParams = LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f) })
        addView(TextView(context).apply { text = valueText; textSize = 12f; setTextColor(Color.WHITE); setTypeface(typeface, Typeface.BOLD); gravity = Gravity.END })
    }

    private fun settingSwitch(label: String, checked: Boolean, onChanged: (Boolean) -> Unit) = SwitchMaterial(this).apply {
        text = label
        textSize = 15f
        setTextColor(Color.WHITE)
        isChecked = checked
        setPadding(0, 12, 0, 12)
        setOnCheckedChangeListener { _, enabled -> onChanged(enabled) }
    }

    private fun rounded(color: Int, radiusDp: Float) = GradientDrawable().apply {
        setColor(color)
        cornerRadius = radiusDp * resources.displayMetrics.density
    }

    private fun lastSyncLabel(): String {
        val syncedAt = preferences.lastSyncAt
        if (syncedAt == 0L) return "Starting…"
        return DateUtils.getRelativeTimeSpanString(syncedAt, System.currentTimeMillis(), DateUtils.SECOND_IN_MILLIS).toString()
    }

    private fun isIgnoringBatteryOptimizations(): Boolean =
        getSystemService(PowerManager::class.java)?.isIgnoringBatteryOptimizations(packageName) ?: true

    private fun offerBatteryProtection() {
        if (isIgnoringBatteryOptimizations()) return
        AlertDialog.Builder(this)
            .setTitle("Keep the gateway alive")
            .setMessage("Android battery optimization can silently stop background apps. Allow HTSMS Gateway to run unrestricted so messages keep sending while the screen is off.")
            .setPositiveButton("Allow") { _, _ ->
                runCatching {
                    startActivity(Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS, Uri.parse("package:$packageName")))
                }.onFailure {
                    runCatching { startActivity(Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)) }
                }
            }
            .setNegativeButton("Later", null)
            .show()
    }

    private fun confirmUnpair() {
        AlertDialog.Builder(this)
            .setTitle("Unpair this phone?")
            .setMessage("The gateway stops immediately and the stored credential is deleted from this phone. Also revoke the device in your HTSMS Devices page to disable its credential on the server.")
            .setPositiveButton("Unpair") { _, _ -> unpair() }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun unpair() {
        stopService(Intent(this, GatewayService::class.java))
        CredentialVault(this).clear()
        preferences.clearPairing()
        pendingLink = null
        render()
        detail.text = "Phone unpaired. Scan a new QR code to connect it again."
    }

    private fun requestNotificationPermission() {
        if (Build.VERSION.SDK_INT >= 33 && ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS) != PackageManager.PERMISSION_GRANTED) notifications.launch(Manifest.permission.POST_NOTIFICATIONS)
    }

    private fun startGateway() {
        if (hasGatewayPermissions()) ContextCompat.startForegroundService(this, Intent(this, GatewayService::class.java))
    }

    private fun hasGatewayPermissions(): Boolean = GATEWAY_PERMISSIONS.all { ContextCompat.checkSelfPermission(this, it) == PackageManager.PERMISSION_GRANTED }

    companion object {
        private val BACKGROUND = Color.rgb(17, 17, 17)
        private val SURFACE = Color.rgb(34, 37, 43)
        private val MUTED = Color.rgb(164, 168, 177)
        private val RED = Color.rgb(226, 56, 43)
        private val SUCCESS = Color.rgb(129, 184, 98)
        private val WARNING = Color.rgb(220, 54, 96)
        private val GATEWAY_PERMISSIONS = arrayOf(Manifest.permission.READ_PHONE_STATE, Manifest.permission.READ_PHONE_NUMBERS, Manifest.permission.SEND_SMS, Manifest.permission.RECEIVE_SMS)
    }
}
