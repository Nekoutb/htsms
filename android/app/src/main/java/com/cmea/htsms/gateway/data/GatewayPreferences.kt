package com.cmea.htsms.gateway.data

import android.content.Context
import com.cmea.htsms.gateway.BuildConfig
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

class GatewayPreferences(context: Context) {
    private val preferences = context.getSharedPreferences("htsms_settings", Context.MODE_PRIVATE)
    var apiUrl: String
        get() = preferences.getString("api_url", BuildConfig.DEFAULT_API_URL) ?: BuildConfig.DEFAULT_API_URL
        set(value) { preferences.edit().putString("api_url", value.trimEnd('/')).apply() }
    var deviceId: String?
        get() = preferences.getString("device_id", null)
        set(value) { preferences.edit().putString("device_id", value).apply() }
    var deviceName: String?
        get() = preferences.getString("device_name", null)
        set(value) { preferences.edit().putString("device_name", value).apply() }
    var lastSyncAt: Long
        get() = preferences.getLong("last_sync_at", 0L)
        set(value) { preferences.edit().putLong("last_sync_at", value).apply() }
    val isPaired: Boolean get() = deviceId != null

    fun outgoingEnabled(slotIndex: Int): Boolean = preferences.getBoolean("sim_${slotIndex}_outgoing", true)
    fun incomingEnabled(slotIndex: Int): Boolean = preferences.getBoolean("sim_${slotIndex}_incoming", true)
    fun setOutgoingEnabled(slotIndex: Int, enabled: Boolean) {
        preferences.edit().putBoolean("sim_${slotIndex}_outgoing", enabled).apply()
    }
    fun setIncomingEnabled(slotIndex: Int, enabled: Boolean) {
        preferences.edit().putBoolean("sim_${slotIndex}_incoming", enabled).apply()
    }
    var debugLogsEnabled: Boolean
        get() = preferences.getBoolean("debug_logs", false)
        set(value) { preferences.edit().putBoolean("debug_logs", value).apply() }

    val sentToday: Int
        get() = if (preferences.getString("sent_date", null) == today()) preferences.getInt("sent_count", 0) else 0

    fun incrementSentToday() {
        val count = sentToday + 1
        preferences.edit().putString("sent_date", today()).putInt("sent_count", count).apply()
    }

    /** Removes the pairing while keeping the stored server URL for the next setup. */
    fun clearPairing() {
        preferences.edit()
            .remove("device_id").remove("device_name")
            .remove("last_sync_at").remove("sent_date").remove("sent_count")
            .apply()
    }

    private fun today(): String = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())
}
