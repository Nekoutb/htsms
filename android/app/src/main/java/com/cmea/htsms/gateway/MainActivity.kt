package com.cmea.htsms.gateway

import android.Manifest
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.Typeface
import android.os.Bundle
import android.view.Gravity
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.LinearLayout
import android.widget.TextView
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.SimRepository
import com.cmea.htsms.gateway.gateway.GatewayService
import com.cmea.htsms.gateway.network.GatewayApi
import com.cmea.htsms.gateway.security.CredentialVault
import java.util.concurrent.Executors

class MainActivity : AppCompatActivity() {
    private lateinit var preferences: GatewayPreferences
    private lateinit var status: TextView
    private lateinit var detail: TextView
    private lateinit var token: EditText
    private lateinit var name: EditText
    private lateinit var apiUrl: EditText
    private lateinit var pair: Button
    private val executor = Executors.newSingleThreadExecutor()
    private val permissions = registerForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { render() }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        preferences = GatewayPreferences(this)
        setContentView(content())
        pair.setOnClickListener { pairDevice() }
        requestPermissionsIfNeeded()
        render()
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
            addView(TextView(context).apply { textSize = 22f; gravity = Gravity.CENTER; setPadding(0, 55, 0, 10); setTextColor(Color.rgb(16, 35, 29)) }.also { status = it })
            addView(TextView(context).apply { textSize = 13f; gravity = Gravity.CENTER; setTextColor(Color.rgb(100, 115, 109)); setPadding(0, 0, 0, 28) }.also { detail = it })
            addView(label("Gateway address")); addView(input(BuildConfig.DEFAULT_API_URL).also { apiUrl = it })
            addView(label("Phone name")); addView(input("Office Gateway").also { name = it })
            addView(label("One-time pairing code")); addView(input("htsms_pair_…").also { token = it })
            addView(Button(context).apply { text = "Pair this phone"; isAllCaps = false; setTextColor(Color.WHITE); setBackgroundColor(Color.rgb(16, 35, 29)); setPadding(20, 15, 20, 15); layoutParams = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT).apply { topMargin = 25 } }.also { pair = it })
            addView(TextView(context).apply { text = "HTSMS stores the phone credential using Android Keystore. Message content and secrets are never written to logs."; textSize = 10f; gravity = Gravity.CENTER; setTextColor(Color.rgb(100, 115, 109)); setPadding(10, 28, 10, 0) })
        }
    }

    private fun input(hintValue: String) = EditText(this).apply {
        hint = hintValue
        textSize = 14f
        setSingleLine()
        setPadding(18, 12, 18, 12)
        setBackgroundColor(Color.WHITE)
        layoutParams = LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT)
    }

    private fun pairDevice() {
        if (! hasRequiredPermissions()) { requestPermissionsIfNeeded(); return }
        val code = token.text.toString().trim()
        val deviceName = name.text.toString().trim()
        if (!code.startsWith("htsms_pair_") || deviceName.length < 2) {
            detail.text = "Enter the valid one-time code from your HTSMS dashboard."
            return
        }
        preferences.apiUrl = apiUrl.text.toString().ifBlank { BuildConfig.DEFAULT_API_URL }
        pair.isEnabled = false
        detail.text = "Pairing securely…"
        executor.execute {
            runCatching { GatewayApi(this).pair(code, deviceName) }
                .onSuccess { result ->
                    CredentialVault(this).store(result.credential)
                    preferences.deviceId = result.deviceId
                    runOnUiThread { startGateway(); render() }
                }
                .onFailure { error -> runOnUiThread { pair.isEnabled = true; detail.text = error.message ?: "Pairing failed. Check the code and connection." } }
        }
    }

    private fun render() {
        val paired = preferences.isPaired && CredentialVault(this).read() != null
        status.text = if (paired) "Gateway connected" else "Connect this phone"
        val simCount = if (hasRequiredPermissions()) SimRepository(this).active().size else 0
        detail.text = if (paired) "$simCount active SIM${if (simCount == 1) "" else "s"} · background gateway enabled" else "Pair this Android phone with your business workspace."
        token.visibility = if (paired) EditText.GONE else EditText.VISIBLE
        name.visibility = token.visibility
        apiUrl.visibility = token.visibility
        pair.visibility = token.visibility
        if (paired) startGateway()
    }

    private fun startGateway() {
        if (hasRequiredPermissions()) ContextCompat.startForegroundService(this, Intent(this, GatewayService::class.java))
    }

    private fun hasRequiredPermissions(): Boolean = REQUIRED.all { ContextCompat.checkSelfPermission(this, it) == PackageManager.PERMISSION_GRANTED }
    private fun requestPermissionsIfNeeded() { if (!hasRequiredPermissions()) permissions.launch(REQUIRED) }

    companion object {
        private val REQUIRED = buildList {
            add(Manifest.permission.READ_PHONE_STATE); add(Manifest.permission.READ_PHONE_NUMBERS); add(Manifest.permission.SEND_SMS)
            add(Manifest.permission.RECEIVE_SMS)
            if (android.os.Build.VERSION.SDK_INT >= 33) add(Manifest.permission.POST_NOTIFICATIONS)
        }.toTypedArray()
    }
}
