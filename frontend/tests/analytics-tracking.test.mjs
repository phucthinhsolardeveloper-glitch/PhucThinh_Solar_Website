import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { buildAnalyticsPageContext, sanitizeAnalyticsUrl } from '../app/utils/analyticsPageContext.ts'
import { dispatchGa4Event } from '../app/utils/ga4Event.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')

test('a GA4 event uses one gtag route and flattens ecommerce parameters', () => {
  const dataLayer = []
  const calls = []
  dispatchGa4Event(dataLayer, (...args) => calls.push(args), 'view_item', {
    page_title: 'Pin mặt trời',
    ecommerce: { currency: 'VND', value: 1200, items: [{ item_id: '42' }] },
  })

  // GTM Custom Event triggers get the object; GA4 gets the single gtag command.
  assert.deepEqual(dataLayer, [
    { ecommerce: null },
    { event: 'view_item', page_title: 'Pin mặt trời', ecommerce: { currency: 'VND', value: 1200, items: [{ item_id: '42' }] } },
  ])
  assert.equal(dataLayer.filter((entry) => Object.prototype.toString.call(entry) === '[object Arguments]').length, 0)
  assert.equal(calls.length, 1)
  assert.deepEqual(calls[0], ['event', 'view_item', {
    page_title: 'Pin mặt trời', currency: 'VND', value: 1200, items: [{ item_id: '42' }],
  }])
})

test('GTM-only tracking queues one standard gtag command when no gtag function exists yet', () => {
  const dataLayer = []
  dispatchGa4Event(dataLayer, undefined, 'add_to_cart', { ecommerce: { currency: 'VND', value: 5 } })

  const commands = dataLayer.filter((entry) => Object.prototype.toString.call(entry) === '[object Arguments]')
  assert.equal(commands.length, 1)
  assert.deepEqual(Array.from(commands[0]), ['event', 'add_to_cart', { currency: 'VND', value: 5 }])
  assert.deepEqual(dataLayer.slice(0, 2), [{ ecommerce: null }, { event: 'add_to_cart', ecommerce: { currency: 'VND', value: 5 } }])
})

test('a non-ecommerce event does not clear the ecommerce object', () => {
  const dataLayer = []
  const calls = []
  dispatchGa4Event(dataLayer, (...args) => calls.push(args), 'click_call', { link_url: 'tel:0900', page_path: '/' })
  assert.deepEqual(dataLayer, [{ event: 'click_call', link_url: 'tel:0900', page_path: '/' }])
  assert.deepEqual(calls, [['event', 'click_call', { link_url: 'tel:0900', page_path: '/' }]])
})

test('product page context is current, bounded, and excludes non-campaign query data and fragments', () => {
  assert.deepEqual(buildAnalyticsPageContext(
    'https://phucthinhsolar.com/san-pham/pin/?email=private%40example.com#buy',
    'Pin mặt trời',
    'https://phucthinhsolar.com/danh-muc-san-pham/pin/?phone=0900000000#list',
  ), {
    page_location: 'https://phucthinhsolar.com/san-pham/pin/',
    page_title: 'Pin mặt trời',
    page_referrer: 'https://phucthinhsolar.com/danh-muc-san-pham/pin/',
  })
  assert.deepEqual(buildAnalyticsPageContext('https://phucthinhsolar.com/san-pham/pin/', 'Pin mặt trời', ''), {
    page_location: 'https://phucthinhsolar.com/san-pham/pin/',
    page_title: 'Pin mặt trời',
  })
})

test('GTM is the sole destination when present; consent starts denied and preserves an explicit choice', () => {
  const app = read('app/app.vue')
  assert.ok(app.includes('onLiveSite && !gtmId.value && /^G-[A-Z0-9]{6,12}$/.test'))
  assert.match(app, /var c=v==="granted"\?"granted":"denied"/)
  assert.match(app, /gtag\("set",\{page_location:s\(location\.href\),page_referrer:s\(document\.referrer\)\}\)/)
  assert.ok(app.includes('function s(x){if(!x)return ""'))
  assert.ok(app.includes('if(v!=="granted"&&v!=="denied")'))
  for (const consentType of ['analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization']) {
    assert.ok(app.includes(`${consentType}:c`), `${consentType} has its own consent state`)
  }
  assert.doesNotMatch(app, /googletagmanager\.com\/ns\.html/, 'noscript GTM must not bypass the denied default')
  const cookieNotice = read('app/components/CookieNotice.vue')
  assert.ok(cookieNotice.includes('localStorage.setItem(STORAGE_KEY, value)'))
  assert.ok(cookieNotice.includes('document.cookie = `${COOKIE_KEY}=${value}; path=/; max-age=15552000; SameSite=Lax'))
  assert.match(cookieNotice, /stored === 'granted' \|\| stored === 'denied'/)
  assert.match(cookieNotice, /gtag\('consent', 'update', \{ analytics_storage: value, ad_storage: value, ad_user_data: value, ad_personalization: value \}\)/)
})

test('internal page tracking removes query and fragment data from the referrer', () => {
  const pageTracker = read('app/plugins/page-view.client.ts')
  assert.match(pageTracker, /referrer: entry \? sanitizeAnalyticsUrl\(document\.referrer\)\.slice\(0, 500\) : ''/)
  assert.match(pageTracker, /page_location: sanitizeAnalyticsUrl\(window\.location\.href\)/)
  assert.match(pageTracker, /setGooglePageContext\(context\.referrer\)/)
  assert.match(pageTracker, /router\.afterEach\(\(to, from, failure\) => \{\s*if \(failure\) return/)
  assert.match(pageTracker, /referrerPolicy: 'origin'/)
  assert.equal(
    buildAnalyticsPageContext('https://phucthinhsolar.com/path/?email=private%40example.com#x', 'Trang', 'https://other.example/path/?phone=0900000000#x').page_referrer,
    'https://other.example/path/',
  )
})

test('existing conversion and contact events use one event dispatcher without a manual duplicate page_view', () => {
  const callSites = [
    read('app/composables/useLeadForm.ts'),
    read('app/components/order/OrderThanksPage.vue'),
    read('app/plugins/contact-clicks.client.ts'),
  ].join('\n')
  for (const event of ['generate_lead', 'purchase', 'click_call', 'click_zalo']) {
    assert.ok(callSites.includes(`'${event}'`), `${event} remains wired`)
  }
  const app = read('app/app.vue')
  assert.match(app, /gtag\('config','\$\{ga4Id\.value\}'\)/)
  assert.doesNotMatch(callSites, /pushDataLayer\(['"]page_view['"]|gtag\(['"]event['"],\s*['"]page_view['"]\)/)
  assert.match(read('app/plugins/page-view.client.ts'), /nuxtApp\.hook\('page:finish', send\)/)
  assert.match(read('app/plugins/page-view.client.ts'), /fetch\('\/api\/v1\/track'/)
  const contactClicks = read('app/plugins/contact-clicks.client.ts')
  assert.match(contactClicks, /kind === 'click_call' \? href\.replace\(\/\[\?#\]\.\*\$\/, ''\) : sanitizeAnalyticsUrl\(href\)/)
})

test('product views wait for route render and use the rendered page context', () => {
  const productPage = read('app/pages/san-pham/[slug].vue')
  assert.match(productPage, /nuxtApp\.hook\('page:finish', finish\)/)
  assert.match(productPage, /nuxtApp\.hook\('page:loading:end', finish\)/)
  assert.match(productPage, /context\.path !== route\.path/)
  assert.match(productPage, /buildAnalyticsPageContext\(window\.location\.href, document\.title, context\.referrer\)/)
  assert.match(productPage, /pushEcommerce\('view_item',[\s\S]*pageContext\)/)
})

test('campaign and ad-click ids survive sanitising so GA4 attribution is kept; other query data is dropped', () => {
  assert.equal(
    sanitizeAnalyticsUrl('https://phucthinhsolar.com/san-pham/pin/?utm_source=facebook&utm_medium=cpc&utm_campaign=t11&utm_content=a&utm_term=pin&utm_id=9&email=a%40b.vn&phone=0900000000#buy'),
    'https://phucthinhsolar.com/san-pham/pin/?utm_source=facebook&utm_medium=cpc&utm_campaign=t11&utm_content=a&utm_term=pin&utm_id=9',
  )
  assert.equal(
    sanitizeAnalyticsUrl('https://phucthinhsolar.com/?q=den&gclid=Cj0K&gbraid=GB1&wbraid=WB1&fbclid=FB1&zarsrc=1'),
    'https://phucthinhsolar.com/?gclid=Cj0K&gbraid=GB1&wbraid=WB1&fbclid=FB1',
  )
  assert.equal(sanitizeAnalyticsUrl('https://phucthinhsolar.com/?utm_source='), 'https://phucthinhsolar.com/')
  assert.equal(sanitizeAnalyticsUrl('https://phucthinhsolar.com/?UTM_SOURCE=x&utm_source_evil%3D=1'), 'https://phucthinhsolar.com/')
  assert.equal(sanitizeAnalyticsUrl('javascript:alert(1)'), '')
  assert.equal(sanitizeAnalyticsUrl('mailto:a@b.vn'), '')
})

test('the order thank-you page never leaks its token or any other query, campaign ids included', () => {
  for (const url of [
    'https://phucthinhsolar.com/cam-on?token=SECRET',
    'https://phucthinhsolar.com/cam-on/?token=SECRET&utm_source=fb&gclid=G1#x',
  ]) {
    const clean = sanitizeAnalyticsUrl(url)
    assert.ok(!clean.includes('?'), clean)
    assert.ok(!clean.includes('SECRET'), clean)
  }
  assert.equal(
    buildAnalyticsPageContext('https://phucthinhsolar.com/san-pham/x/', 'X', '/cam-on/?token=SECRET').page_referrer,
    'https://phucthinhsolar.com/cam-on/',
  )
  // A path that only starts with the same letters keeps its campaign.
  assert.equal(sanitizeAnalyticsUrl('https://phucthinhsolar.com/cam-on-khach/?utm_source=x'), 'https://phucthinhsolar.com/cam-on-khach/?utm_source=x')
})

test('the inline consent bootstrap applies the same allowlist as the shared sanitiser', () => {
  const app = read('app/app.vue')
  const literal = app.match(/\+ ('\(function\(\)\{var v=.*?'),\n/)
  assert.ok(literal, 'bootstrap script literal found')
  const js = new Function(`return ${literal[1]}`)()
  const fn = js.match(/function s\(x\)\{.*?catch\(e\)\{return ""\}\}/)
  assert.ok(fn, 'inline sanitiser found')
  const previous = globalThis.location
  globalThis.location = { href: 'https://phucthinhsolar.com/' }
  try {
    const inline = new Function(`${fn[0]};return s`)()
    for (const url of [
      'https://phucthinhsolar.com/san-pham/pin/?utm_source=fb&email=a%40b.vn&gclid=G1#h',
      'https://phucthinhsolar.com/cam-on/?token=SECRET&utm_source=fb',
      'https://phucthinhsolar.com/?fbclid=F&wbraid=W&gbraid=B&q=1',
      'javascript:alert(1)',
      '',
    ]) assert.equal(inline(url), sanitizeAnalyticsUrl(url), url)
  } finally {
    if (previous === undefined) delete globalThis.location
    else globalThis.location = previous
  }
})
