package com.cmea.htsms.gateway.gateway

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class MultipartProgressTest {
    @Test
    fun completesSinglePartImmediately() {
        val progress = MultipartProgress.advance(0, 1)
        assertTrue(progress.complete)
        assertEquals(0, progress.storedCount)
    }

    @Test
    fun storesIntermediatePartsAndClearsAtCompletion() {
        val first = MultipartProgress.advance(0, 3)
        val second = MultipartProgress.advance(first.storedCount, 3)
        val third = MultipartProgress.advance(second.storedCount, 3)
        assertFalse(first.complete)
        assertFalse(second.complete)
        assertEquals(2, second.storedCount)
        assertTrue(third.complete)
        assertEquals(0, third.storedCount)
    }

    @Test
    fun defensiveBoundsStillConverge() {
        assertTrue(MultipartProgress.advance(-4, 0).complete)
    }
}
