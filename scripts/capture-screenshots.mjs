import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, '..', 'docs', 'screenshots');
const frontend = process.env.FRONTEND_URL ?? 'http://localhost:3000';
const backend = process.env.BACKEND_URL ?? 'http://localhost:3001';
const password = process.env.DEMO_USER_PASSWORD ?? 'BargainDemo2026!';

async function shot(page, name) {
  const file = path.join(outDir, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  console.log(`Saved ${file}`);
}

async function login(page, baseUrl, email) {
  await page.goto(`${baseUrl}/site/login`, { waitUntil: 'domcontentloaded' });
  await page.locator('#login-form input[type="email"], #login-form input[name="LoginForm[email]"]').fill(email);
  await page.locator('#login-form input[type="password"]').fill(password);
  await Promise.all([
    page.waitForURL((url) => !url.pathname.includes('/site/login'), { timeout: 15000 }),
    page.locator('#login-form button[type="submit"], #login-form [name="login-button"]').click(),
  ]);
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await mkdir(outDir, { recursive: true });

await page.goto(`${frontend}/`, { waitUntil: 'networkidle' });
await shot(page, 'public-home');

const auctionLink = page.locator('a[href*="/auction/view"]').first();
if (await auctionLink.count()) {
  await auctionLink.click();
  await page.waitForLoadState('domcontentloaded');
} else {
  await page.goto(`${frontend}/auction/view?id=1`, { waitUntil: 'domcontentloaded' });
}
await shot(page, 'public-auction');

try {
  await login(page, frontend, 'demo-bidder@bargain.app');
  await page.goto(`${frontend}/dashboard/bidder`, { waitUntil: 'networkidle' });
  await shot(page, 'bidder-dashboard');
} catch (error) {
  console.warn(error.message);
}

await page.context().clearCookies();
try {
  await login(page, frontend, 'demo-auctioneer@bargain.app');
  await page.goto(`${frontend}/dashboard/auctioneer`, { waitUntil: 'networkidle' });
  await shot(page, 'auctioneer-dashboard');
} catch (error) {
  console.warn(error.message);
}

await page.context().clearCookies();
try {
  await login(page, backend, 'demo-admin@bargain.app');
  await page.goto(`${backend}/site/index`, { waitUntil: 'networkidle' });
  await shot(page, 'admin-dashboard');
  await page.goto(`${backend}/admin/index`, { waitUntil: 'networkidle' });
  await shot(page, 'admin-platform');
  await page.goto(`${backend}/complaint/index`, { waitUntil: 'networkidle' });
  await shot(page, 'admin-complaints');
} catch (error) {
  console.warn(error.message);
}

await browser.close();
