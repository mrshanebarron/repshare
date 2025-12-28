import { chromium } from 'playwright';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
await page.goto('https://repshare.sbarron.com/login');
await page.waitForLoadState('networkidle');
await page.screenshot({ path: 'login-screenshot.png', fullPage: true });
await browser.close();
console.log('Screenshot saved');
