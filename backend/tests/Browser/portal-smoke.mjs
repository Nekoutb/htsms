import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const baseUrl = process.env.HTSMS_BASE_URL ?? 'http://127.0.0.1:8765';
const email = process.env.HTSMS_QA_EMAIL ?? 'qa@htsms.local';
const password = process.env.HTSMS_QA_PASSWORD ?? 'ChangeMe!123456';
const output = 'storage/app/qa';
await mkdir(output, { recursive: true });

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const errors = [];
page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
await page.goto(baseUrl, { waitUntil: 'networkidle' });
await page.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();
await page.screenshot({ path: `${output}/landing-desktop.png`, fullPage: true });

await page.getByRole('link', { name: 'Sign in' }).click();
await page.getByLabel('Email address').fill(email);
await page.getByLabel('Password').fill(password);
await page.getByRole('button', { name: 'Sign in' }).click();
await page.waitForURL('**/app/**');
await page.getByText('Latest messages').waitFor();
await page.screenshot({ path: `${output}/dashboard-desktop.png`, fullPage: true });

const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
await mobile.goto(baseUrl, { waitUntil: 'networkidle' });
await mobile.getByRole('heading', { name: 'Your Android phone is now an SMS API.' }).waitFor();
await mobile.screenshot({ path: `${output}/landing-mobile.png`, fullPage: true });
if (errors.length > 0) throw new Error(`Browser console errors: ${errors.join('; ')}`);
await browser.close();
