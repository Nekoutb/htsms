package com.cmea.htsms.gateway.network

import android.content.Context
import android.os.Build
import com.cmea.htsms.gateway.BuildConfig
import com.cmea.htsms.gateway.data.GatewayPreferences
import com.cmea.htsms.gateway.data.SimRepository
import com.cmea.htsms.gateway.security.CredentialVault
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

data class LeasedMessage(val id: String, val recipient: String, val content: String)
data class PairingResult(val deviceId: String, val credential: String)

class GatewayApi(private val context: Context) {
    private val preferences = GatewayPreferences(context)
    private val vault = CredentialVault(context)

    fun pair(token: String, deviceName: String): PairingResult {
        val body = JSONObject()
            .put("pairing_token", token.trim())
            .put("name", deviceName.trim())
            .put("manufacturer", Build.MANUFACTURER)
            .put("model", Build.MODEL)
            .put("android_version", Build.VERSION.RELEASE)
            .put("app_version", BuildConfig.VERSION_NAME)
            .put("fcm_token", JSONObject.NULL)
            .put("sims", SimRepository(context).asJson())
        val data = request("POST", "/api/v1/device/pair", body, authenticated = false).getJSONObject("data")
        return PairingResult(data.getJSONObject("device").getString("id"), data.getString("device_credential"))
    }

    fun heartbeat(batteryPercent: Int, connection: String) {
        request("POST", "/api/v1/device/heartbeat", JSONObject()
            .put("app_version", BuildConfig.VERSION_NAME)
            .put("android_version", Build.VERSION.RELEASE)
            .put("battery_percent", batteryPercent)
            .put("connection_type", connection)
            .put("fcm_token", JSONObject.NULL)
            .put("sims", SimRepository(context).asJson()))
    }

    fun lease(): LeasedMessage? {
        val response = request("POST", "/api/v1/device/messages/lease", JSONObject())
        if (response.isNull("data")) return null
        val data = response.getJSONObject("data")
        return LeasedMessage(data.getString("id"), data.getString("to"), data.getString("content"))
    }

    fun status(messageId: String, status: String, failureCode: String? = null, failureMessage: String? = null) {
        val body = JSONObject().put("status", status)
        failureCode?.let { body.put("failure_code", it) }
        failureMessage?.let { body.put("failure_message", it.take(500)) }
        request("POST", "/api/v1/device/messages/$messageId/status", body)
    }

    fun inbound(payload: JSONObject) {
        request("POST", "/api/v1/device/inbound-messages", payload)
    }

    private fun request(method: String, path: String, body: JSONObject, authenticated: Boolean = true): JSONObject {
        val connection = URL(preferences.apiUrl + path).openConnection() as HttpURLConnection
        try {
            connection.requestMethod = method
            connection.connectTimeout = 15_000
            connection.readTimeout = 20_000
            connection.doOutput = true
            connection.setRequestProperty("Accept", "application/json")
            connection.setRequestProperty("Content-Type", "application/json")
            if (authenticated) {
                val credential = vault.read() ?: error("Device is not paired")
                connection.setRequestProperty("Authorization", "Bearer $credential")
            }
            connection.outputStream.use { it.write(body.toString().toByteArray()) }
            val stream = if (connection.responseCode in 200..299) connection.inputStream else connection.errorStream
            val payload = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
            if (connection.responseCode !in 200..299) throw GatewayApiException(connection.responseCode, payload)
            return if (payload.isBlank()) JSONObject().put("data", JSONObject.NULL) else JSONObject(payload)
        } finally {
            connection.disconnect()
        }
    }
}

class GatewayApiException(val statusCode: Int, private val response: String) : Exception("HTSMS request failed ($statusCode): ${response.take(240)}") {
    /** Plain-language explanation shown on the phone instead of raw status/JSON. */
    fun userMessage(): String {
        val serverMessage = runCatching {
            JSONObject(response).optString("message").takeIf { it.isNotBlank() }
        }.getOrNull()

        return when {
            statusCode == 402 -> serverMessage ?: "Your plan limit was reached. Review your plan in the HTSMS portal."
            statusCode == 404 -> "This code has expired or was already used. Create a new QR code from the Devices page."
            statusCode == 422 -> serverMessage ?: "The pairing details were not accepted. Create a new QR code and try again."
            statusCode == 429 -> "Too many attempts. Wait a minute and try again."
            statusCode >= 500 -> "The HTSMS server had a problem. Try again in a moment."
            else -> serverMessage ?: "The connection was rejected (code $statusCode). Create a new QR code and try again."
        }
    }
}
