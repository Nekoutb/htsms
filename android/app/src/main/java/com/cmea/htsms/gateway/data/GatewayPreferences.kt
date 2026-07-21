package com.cmea.htsms.gateway.data

import android.content.Context
import com.cmea.htsms.gateway.BuildConfig

class GatewayPreferences(context: Context) {
    private val preferences = context.getSharedPreferences("htsms_settings", Context.MODE_PRIVATE)
    var apiUrl: String
        get() = preferences.getString("api_url", BuildConfig.DEFAULT_API_URL) ?: BuildConfig.DEFAULT_API_URL
        set(value) { preferences.edit().putString("api_url", value.trimEnd('/')).apply() }
    var deviceId: String?
        get() = preferences.getString("device_id", null)
        set(value) { preferences.edit().putString("device_id", value).apply() }
    val isPaired: Boolean get() = deviceId != null
}
