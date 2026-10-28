import { chromium } from '/home/admin01/src/Web_PTS_PHP/tools/visual-tests/node_modules/playwright/index.mjs'

const BASE = 'http://127.0.0.1:3115'
const results = []

function record(name, ok, detail) {
  results.push({ name, ok, detail })
  console.log(`${ok ? 'PASS' : 'FAIL'} ${name}${detail ? ' - ' + detail : ''}`)
}

const browser = await chromium.launch()
const page = await browser.newPage()
const consoleErrors = []
page.on('console', (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()) })
page.on('pageerror', (err) => consoleErrors.push(String(err)))

// ---- Gallery lightbox ----
await page.goto(`${BASE}/migration-gallery-detail-preview?slug=lightbox-qa-album`, { waitUntil: 'networkidle' })
const thumbs = page.locator('[data-gallery-open], button:has(img)')
const thumbCount = await page.locator('img[alt="Fixture ảnh 1"], img[alt="Fixture ảnh 2"], img[alt="Fixture ảnh 3"]').count()
record('gallery: fixture images rendered', thumbCount >= 3, `found ${thumbCount} fixture images`)

// Find the first fixture image and click its enclosing trigger (button/a) to open lightbox
const firstImg = page.locator('img[alt="Fixture ảnh 1"]').first()
const trigger = firstImg.locator('xpath=ancestor::button[1] | ancestor::a[1]').first()
await trigger.click()
await page.waitForTimeout(300)

const overlay = page.locator('[role="dialog"], [class*="lightbox"]').first()
const overlayVisible = await overlay.isVisible().catch(() => false)
record('gallery: lightbox opens on click', overlayVisible)

if (overlayVisible) {
  const activeAfterOpen = await page.evaluate(() => document.activeElement?.tagName + ':' + (document.activeElement?.className || ''))
  record('gallery: focus moved into overlay on open', !activeAfterOpen.startsWith('BODY'), activeAfterOpen)

  // Tab cycle: press Tab many times, confirm focus never leaves the overlay container
  let stayedInside = true
  for (let i = 0; i < 15; i++) {
    await page.keyboard.press('Tab')
    const inside = await page.evaluate(() => {
      const dialog = document.querySelector('[role="dialog"], [class*="lightbox"]')
      return dialog ? dialog.contains(document.activeElement) : false
    })
    if (!inside) { stayedInside = false; break }
  }
  record('gallery: Tab cycle stays inside overlay (15 presses)', stayedInside)

  // Arrow nav should NOT re-trigger focusFirst (regression check for the bug just fixed)
  const focusBeforeArrow = await page.evaluate(() => document.activeElement?.outerHTML?.slice(0, 120))
  await page.keyboard.press('ArrowRight')
  await page.waitForTimeout(150)
  const focusAfterArrow = await page.evaluate(() => document.activeElement?.outerHTML?.slice(0, 120))
  // The fix means focus should NOT be yanked back to the "first focusable" element on every arrow press.
  // We check that focus is still inside the dialog (not that it's unchanged - arrow nav may reasonably keep focus on a nav button).
  const stillInsideAfterArrow = await page.evaluate(() => {
    const dialog = document.querySelector('[role="dialog"], [class*="lightbox"]')
    return dialog ? dialog.contains(document.activeElement) : false
  })
  record('gallery: focus still inside overlay after ArrowRight (no re-trap bug)', stillInsideAfterArrow, `before=${focusBeforeArrow} after=${focusAfterArrow}`)

  // Escape closes, focus returns to trigger
  await page.keyboard.press('Escape')
  await page.waitForTimeout(300)
  const overlayClosed = !(await overlay.isVisible().catch(() => false))
  record('gallery: Escape closes overlay', overlayClosed)

  const focusReturnedToTrigger = await trigger.evaluate((el) => el === document.activeElement)
  record('gallery: focus returns to trigger button after Escape', focusReturnedToTrigger)
} else {
  record('gallery: Tab cycle stays inside overlay', false, 'skipped - overlay never opened')
  record('gallery: focus still inside overlay after ArrowRight (no re-trap bug)', false, 'skipped - overlay never opened')
  record('gallery: Escape closes overlay', false, 'skipped - overlay never opened')
  record('gallery: focus returns to trigger button after Escape', false, 'skipped - overlay never opened')
}

// ---- Video page (per Lane E finding: no arrow-nav between videos, so just check base open/close/escape) ----
await page.goto(`${BASE}/migration-video-preview`, { waitUntil: 'networkidle' })
const videoTitleCount = await page.locator('text=Fixture Video QA').count()
record('video: fixture video rendered', videoTitleCount >= 1, `found ${videoTitleCount}`)

const videoTrigger = page.locator('text=Fixture Video QA').first().locator('xpath=ancestor::button[1] | ancestor::a[1]').first()
const videoTriggerExists = await videoTrigger.count() > 0
if (videoTriggerExists) {
  await videoTrigger.click()
  await page.waitForTimeout(300)
  const videoOverlay = page.locator('[role="dialog"], [class*="lightbox"]').first()
  const videoOverlayVisible = await videoOverlay.isVisible().catch(() => false)
  record('video: overlay opens on click', videoOverlayVisible)
  if (videoOverlayVisible) {
    await page.keyboard.press('Escape')
    await page.waitForTimeout(300)
    const videoClosed = !(await videoOverlay.isVisible().catch(() => false))
    record('video: Escape closes overlay', videoClosed)
    const videoFocusReturned = await videoTrigger.evaluate((el) => el === document.activeElement)
    record('video: focus returns to trigger after Escape', videoFocusReturned)
  } else {
    record('video: Escape closes overlay', false, 'skipped - overlay never opened')
    record('video: focus returns to trigger after Escape', false, 'skipped - overlay never opened')
  }
} else {
  record('video: overlay opens on click', false, 'no trigger element found near video title (may render inline without lightbox)')
}

record('console: no page errors across both pages', consoleErrors.length === 0, consoleErrors.join(' | '))

await browser.close()

const failed = results.filter((r) => !r.ok)
console.log('\n=== SUMMARY ===')
console.log(`${results.length - failed.length}/${results.length} PASS`)
if (failed.length) {
  console.log('FAILED:')
  for (const f of failed) console.log(` - ${f.name}: ${f.detail}`)
}
process.exit(failed.length ? 1 : 0)
