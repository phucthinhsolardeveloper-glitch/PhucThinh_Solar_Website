/**
 * Phase-A read-only PHP viewport capture: fixed viewports/conditions,
 * real local PHP data, no Admin login and no production mutation.
 *
 * PLAYWRIGHT_BROWSERS_PATH=/home/admin01/src/Web_PTS_PHP/tools/visual-tests/.browsers
 * node scripts/capture-migration-baseline.cjs
 */
const { chromium } = require('@playwright/test')
const fs = require('node:fs/promises')
const path = require('node:path')

const root = path.resolve(__dirname, '../../..')
const base = (process.env.PTS_PHP_BASE_URL || 'http://127.0.0.1:8080').replace(/\/$/, '')
const output = process.env.PTS_CAPTURE_DIR || path.join(root, 'frontend/.migration-artifacts/legacy-baseline')
const pages = [
  ['home', '/'],
  ['catalog', '/san-pham/'],
  ['solutions', '/giai-phap/'],
  ['projects', '/du-an-da-thuc-hien/'],
  ['news', '/tin-tuc/'],
  ['about', '/gioi-thieu/'],
  ['dealer', '/dai-ly/'],
  ['contact', '/lien-he/'],
]
const viewports = [
  { id: 'mobile-390', width: 390, height: 844 },
  { id: 'desktop-1440', width: 1440, height: 900 },
]

async function main() {
  await fs.mkdir(output, { recursive: true })
  const browser = await chromium.launch({ headless: true })
  const results = []
  try {
    for (const viewport of viewports) {
      const page = await browser.newPage({
        viewport: { width: viewport.width, height: viewport.height },
        deviceScaleFactor: 1,
        reducedMotion: 'reduce',
        locale: 'vi-VN',
      })
      for (const [name, pathname] of pages) {
        const pageErrors = []
        const errorListener = error => pageErrors.push(String(error.message || error).slice(0, 200))
        page.on('pageerror', errorListener)
        try {
          const res = await page.goto(base + pathname, { waitUntil: 'domcontentloaded', timeout: 30000 })
          await page.evaluate(() => document.fonts?.ready).catch(() => {})
          await page.waitForTimeout(700)
          const meta = await page.evaluate(() => {
            const query = selector => document.querySelector(selector)?.getAttribute('content') || ''
            const canonical = document.querySelector('link[rel="canonical"]')?.getAttribute('href') || ''
            const titles = [...document.querySelectorAll('h1')].map(e => e.textContent?.trim()).filter(Boolean)
            return {
              title: document.title,
              canonical,
              description: query('meta[name="description"]'),
              robots: query('meta[name="robots"]'),
              h1: titles,
              scrollWidth: document.documentElement.scrollWidth,
              viewportWidth: document.documentElement.clientWidth,
            }
          })
          const popup = page.locator('#popup')
          let popupDismissed = false
          if (await popup.isVisible().catch(() => false)) {
            const close = popup.locator('[data-dismiss="modal"]').first()
            if (await close.isVisible().catch(() => false)) {
              await close.click({ timeout: 1800 }).catch(() => {})
              popupDismissed = true
            }
          }
          await page.screenshot({
            path: path.join(output, `${name}-${viewport.id}.png`),
            fullPage: false,
            animations: 'disabled',
            timeout: 20000,
          })
          results.push({ name, pathname, viewport: viewport.id, status: res?.status(), popupDismissed, ...meta, pageErrors })
          console.log(`${res?.status()} ${name} ${viewport.id}`)
        } catch (error) {
          results.push({ name, pathname, viewport: viewport.id, error: String(error.message || error).slice(0, 450) })
          console.error(`FAIL ${name} ${viewport.id}: ${String(error.message || error).slice(0, 160)}`)
        } finally {
          page.off('pageerror', errorListener)
        }
      }
      await page.close()
    }
  } finally {
    await browser.close()
  }
  await fs.writeFile(path.join(output, 'manifest.json'), JSON.stringify({
    capturedAt: new Date().toISOString(),
    baseline: '5a488633ec3b434a22505449b21a04ac981e9cd5',
    phpBaseUrl: base,
    screenshotConditions: 'chromium viewport, deviceScaleFactor=1, reducedMotion=reduce; first fold only',
    records: results
  }, null, 2) + '\n')
  console.log(`Saved ${results.length} route captures to ${output}`)
  if (results.some(row => row.error || row.status !== 200)) process.exitCode = 1
}

main().catch(error => { console.error(error); process.exitCode = 1 })
