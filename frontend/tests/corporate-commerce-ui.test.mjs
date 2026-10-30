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
  assert.match(cart, /v-else-if="checkoutRouteEnabled"[\s\S]*to="\/thanh-toan\/"/)
  assert.doesNotMatch(cart, /migration-checkout-preview|Tiếp tục thanh toán/)
  assert.match(cart, /v-else class="mt-4[\s\S]*links\.generic\('\/lien-he\/#yeu-cau-tu-van'\)/)
  assert.doesNotMatch(cart, /Commerce preview|Cart session đã chạy|để thử luồng giỏ hàng Nuxt/)
})

test('cart mutation failures remain visible and are handled by the UI event handlers', () => {
  assert.match(cart, /<p v-if="error"[^>]*role="alert"/)
  assert.match(cart, /async function updateItem[\s\S]*try \{[\s\S]*await mutate\([\s\S]*\} catch \{/)
  assert.match(cart, /async function removeItem[\s\S]*try \{[\s\S]*await mutate\([\s\S]*\} catch \{/)
  assert.match(cart, /:disabled="loading"/)
})

test('contact page renders one PHP-matched form heading and description', () => {
  assert.doesNotMatch(contactPage, /contact\.intro|contact\.labels\.formCta|contact\.labels\.formNote/)
  assert.match(contactPage, /<ContactLeadForm \/>/)
  assert.equal((contactForm.match(/<h2\b/g) ?? []).length, 1)
  assert.match(contactForm, /nav:grid-cols-\[minmax\(245px,315px\)_minmax\(0,1fr\)\]/)
})

test('dealer lead form matches PHP field arrangement and examples', () => {
  assert.match(dealerForm, /grid-cols-1[^\n]*min-\[640px\]:grid-cols-2/)
  for (const placeholder of ['Nguyễn Văn A', '09xx xxx xxx', 'email@doanhnghiep.vn', 'Tên đơn vị của bạn']) {
    assert.ok(dealerForm.includes(`placeholder="${placeholder}"`))
  }
  assert.match(dealerForm, /Doanh nghiệp hoặc cửa hàng[\s\S]*min-\[640px\]:col-span-2/)
  assert.match(dealerForm, /Đăng ký ngay[\s\S]*min-\[640px\]:col-span-2/)
})
