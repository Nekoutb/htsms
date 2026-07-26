package com.cmea.htsms.gateway.gateway

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.provider.Telephony
import android.os.Build
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
        if (intent.action != Telephony.Sms.Intents.SMS_RECEIVED_ACTION) return
        if (!GatewayPreferences(context).isPaired) return
        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        if (messages.isEmpty()) return
        val sender = messages.first().originatingAddress ?: "unknown"
        val receivedAt = messages.minOf { it.timestampMillis }
        val content = messages.joinToString(separator = "") { it.messageBody.orEmpty() }
        val slotIndex = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.R) {
            intent.getIntExtra(SubscriptionManager.EXTRA_SLOT_INDEX, intent.getIntExtra("slot", 0))
        } else {
            intent.getIntExtra("slot", 0)
        }.coerceIn(0, 3)
        if (!GatewayPreferences(context).incomingEnabled(slotIndex)) return
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
        val applicationContext = context.applicationContext
        worker.execute {
            try {
                runCatching { GatewayApi(applicationContext).inbound(payload) }
                    .onFailure { PendingInboundStore(applicationContext).add(payload) }
            } finally { pendingResult.finish() }
        }
    }

    companion object {
        // One shared worker; a fresh pool per broadcast leaked threads under bursty inbound traffic.
        private val worker = Executors.newSingleThreadExecutor()
    }
}
