import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

const routes = [
  {
    name: 'home',
    publicPage: 'app/pages/index.vue',
    previewPage: 'app/pages/migration-home-preview.vue',
    content: 'app/components/home/HomePageContent.vue',
    middleware: 'app/middleware/home-public-route.ts',
    flag: 'homePublicRouteEnabled',
    indexableFlag: 'homePublicRouteIndexable',
    path: 'HOME_PUBLIC_PATH',
    component: 'HomePageContent',
  },
  {
    name: 'contact',
    publicPage: 'app/pages/lien-he.vue',
    previewPage: 'app/pages/migration-contact-preview.vue',
    content: 'app/components/contact/ContactPageContent.vue',
    middleware: 'app/middleware/contact-public-route.ts',
    flag: 'contactPublicRouteEnabled',
    indexableFlag: 'contactPublicRouteIndexable',
    path: 'CONTACT_PUBLIC_PATH',
    component: 'ContactPageContent',
  },
  {
    name: 'dealer',
    publicPage: 'app/pages/dai-ly.vue',
    previewPage: 'app/pages/migration-dealer-preview.vue',
    content: 'app/components/dealer/DealerPageContent.vue',
    middleware: 'app/middleware/dealer-public-route.ts',
    flag: 'dealerPublicRouteEnabled',
    indexableFlag: 'dealerPublicRouteIndexable',
    path: 'DEALER_PUBLIC_PATH',
    component: 'DealerPageContent',
  },
]

test('public Home, Contact, and Dealer routes reuse their preview content and attach route middleware', () => {
  for (const route of routes) {
    const publicPage = read(route.publicPage)
    const previewPage = read(route.previewPage)
    const content = read(route.content)
    const middleware = read(route.middleware)

    assert.match(publicPage, new RegExp(`middleware:\\s*${route.name}PublicRoute`), `${route.name} public route middleware`)
    assert.match(publicPage, new RegExp(`<${route.component}\\s*/>`), `${route.name} public content`)
    assert.match(previewPage, new RegExp(`<${route.component}\\s+preview\\s*/>`), `${route.name} preview content`)
    assert.match(content, /defineProps<\{\s*preview\?: boolean\s*\}>\(\)/, `${route.name} shared preview prop`)
    assert.match(content, new RegExp(`config\\.public\\.${route.indexableFlag}`), `${route.name} indexability flag`)
    assert.match(content, /!props\.preview\s*&&\s*isRuntimeFlagEnabled/, `${route.name} preview must remain noindex`)

    assert.match(middleware, new RegExp(`config\\.public\\.${route.flag}`), `${route.name} enabled flag`)
    assert.match(middleware, new RegExp(`phpPublicRouteUrl\\(config\\.public\\.phpPublicOrigin,\\s*${route.path}\\)`), `${route.name} PHP fallback path`)
    assert.match(middleware, /external:\s*true/)
    assert.match(middleware, /redirectCode:\s*307/)
  }
})

test('new public corporate route flags and indexability flags default off', () => {
  const config = read('nuxt.config.ts')
  for (const route of routes) {
    assert.match(config, new RegExp(`${route.flag}:\\s*false`))
    assert.match(config, new RegExp(`${route.indexableFlag}:\\s*false`))
  }
})

test('public and preview wrappers contain no duplicate page templates', () => {
  for (const route of routes) {
    assert.doesNotMatch(read(route.publicPage), /<main[\s>]/)
    assert.doesNotMatch(read(route.previewPage), /<main[\s>]/)
    assert.match(read(route.content), /<main[\s>]/)
  }
})
