package com.cmea.htsms.gateway

import java.net.URLDecoder
import java.util.Locale

/**
 * Parses everything a user can hand the app to pair: a bare 8-character code,
 * or an htsms://pair deep link from the portal QR. The optional host parameter
 * lets the app follow the portal that issued the code; only HTTPS origins are
 * accepted so a hostile QR cannot downgrade transport security.
 */
data class PairingLink(val code: String, val host: String?) {
    companion object {
        private val CODE = Regex("^[A-HJ-NP-Z2-9]{8}$")
        private val HOST = Regex("^https://[a-zA-Z0-9.-]+(:\\d{2,5})?$")

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
            val host = hostCandidate?.trim()?.trimEnd('/')?.takeIf { HOST.matches(it) }

            return PairingLink(code, host)
        }
    }
}
