package com.cmea.htsms.gateway.data

import android.annotation.SuppressLint
import android.content.Context
import android.os.Build
import android.telephony.SubscriptionInfo
import android.telephony.SubscriptionManager
import org.json.JSONArray
import org.json.JSONObject

class SimRepository(context: Context) {
    private val manager = context.getSystemService(SubscriptionManager::class.java)

    @SuppressLint("MissingPermission")
    fun active(): List<SubscriptionInfo> = manager.activeSubscriptionInfoList.orEmpty()

    /**
     * SubscriptionInfo.number is deprecated and blank on modern Android;
     * prefer SubscriptionManager.getPhoneNumber (API 33+, READ_PHONE_NUMBERS).
     */
    @SuppressLint("HardwareIds", "MissingPermission")
    fun numberOf(subscription: SubscriptionInfo): String? {
        val modern = if (Build.VERSION.SDK_INT >= 33) {
            runCatching { manager.getPhoneNumber(subscription.subscriptionId) }.getOrDefault("")
        } else {
            ""
        }
        @Suppress("DEPRECATION")
        val legacy = subscription.number.orEmpty()

        return modern.ifBlank { legacy }.takeIf { it.isNotBlank() }
    }

    @SuppressLint("HardwareIds", "MissingPermission")
    fun asJson(): JSONArray = JSONArray().also { array ->
        active().forEach { subscription ->
            array.put(JSONObject()
                .put("slot_index", subscription.simSlotIndex)
                .put("carrier_name", subscription.carrierName?.toString())
                .put("phone_number", numberOf(subscription))
                .put("is_active", true))
        }
    }
}
