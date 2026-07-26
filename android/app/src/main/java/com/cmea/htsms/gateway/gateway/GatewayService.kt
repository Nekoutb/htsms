package com.cmea.htsms.gateway.gateway

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.content.pm.PackageManager
import android.net.ConnectivityManager
import android.net.NetworkCapabilities
import android.os.BatteryManager
import android.os.Build
import android.os.IBinder
import android.telephony.SmsManager
import android.telephony.SubscriptionInfo
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
        val preferences = GatewayPreferences(this)
        var heartbeatCountdown = 0
        while (running.get()) {
            try {
                if (heartbeatCountdown <= 0) {
                    val battery = getSystemService(BatteryManager::class.java).getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY)
                    api.heartbeat(battery.coerceIn(0, 100), connectionType())
                    heartbeatCountdown = 6
                }
                api.lease()?.let { dispatch(it, api) }
                flushInbound(api)
                preferences.lastSyncAt = System.currentTimeMillis()
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
        val preferences = GatewayPreferences(this)
        val active = SimRepository(this).active().filter { preferences.outgoingEnabled(it.simSlotIndex) }
        val subscription = selectSubscription(active, message.simSlotIndex)
        if (subscription == null) {
            api.status(message.id, "failed", "no_active_sim", requestedSimMessage(message.simSlotIndex, active.isEmpty()))
            return
        }
        api.status(message.id, "dispatched")
        try {
            val manager = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
                getSystemService(SmsManager::class.java).createForSubscriptionId(subscription.subscriptionId)
            } else {
                @Suppress("DEPRECATION")
                SmsManager.getSmsManagerForSubscriptionId(subscription.subscriptionId)
            }
            val parts = manager.divideMessage(message.content)
            if (parts.size == 1) {
                val sent = callback(SmsSentReceiver::class.java, "HTSMS_SMS_SENT", message.id, 0, 1)
                val delivered = callback(SmsDeliveredReceiver::class.java, "HTSMS_SMS_DELIVERED", message.id, 0, 1)
                manager.sendTextMessage(message.recipient, null, message.content, sent, delivered)
            } else {
                val sentCallbacks = ArrayList(List(parts.size) { index ->
                    callback(SmsSentReceiver::class.java, "HTSMS_SMS_SENT", message.id, index, parts.size)
                })
                val deliveryCallbacks = ArrayList(List(parts.size) { index ->
                    callback(SmsDeliveredReceiver::class.java, "HTSMS_SMS_DELIVERED", message.id, index, parts.size)
                })
                manager.sendMultipartTextMessage(message.recipient, null, parts, sentCallbacks, deliveryCallbacks)
            }
        } catch (error: Exception) {
            // Marked dispatched above; a throw here would otherwise strand the message. Report it back.
            api.status(message.id, "failed", "android_dispatch_error", error.message ?: "SMS manager rejected the message")
        }
    }

    /** Honor the message's preferred SIM slot when the SIM is present; fall back to the first active SIM. */
    private fun selectSubscription(active: List<SubscriptionInfo>, preferredSlotIndex: Int?): SubscriptionInfo? {
        if (preferredSlotIndex != null) {
            active.firstOrNull { it.simSlotIndex == preferredSlotIndex }?.let { return it }
        }
        return active.firstOrNull()
    }

    private fun requestedSimMessage(preferredSlotIndex: Int?, noSims: Boolean): String = when {
        noSims -> "No active SIM is available"
        preferredSlotIndex != null -> "The requested SIM (slot ${preferredSlotIndex + 1}) is not available on this phone"
        else -> "No active SIM is available"
    }

    private fun connectionType(): String {
        val manager = getSystemService(ConnectivityManager::class.java) ?: return "offline"
        val capabilities = manager.getNetworkCapabilities(manager.activeNetwork) ?: return "offline"
        return when {
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_WIFI) -> "wifi"
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR) -> "cellular"
            capabilities.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET) -> "ethernet"
            else -> "other"
        }
    }

    private fun callback(receiver: Class<*>, action: String, messageId: String, partIndex: Int = 0, partCount: Int = 1): PendingIntent {
        val intent = Intent(this, receiver).setAction(action).putExtra("message_id", messageId)
            .putExtra("part_index", partIndex).putExtra("part_count", partCount)
        return PendingIntent.getBroadcast(this, messageId.hashCode() xor action.hashCode() xor partIndex, intent, PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE)
    }

    companion object { private const val CHANNEL_ID = "htsms_gateway"; private const val NOTIFICATION_ID = 4101 }
}
