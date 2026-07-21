package com.cmea.htsms.gateway.gateway

import android.app.Activity
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import com.cmea.htsms.gateway.network.GatewayApi
import java.util.concurrent.Executors

class SmsSentReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        val messageId = intent.getStringExtra("message_id") ?: return
        val partIndex = intent.getIntExtra("part_index", 0)
        val partCount = intent.getIntExtra("part_count", 1)
        val result = resultCode
        val pending = goAsync()
        Executors.newSingleThreadExecutor().execute {
            try {
                if (result != Activity.RESULT_OK) {
                    GatewayApi(context).status(messageId, "failed", "android_send_$result", "Android SMS manager rejected message part ${partIndex + 1}")
                } else {
                    val state = context.getSharedPreferences("htsms_multipart", Context.MODE_PRIVATE)
                    val completed = synchronized(state) {
                        val key = "sent_$messageId"
                        val progress = MultipartProgress.advance(state.getInt(key, 0), partCount)
                        if (progress.complete) state.edit().remove(key).apply() else state.edit().putInt(key, progress.storedCount).apply()
                        progress.complete
                    }
                    if (completed) GatewayApi(context).status(messageId, "sent")
                }
            } finally { pending.finish() }
        }
    }
}
