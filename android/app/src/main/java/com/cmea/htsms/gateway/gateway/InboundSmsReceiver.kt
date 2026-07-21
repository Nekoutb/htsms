package com.cmea.htsms.gateway.gateway

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.provider.Telephony
import android.telephony.SubscriptionManager
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.PendingInboundStore
import com.cmea.htsms.gateway.network.GatewayApi
import org.json.JSONObject
import java.security.MessageDigest
import java.time.Instant
import java.util.concurrent.Executors

class InboundSmsReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (!GatewayPreferences(context).isPaired) return
        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        if (messages.isEmpty()) return
        val sender = messages.first().originatingAddress ?: "unknown"
        val receivedAt = messages.minOf { it.timestampMillis }
        val content = messages.joinToString(separator = "") { it.messageBody.orEmpty() }
        val slotIndex = intent.getIntExtra(SubscriptionManager.EXTRA_SLOT_INDEX, intent.getIntExtra("slot", 0)).coerceIn(0, 3)
        val eventSource = "$sender|$receivedAt|$slotIndex|$content"
        val eventId = MessageDigest.getInstance("SHA-256").digest(eventSource.toByteArray()).joinToString("") { "%02x".format(it) }
        val payload = JSONObject()
            .put("device_event_id", eventId)
            .put("sender", sender)
            .put("recipient", JSONObject.NULL)
            .put("content", content)
            .put("received_at", Instant.ofEpochMilli(receivedAt).toString())
            .put("sim_slot_index", slotIndex)
        val pendingResult = goAsync()
        Executors.newSingleThreadExecutor().execute {
            try {
                runCatching { GatewayApi(context).inbound(payload) }
                    .onFailure { PendingInboundStore(context).add(payload) }
            } finally { pendingResult.finish() }
        }
    }
}
