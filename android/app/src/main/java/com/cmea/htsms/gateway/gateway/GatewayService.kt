package com.cmea.htsms.gateway.gateway

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.content.pm.PackageManager
import android.net.ConnectivityManager
import android.os.BatteryManager
import android.os.IBinder
import android.telephony.SmsManager
import androidx.core.app.ActivityCompat
import androidx.core.app.NotificationCompat
import com.cmea.htsms.gateway.MainActivity
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.PendingInboundStore
import com.cmea.htsms.gateway.data.SimRepository
import com.cmea.htsms.gateway.network.GatewayApi
import com.cmea.htsms.gateway.network.LeasedMessage
import java.util.concurrent.Executors
import java.util.concurrent.atomic.AtomicBoolean

class GatewayService : Service() {
    private val running = AtomicBoolean(false)
    private val executor = Executors.newSingleThreadExecutor()

    override fun onCreate() {
        super.onCreate()
        val channel = NotificationChannel(CHANNEL_ID, "HTSMS gateway", NotificationManager.IMPORTANCE_LOW)
        getSystemService(NotificationManager::class.java).createNotificationChannel(channel)
        val content = PendingIntent.getActivity(this, 0, Intent(this, MainActivity::class.java), PendingIntent.FLAG_IMMUTABLE)
        startForeground(NOTIFICATION_ID, NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.stat_notify_sync).setContentTitle("HTSMS gateway is online")
            .setContentText("Waiting securely for outbound messages").setOngoing(true).setContentIntent(content).build())
        running.set(true)
        executor.execute(::loop)
    }

    override fun onDestroy() { running.set(false); executor.shutdownNow(); super.onDestroy() }
    override fun onBind(intent: Intent?): IBinder? = null

    private fun loop() {
        val api = GatewayApi(this)
        var heartbeatCountdown = 0
        while (running.get()) {
            try {
                if (heartbeatCountdown <= 0) {
                    val battery = getSystemService(BatteryManager::class.java).getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
                    val network = getSystemService(ConnectivityManager::class.java).activeNetworkInfo?.typeName?.lowercase() ?: "offline"
                    api.heartbeat(battery.coerceIn(0, 100), network)
                    heartbeatCountdown = 6
                }
                api.lease()?.let { dispatch(it, api) }
                flushInbound(api)
            } catch (_: Exception) { /* Network errors retry without logging message contents or credentials. */ }
            heartbeatCountdown--
            try { Thread.sleep(10_000) } catch (_: InterruptedException) { return }
        }
    }

    private fun flushInbound(api: GatewayApi) {
        val store = PendingInboundStore(this)
        store.pending().forEach { (eventId, payload) ->
            api.inbound(payload)
            store.remove(eventId)
        }
    }

    private fun dispatch(message: LeasedMessage, api: GatewayApi) {
        if (ActivityCompat.checkSelfPermission(this, Manifest.permission.SEND_SMS) != PackageManager.PERMISSION_GRANTED) {
            api.status(message.id, "failed", "permission_denied", "SMS permission is not granted")
            return
        }
        val subscription = SimRepository(this).active().firstOrNull() ?: run {
            api.status(message.id, "failed", "no_active_sim", "No active SIM is available")
            return
        }
        api.status(message.id, "dispatched")
        val manager = getSystemService(SmsManager::class.java).createForSubscriptionId(subscription.subscriptionId)
        val parts = manager.divideMessage(message.content)
        if (parts.size == 1) {
            val sent = callback(SmsSentReceiver::class.java, "HTSMS_SMS_SENT", message.id, 0, 1)
            val delivered = callback(SmsDeliveredReceiver::class.java, "HTSMS_SMS_DELIVERED", message.id, 0, 1)
            manager.sendTextMessage(message.recipient, null, message.content, sent, delivered)
        }
        else {
            val sentCallbacks = ArrayList(List(parts.size) { index ->
                callback(SmsSentReceiver::class.java, "HTSMS_SMS_SENT", message.id, index, parts.size)
            })
            val deliveryCallbacks = ArrayList(List(parts.size) { index ->
                callback(SmsDeliveredReceiver::class.java, "HTSMS_SMS_DELIVERED", message.id, index, parts.size)
            })
            manager.sendMultipartTextMessage(message.recipient, null, parts, sentCallbacks, deliveryCallbacks)
        }
    }

    private fun callback(receiver: Class<*>, action: String, messageId: String, partIndex: Int = 0, partCount: Int = 1): PendingIntent {
        val intent = Intent(this, receiver).setAction(action).putExtra("message_id", messageId)
            .putExtra("part_index", partIndex).putExtra("part_count", partCount)
        return PendingIntent.getBroadcast(this, messageId.hashCode() xor action.hashCode() xor partIndex, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE)
    }

    companion object { private const val CHANNEL_ID = "htsms_gateway"; private const val NOTIFICATION_ID = 4101 }
}
