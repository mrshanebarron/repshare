import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await context.newPage();

await page.goto('https://repshare.sbarron.com/login');
await page.waitForLoadState('networkidle');
await page.fill('input[type="email"]', 'admin@repshare.test');
await page.fill('input[type="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL('**/admin/dashboard');
await page.waitForLoadState('networkidle');

// Try clicking the user dropdown
await page.click('text=Admin User');
await page.waitForTimeout(500);
await page.screenshot({ path: 'dropdown-screenshot.png', fullPage: true });
await browser.close();
console.log('Screenshot saved');
