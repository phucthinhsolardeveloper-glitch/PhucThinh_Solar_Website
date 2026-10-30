import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('Storefront modal composable exposes consultation and dealer modal state and controls', () => {
  const composable = read('composables/useStorefrontModal.ts')
  assert.match(composable, /export function useStorefrontModal\(\)/)
  assert.match(composable, /isConsultationOpen/)
  assert.match(composable, /isDealerOpen/)
  assert.match(composable, /consultationSubject/)
  assert.match(composable, /openConsultation\(subject\?: string\)/)
  assert.match(composable, /closeConsultation\(\)/)
  assert.match(composable, /openDealer\(\)/)
  assert.match(composable, /closeDealer\(\)/)
})

test('StorefrontConsultationModal renders consultation fields, gold submit action, and connects to lead form', () => {
  const consultationModal = read('components/modal/StorefrontConsultationModal.vue')
  assert.match(consultationModal, /Đăng ký nhận tư vấn giải pháp/)
  assert.match(consultationModal, /kỹ sư Phúc Thịnh Solar sẽ liên hệ tư vấn trong vòng 15 phút/)
  assert.match(consultationModal, /Họ và tên/)
  assert.match(consultationModal, /Số điện thoại/)
  assert.match(consultationModal, /Email/)
  assert.match(consultationModal, /Nhu cầu tư vấn/)
  assert.match(consultationModal, /Điện mặt trời doanh nghiệp/)
  assert.match(consultationModal, /Điện mặt trời gia đình/)
  assert.match(consultationModal, /Đèn NLMT/)
  assert.match(consultationModal, /Bơm nước NLMT/)
  assert.match(consultationModal, /Ghi chú thêm/)
  assert.match(consultationModal, /#c58a2d/)
  assert.match(consultationModal, /submit\('contact'/)
  assert.match(consultationModal, /UModal/)
})

test('StorefrontDealerModal renders dealer fields, city selection, gold submit action, and connects to lead form', () => {
  const dealerModal = read('components/modal/StorefrontDealerModal.vue')
  assert.match(dealerModal, /Đăng ký đối tác & Đại lý phân phối/)
  assert.match(dealerModal, /Hợp tác phát triển năng lượng xanh cùng Phúc Thịnh Solar/)
  assert.match(dealerModal, /Họ và tên/)
  assert.match(dealerModal, /Số điện thoại/)
  assert.match(dealerModal, /Email/)
  assert.match(dealerModal, /Tỉnh\/Thành phố/)
  assert.match(dealerModal, /Khu vực kinh doanh/)
  assert.match(dealerModal, /Ghi chú thêm/)
  assert.match(dealerModal, /#c58a2d/)
  assert.match(dealerModal, /submit\('dealer'/)
  assert.match(dealerModal, /UModal/)
})

test('app.vue mounts both storefront modals inside UApp provider', () => {
  const app = read('app.vue')
  assert.match(app, /StorefrontConsultationModal/)
  assert.match(app, /StorefrontDealerModal/)
  const uappIndex = app.indexOf('<UApp>')
  const consultIndex = app.indexOf('<StorefrontConsultationModal')
  const dealerIndex = app.indexOf('<StorefrontDealerModal')
  const closeUappIndex = app.indexOf('</UApp>')
  assert.ok(uappIndex >= 0 && consultIndex > uappIndex && consultIndex < closeUappIndex)
  assert.ok(uappIndex >= 0 && dealerIndex > uappIndex && dealerIndex < closeUappIndex)
})

test('SiteHeader and SolutionsCta trigger openConsultation on consultation actions', () => {
  const header = read('components/SiteHeader.vue')
  const solutionsCta = read('components/solutions/SolutionsCta.vue')

  assert.match(header, /useStorefrontModal\(\)/)
  assert.match(header, /openConsultation/)
  assert.match(solutionsCta, /useStorefrontModal\(\)/)
  assert.match(solutionsCta, /openConsultation/)
})
