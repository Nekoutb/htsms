import { chromium } from 'playwright';

const baseUrl = process.env.HTSMS_BASE_URL;
if (!baseUrl?.startsWith('https://')) {
    throw new Error('HTSMS_BASE_URL must be an HTTPS URL');
}

const browser = await chromium.launch({ headless: true });
const errors = [];

try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    const response = await page.goto(baseUrl, { waitUntil: 'networkidle' });
    if (!response?.ok()) throw new Error(`Landing page returned ${response?.status()}`);
    await page.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();

    await page.getByRole('link', { name: 'Sign in' }).click();
    await page.getByLabel('Email address').waitFor();
    if (await page.locator('input[type=password]').count() > 0) throw new Error('Login page still exposes a password field');

    await page.goto(`${baseUrl}/register`, { waitUntil: 'networkidle' });
    await page.getByLabel('Work email').waitFor();
    if (await page.locator('input[type=password]').count() > 0) throw new Error('Register page still exposes a password field');

    const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
    const mobileResponse = await mobile.goto(baseUrl, { waitUntil: 'networkidle' });
    if (!mobileResponse?.ok()) throw new Error(`Mobile landing page returned ${mobileResponse?.status()}`);
    await mobile.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();

    if (errors.length > 0) throw new Error(`Browser console errors: ${errors.join('; ')}`);
    console.log('Production public smoke test passed');
} finally {
    await browser.close();
}
