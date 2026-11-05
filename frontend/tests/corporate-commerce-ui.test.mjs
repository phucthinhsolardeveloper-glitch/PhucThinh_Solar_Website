import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const app = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, app), 'utf8')

const cart = read('components/cart/CartPage.vue')
const contactPage = read('components/contact/ContactPageContent.vue')
const contactForm = read('components/contact/ContactLeadForm.vue')
const dealerForm = read('components/dealer/DealerLeadForm.vue')

test('cart navigation follows the current public or preview route', () => {
  assert.match(cart, /const links = useMigrationLinks\(\)/)
  assert.match(cart, /links\.generic\('\/san-pham\/'\)/)
  assert.match(cart, /links\.product\(item\.slug\)/)
  assert.match(cart, /v-if="!payload\.canCheckout"[\s\S]*cần báo giá/)
  assert.match(cart, /v-else-if="isMigrationPreview"[\s\S]*role="status"[\s\S]*không tạo đơn hàng/)
  assert.match(cart, /<NuxtLink[\s\S]*v-else[\s\S]*to="\/thanh-toan\/"/)
  assert.doesNotMatch(cart, /migration-checkout-preview|Tiếp tục thanh toán/)
  assert.doesNotMatch(cart, /Thanh toán trực tuyến chưa được mở|checkoutRouteEnabled/)
  assert.doesNotMatch(cart, /Commerce preview|Cart session đã chạy|để thử luồng giỏ hàng Nuxt/)
})

test('cart mutation failures remain visible and are handled by the UI event handlers', () => {
  assert.match(cart, /<p v-if="error"[^>]*role="alert"/)
  assert.match(cart, /async function updateItem[\s\S]*try \{[\s\S]*await mutate\([\s\S]*\} catch \{/)
  assert.match(cart, /async function removeItem[\s\S]*try \{[\s\S]*await mutate\([\s\S]*\} catch \{/)
  assert.match(cart, /:disabled="loading"/)
})

test('cart quantity updates keep money and stepper surfaces visually stable while pending', () => {
  const stepper = read('components/commerce/QuantityStepper.vue')
  const money = read('components/commerce/AnimatedMoney.vue')
  assert.match(cart, /:busy="loading"[\s\S]*:disabled="busyCode/)
  assert.equal((cart.match(/:animate="false"/g) || []).length, 3)
  assert.match(money, /if \(!props\.animate \|\|/)
  assert.match(stepper, /:aria-busy="busy \? 'true' : undefined"/)
  assert.match(stepper, /\.quantity-stepper\.is-busy button:disabled \{[\s\S]*?color: var\(--color-navy-800\)/)
  assert.match(stepper, /\.quantity-stepper\.is-busy input:disabled \{[\s\S]*?background: #fff/)
})

test('contact page keeps one CMS-backed title and the working lead form', () => {
  assert.doesNotMatch(contactPage, /contact\.intro|contact\.labels\.formCta|contact\.labels\.formNote/)
  assert.match(contactPage, /<ContactLeadForm \/>/)
  assert.equal((contactForm.match(/<h2\b/g) ?? []).length, 1)
  assert.match(contactForm, /lg:grid-cols-\[minmax\(230px,\.75fr\)_minmax\(0,1\.25fr\)\]/)
})

test('dealer lead form matches PHP field arrangement and examples', () => {
  assert.match(dealerForm, /grid-cols-1[^\n]*min-\[640px\]:grid-cols-2/)
  for (const placeholder of ['Nguyễn Văn A', '09xx xxx xxx', 'email@doanhnghiep.vn', 'Tên đơn vị của bạn']) {
    assert.ok(dealerForm.includes(`placeholder="${placeholder}"`))
  }
  assert.match(dealerForm, /Doanh nghiệp hoặc cửa hàng[\s\S]*min-\[640px\]:col-span-2/)
  assert.match(dealerForm, /min-\[640px\]:col-span-2[\s\S]*Đăng ký ngay/)
})
