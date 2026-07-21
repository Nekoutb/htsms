package com.cmea.htsms.gateway.gateway

data class MultipartProgress(val storedCount: Int, val complete: Boolean) {
    companion object {
        fun advance(currentCount: Int, expectedParts: Int): MultipartProgress {
            val expected = expectedParts.coerceAtLeast(1)
            val next = currentCount.coerceAtLeast(0) + 1
            return if (next >= expected) MultipartProgress(0, true) else MultipartProgress(next, false)
        }
    }
}
