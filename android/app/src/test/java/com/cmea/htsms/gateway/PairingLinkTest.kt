package com.cmea.htsms.gateway

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class PairingLinkTest {
    @Test
    fun `accepts a bare code and normalizes case`() {
        assertEquals(PairingLink("ABCD2345", null), PairingLink.parse(" abcd2345 "))
    }

    @Test
    fun `rejects codes with excluded characters or wrong length`() {
        assertNull(PairingLink.parse("ABCD234"))
        assertNull(PairingLink.parse("ABCD23456"))
        assertNull(PairingLink.parse("ABCD234O"))
        assertNull(PairingLink.parse("ABCD234I"))
        assertNull(PairingLink.parse("ABCD2341"))
        assertNull(PairingLink.parse(""))
    }

    @Test
    fun `parses portal deep link with encoded https host`() {
        val link = PairingLink.parse("htsms://pair?code=MDYX79U5&host=https%3A%2F%2Fhtsms.cm-ea.com")
        assertEquals(PairingLink("MDYX79U5", "https://htsms.cm-ea.com"), link)
    }

    @Test
    fun `parses deep link without host`() {
        assertEquals(PairingLink("MDYX79U5", null), PairingLink.parse("htsms://pair?code=MDYX79U5"))
    }

    @Test
    fun `ignores untrusted insecure or malformed hosts but keeps the code`() {
        assertEquals(
            PairingLink("MDYX79U5", null),
            PairingLink.parse("htsms://pair?code=MDYX79U5&host=http%3A%2F%2Fevil.example"),
        )
        assertEquals(
            PairingLink("MDYX79U5", null),
            PairingLink.parse("htsms://pair?code=MDYX79U5&host=https%3A%2F%2Fevil.example"),
        )
        assertEquals(
            PairingLink("MDYX79U5", null),
            PairingLink.parse("htsms://pair?code=MDYX79U5&host=https%3A%2F%2Fevil.example%2Fphish%3Fq%3D1"),
        )
    }

    @Test
    fun `rejects other https deployment hosts`() {
        val link = PairingLink.parse("htsms://pair?code=MDYX79U5&host=https%3A%2F%2Fstaging.cm-ea.com%3A8443%2F")
        assertEquals(PairingLink("MDYX79U5", null), link)
    }

    @Test
    fun `rejects deep link with missing or invalid code`() {
        assertNull(PairingLink.parse("htsms://pair?host=https%3A%2F%2Fhtsms.cm-ea.com"))
        assertNull(PairingLink.parse("htsms://pair?code=BAD"))
    }
}
