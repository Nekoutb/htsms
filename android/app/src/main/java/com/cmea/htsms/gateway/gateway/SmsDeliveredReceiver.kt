package com.cmea.htsms.gateway.gateway

import android.app.Activity
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.cmea.htsms.gateway.network.GatewayApi
import java.util.concurrent.Executors

class SmsDeliveredReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val messageId = intent.getStringExtra("message_id") ?: return
        val partCount = intent.getIntExtra("part_count", 1)
        if (resultCode != Activity.RESULT_OK) return
        val pending = goAsync()
        Executors.newSingleThreadExecutor().execute {
            try {
                val state = context.getSharedPreferences("htsms_multipart", Context.MODE_PRIVATE)
                val completed = synchronized(state) {
                    val key = "delivered_$messageId"
                    val next = state.getInt(key, 0) + 1
                    if (next >= partCount) state.edit().remove(key).apply() else state.edit().putInt(key, next).apply()
                    next >= partCount
                }
                if (completed) GatewayApi(context).status(messageId, "delivered")
            } finally { pending.finish() }
        }
    }
}
