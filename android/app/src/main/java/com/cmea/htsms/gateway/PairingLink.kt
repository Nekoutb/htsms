package com.cmea.htsms.gateway

import java.net.URLDecoder
import java.util.Locale

/**
 * Parses everything a user can hand the app to pair: a bare 8-character code,
 * or an htsms://pair deep link from the portal QR. The optional host parameter
 * is accepted only when it matches the API origin compiled into this proprietary
 * app. Requiring HTTPS alone is insufficient: a hostile QR could otherwise make
 * the gateway disclose its pairing request and future credentials to an attacker.
 */
data class PairingLink(val code: String, val host: String?) {
    companion object {
        private val CODE = Regex("^[A-HJ-NP-Z2-9]{8}$")
        private val TRUSTED_HOST = BuildConfig.DEFAULT_API_URL.trimEnd('/')

        fun parse(raw: String): PairingLink? {
            val value = raw.trim()
            if (!value.lowercase(Locale.ROOT).startsWith("htsms://pair")) {
                return fromCode(value, null)
            }
            val query = value.substringAfter('?', "")
            val parameters = query.split('&').mapNotNull { pair ->
                val name = pair.substringBefore('=', "")
                if (name.isBlank() || !pair.contains('=')) return@mapNotNull null
                val decoded = runCatching {
                    URLDecoder.decode(pair.substringAfter('='), "UTF-8")
                }.getOrNull() ?: return@mapNotNull null
                name to decoded
            }.toMap()

            return fromCode(parameters["code"].orEmpty(), parameters["host"])
        }

        private fun fromCode(candidate: String, hostCandidate: String?): PairingLink? {
            val code = candidate.trim().uppercase(Locale.ROOT)
            if (!CODE.matches(code)) return null
            val host = hostCandidate
                ?.trim()
                ?.trimEnd('/')
                ?.takeIf { it == TRUSTED_HOST }

            return PairingLink(code, host)
        }
    }
}
