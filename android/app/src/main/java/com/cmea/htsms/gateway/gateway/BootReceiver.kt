package com.cmea.htsms.gateway.gateway

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import androidx.core.app.NotificationCompat
import androidx.core.content.ContextCompat
import com.cmea.htsms.gateway.MainActivity
import com.cmea.htsms.gateway.data.GatewayPreferences

/**
 * Restarts the gateway after a reboot so a dedicated phone keeps sending
 * without anyone opening the app. Android 15+ can forbid starting a dataSync
 * foreground service from BOOT_COMPLETED; in that case a tap-to-resume
 * notification is posted instead of failing silently.
 */
class BootReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action != Intent.ACTION_BOOT_COMPLETED && intent.action != "android.intent.action.QUICKBOOT_POWERON") return
        if (!GatewayPreferences(context).isPaired) return
        val permitted = arrayOf(
            Manifest.permission.READ_PHONE_STATE,
            Manifest.permission.READ_PHONE_NUMBERS,
            Manifest.permission.SEND_SMS,
            Manifest.permission.RECEIVE_SMS,
        ).all { ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
        if (!permitted) return

        runCatching {
            ContextCompat.startForegroundService(context, Intent(context, GatewayService::class.java))
        }.onFailure { promptToResume(context) }
    }

    private fun promptToResume(context: Context) {
        val manager = context.getSystemService(NotificationManager::class.java) ?: return
        manager.createNotificationChannel(
            NotificationChannel(CHANNEL_ID, "HTSMS gateway alerts", NotificationManager.IMPORTANCE_HIGH),
        )
        val open = PendingIntent.getActivity(
            context, 0, Intent(context, MainActivity::class.java), PendingIntent.FLAG_IMMUTABLE,
        )
        manager.notify(
            NOTIFICATION_ID,
            NotificationCompat.Builder(context, CHANNEL_ID)
                .setSmallIcon(android.R.drawable.stat_notify_error)
                .setContentTitle("HTSMS gateway is stopped")
                .setContentText("The phone restarted. Tap to resume sending messages.")
                .setAutoCancel(true)
                .setContentIntent(open)
                .build(),
        )
    }

    companion object {
        private const val CHANNEL_ID = "htsms_gateway_alerts"
        private const val NOTIFICATION_ID = 4102
    }
}
