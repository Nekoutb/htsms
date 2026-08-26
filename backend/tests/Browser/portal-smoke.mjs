import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const baseUrl = process.env.HTSMS_BASE_URL ?? 'http://127.0.0.1:8765';
const email = process.env.HTSMS_QA_EMAIL ?? 'qa@htsms.local';
const output = 'storage/app/qa';
await mkdir(output, { recursive: true });

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
await page.goto(baseUrl, { waitUntil: 'networkidle' });
await page.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();
await page.screenshot({ path: `${output}/landing-desktop.png`, fullPage: true });

// Passwordless sign-in: request a magic link. There is no password field.
await page.getByRole('link', { name: 'Sign in' }).click();
if (await page.locator('input[type=password]').count() > 0) throw new Error('Login page still has a password field');
await page.getByLabel('Email address').fill(email);
await page.getByRole('button', { name: 'Email me a sign-in link' }).click();
await page.getByText('Check your inbox').waitFor();
await page.screenshot({ path: `${output}/login-link-sent.png`, fullPage: true });

const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
await mobile.goto(baseUrl, { waitUntil: 'networkidle' });
await mobile.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();
await mobile.screenshot({ path: `${output}/landing-mobile.png`, fullPage: true });
if (errors.length > 0) throw new Error(`Browser console errors: ${errors.join('; ')}`);
await browser.close();
