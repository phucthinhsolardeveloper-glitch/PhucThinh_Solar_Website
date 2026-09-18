const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const phase = process.argv[2] || 'before';
if (!/^(before|after)$/.test(phase)) {
  throw new Error('Usage: node scripts/capture-responsive-architecture.js before|after');
}

const baseURL = process.env.PTS_VISUAL_BASE_URL || 'http://localhost:8080';
const outputRoot = path.resolve(__dirname, '..', 'artifacts', 'responsive-architecture-2026-09-18', phase);
const widths = [320, 360, 375, 390, 414, 768];
const routes = [
  ['home', '/'],
  ['about', '/gioi-thieu/'],
  ['category', '/danh-muc-san-pham/den-nlmt/'],
  ['product-detail', '/san-pham/den-pha-nang-luong-mat-troi-jindian-jd-t300-300w'],
  ['news', '/tin-tuc/'],
  ['news-detail', '/tin-tuc/8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio/'],
  ['contact', '/lien-he/']
];

async function main() {
  fs.mkdirSync(outputRoot, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const report = [];

  for (const width of widths) {
    const context = await browser.newContext({
      viewport: { width, height: 900 },
      deviceScaleFactor: 1,
      isMobile: width < 768,
      hasTouch: width < 768
    });
    const page = await context.newPage();

    for (const [name, route] of routes) {
      const response = await page.goto(baseURL + route, { waitUntil: 'networkidle' });
      await page.evaluate(async () => {
        /* The public CSS enables smooth scrolling. Disable it for deterministic
           full-page captures; otherwise a long page can still be travelling
           back to y=0 when Playwright starts the screenshot. */
        document.documentElement.style.scrollBehavior = 'auto';
        document.body.style.scrollBehavior = 'auto';
        const step = Math.max(500, Math.floor(window.innerHeight * 0.8));
        for (let y = 0; y < document.documentElement.scrollHeight; y += step) {
          window.scrollTo(0, y);
          await new Promise(resolve => setTimeout(resolve, 30));
        }
        window.scrollTo(0, 0);
      });
      await page.waitForTimeout(250);

      const audit = await page.evaluate(() => {
        const px = value => Number.parseFloat(value) || 0;
        const label = element => {
          const id = element.id ? `#${element.id}` : '';
          const classes = Array.from(element.classList || []).slice(0, 4).map(value => `.${value}`).join('');
          return `${element.tagName.toLowerCase()}${id}${classes}`;
        };
        const candidates = Array.from(document.querySelectorAll('main,section,article,aside,header,footer,form,div,ul'));
        const records = candidates.map(element => {
          const rect = element.getBoundingClientRect();
          const style = getComputedStyle(element);
          if (rect.width < 120 || rect.height < 35 || style.display === 'none' || style.visibility === 'hidden') return null;
          const paddingLeft = px(style.paddingLeft);
          const paddingRight = px(style.paddingRight);
          if (paddingLeft + paddingRight < 12) return null;
          const chain = [];
          let ancestor = element.parentElement;
          while (ancestor && ancestor !== document.body) {
            const ancestorRect = ancestor.getBoundingClientRect();
            const ancestorStyle = getComputedStyle(ancestor);
            const ancestorPadding = px(ancestorStyle.paddingLeft) + px(ancestorStyle.paddingRight);
            if (ancestorRect.width >= 120 && ancestorPadding >= 12) {
              chain.push({
                selector: label(ancestor),
                paddingInline: Math.round(ancestorPadding),
                width: Math.round(ancestorRect.width)
              });
            }
            ancestor = ancestor.parentElement;
          }
          if (!chain.length) return null;
          const ownPadding = paddingLeft + paddingRight;
          const cumulativePadding = ownPadding + chain.reduce((sum, item) => sum + item.paddingInline, 0);
          return {
            selector: label(element),
            width: Math.round(rect.width),
            left: Math.round(rect.left),
            right: Math.round(window.innerWidth - rect.right),
            paddingInline: Math.round(ownPadding),
            cumulativePadding: Math.round(cumulativePadding),
            background: style.backgroundColor,
            border: `${style.borderTopWidth} ${style.borderTopStyle} ${style.borderTopColor}`,
            radius: style.borderRadius,
            shadow: style.boxShadow,
            ancestors: chain.slice(0, 5)
          };
        }).filter(Boolean);

        records.sort((a, b) => b.cumulativePadding - a.cumulativePadding || a.width - b.width);
        return {
          documentWidth: document.documentElement.scrollWidth,
          viewportWidth: window.innerWidth,
          horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth,
          nestedPadding: records.slice(0, 40)
        };
      });

      await page.screenshot({
        path: path.join(outputRoot, `${name}-${width}.png`),
        fullPage: true,
        animations: 'disabled'
      });
      report.push({ name, route, width, status: response ? response.status() : null, ...audit });
    }
    await context.close();
  }

  fs.writeFileSync(path.join(outputRoot, 'audit.json'), JSON.stringify(report, null, 2));
  await browser.close();
  process.stdout.write(`Captured ${report.length} page/viewport combinations in ${outputRoot}\n`);
}

main().catch(error => {
  console.error(error);
  process.exit(1);
});
