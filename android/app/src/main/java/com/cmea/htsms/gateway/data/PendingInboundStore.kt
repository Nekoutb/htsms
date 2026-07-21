package com.cmea.htsms.gateway.data

import android.content.Context
import com.cmea.htsms.gateway.security.CredentialVault
import org.json.JSONObject

class PendingInboundStore(private val context: Context) {
    private val preferences = context.getSharedPreferences("htsms_inbound_queue", Context.MODE_PRIVATE)
    private val vault = CredentialVault(context)

    @Synchronized
    fun add(payload: JSONObject) {
        val eventId = payload.getString("device_event_id")
        preferences.edit().putString(eventId, vault.encrypt(payload.toString())).apply()
    }

    @Synchronized
    fun pending(): List<Pair<String, JSONObject>> = preferences.all.mapNotNull { (eventId, encrypted) ->
        val value = encrypted as? String ?: return@mapNotNull null
        vault.decrypt(value)?.let { runCatching { JSONObject(it) }.getOrNull() }?.let { eventId to it }
    }

    @Synchronized
    fun remove(eventId: String) { preferences.edit().remove(eventId).apply() }
}
