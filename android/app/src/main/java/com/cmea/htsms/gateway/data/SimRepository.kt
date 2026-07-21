package com.cmea.htsms.gateway.data

import android.annotation.SuppressLint
import android.content.Context
import android.telephony.SubscriptionInfo
import android.telephony.SubscriptionManager
import org.json.JSONArray
import org.json.JSONObject

class SimRepository(context: Context) {
    private val manager = context.getSystemService(SubscriptionManager::class.java)

    @SuppressLint("MissingPermission")
    fun active(): List<SubscriptionInfo> = manager.activeSubscriptionInfoList.orEmpty()

    @SuppressLint("HardwareIds", "MissingPermission")
    fun asJson(): JSONArray = JSONArray().also { array ->
        active().forEach { subscription ->
            array.put(JSONObject()
                .put("slot_index", subscription.simSlotIndex)
                .put("carrier_name", subscription.carrierName?.toString())
                .put("phone_number", subscription.number.takeIf { it.isNotBlank() })
                .put("is_active", true))
        }
    }
}
