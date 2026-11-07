import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

const routes = [
  {
    name: 'home',
    publicPage: 'app/pages/index.vue',
    content: 'app/components/home/HomePageContent.vue',
    middleware: 'app/middleware/home-public-route.ts',
    indexableFlag: 'homePublicRouteIndexable',
    path: 'HOME_PUBLIC_PATH',
    component: 'HomePageContent',
  },
  {
    name: 'contact',
    publicPage: 'app/pages/lien-he.vue',
    content: 'app/components/contact/ContactPageContent.vue',
    middleware: 'app/middleware/contact-public-route.ts',
    indexableFlag: 'contactPublicRouteIndexable',
    path: 'CONTACT_PUBLIC_PATH',
    component: 'ContactPageContent',
  },
  {
    name: 'dealer',
    publicPage: 'app/pages/dai-ly.vue',
    content: 'app/components/dealer/DealerPageContent.vue',
    middleware: 'app/middleware/dealer-public-route.ts',
    indexableFlag: 'dealerPublicRouteIndexable',
    path: 'DEALER_PUBLIC_PATH',
    component: 'DealerPageContent',
  },
]

test('Home, Contact and Dealer pages render their content component and attach route middleware', () => {
  for (const route of routes) {
    const publicPage = read(route.publicPage)
    const content = read(route.content)
    const middleware = read(route.middleware)

    assert.match(publicPage, new RegExp(`middleware:\\s*['"]${route.name}-public-route['"]`), `${route.name} public route middleware`)
    assert.match(publicPage, new RegExp(`<${route.component}\\s*/>`), `${route.name} public content`)
    assert.doesNotMatch(content, /preview/, `${route.name} has no preview mode`)
    assert.match(content, new RegExp(`config\\.public\\.${route.indexableFlag}`), `${route.name} indexability flag`)

    assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl/)
    assert.match(middleware, new RegExp(`\\b${route.path}\\b`), `${route.name} canonical route`)
  }
})

test('public corporate routes have no enable flag and indexable pages default on', () => {
  const config = read('nuxt.config.ts')
  for (const route of routes) {
    assert.doesNotMatch(config, new RegExp(`${route.name}PublicRouteEnabled`))
    assert.match(config, new RegExp(`${route.indexableFlag}:\\s*true`))
  }
})

test('page wrappers contain no duplicate page templates', () => {
  for (const route of routes) {
    assert.doesNotMatch(read(route.publicPage), /<main[\s>]/)
    assert.match(read(route.content), /<main[\s>]/)
  }
})
