import { chromium } from 'playwright';

const browser = await chromium.launch();
const context = await browser.newContext({ 
    viewport: { width: 1280, height: 900 },
    storageState: undefined
});
const page = await context.newPage();

// Login first
await page.goto('https://repshare.sbarron.com/login');
await page.waitForLoadState('networkidle');
await page.fill('input[type="email"]', 'admin@repshare.test');
await page.fill('input[type="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL('**/admin/dashboard');
await page.waitForLoadState('networkidle');
await page.screenshot({ path: 'dashboard-screenshot.png', fullPage: true });
await browser.close();
console.log('Screenshot saved');
