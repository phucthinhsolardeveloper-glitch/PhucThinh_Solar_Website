import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { isPublicSolarEstimate, isPublicSolarEstimatorForm } from '../shared/contracts/public-solar-estimator.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')
const form = {
  schemaVersion: 1, enabled: true,
  types: [{ key: 'household', label: 'Hộ gia đình' }, { key: 'business', label: 'Doanh nghiệp, nhà xưởng' }],
  habits: [{ key: 'day', label: 'Dùng nhiều ban ngày' }], regions: [{ key: 'tay-nguyen', label: 'Tây Nguyên' }],
  bill: { household: { min: 300000, max: 20000000, default: 2000000 }, business: { min: 3000000, max: 2000000000, default: 30000000 } },
  businessPrice: 2900,
}
const estimate = {
  type: 'household', typeLabel: 'Hộ gia đình', region: 'Tây Nguyên', habit: 'Ngày và tối như nhau', battery: false, bill: 2000000, usageKwh: 625,
  kwp: 3.1, panels: 5, panelWatt: 620, roofM2: 15, limitedByRoof: false, batteryKwh: 0, outputKwh: 400, saving: 987000, newBill: 1013000,
  savingPercent: 49, cost: { low: 28600000, high: 36500000 }, payback: { low: 2.4, high: 3.1 }, co2Tonnes: 3.2,
  basis: 'Nắng 4,3 kWh/kWp/ngày.', summary: 'Ước tính điện mặt trời\nĐề xuất: 3,1 kWp',
}

test('estimator form and estimate contracts accept the Laravel answers and refuse broken ones', () => {
  assert.equal(isPublicSolarEstimatorForm(form), true)
  assert.equal(isPublicSolarEstimatorForm({ ...form, regions: [] }), false)
  assert.equal(isPublicSolarEstimatorForm({ ...form, bill: { ...form.bill, household: { min: 5, max: 1, default: 2 } } }), false)
  assert.equal(isPublicSolarEstimate(estimate), true)
  assert.equal(isPublicSolarEstimate({ ...estimate, payback: null }), true)
  assert.equal(isPublicSolarEstimate({ ...estimate, kwp: -1 }), false)
  assert.equal(isPublicSolarEstimate({ ...estimate, summary: 42 }), false)
})

test('the estimate page only shows Laravel results and sends the lead with the server summary', () => {
  const page = read('app/components/estimator/SolarEstimatorPage.vue')
  assert.match(page, /\$fetch<Answer>\('\/api\/v1\/solar-estimate'/)
  assert.match(page, /content: result\.value\.summary/)
  assert.match(page, /createError\(\{ statusCode: 404/)
  // One formula only: tariff tiers and sun hours live in Laravel (SolarEstimator), never here.
  assert.doesNotMatch(page, /tiers|1984|3460|kWh\/kWp\/ngày'/)
})

test('solution pages link to the estimator only while it is switched on', () => {
  const solution = read('app/components/solution-detail/SolutionDetailPage.vue')
  assert.match(solution, /publicSite\.value\?\.estimator === true/)
  assert.match(solution, /to="\/uoc-tinh-dien-mat-troi\/"/)
})

test('project pages accept system figures, refuse broken ones and show only filled facts', async () => {
  const { isPublicProjectDetailPayload } = await import('../shared/contracts/public-project-detail.ts')
  const sample = JSON.parse(read('../backend/resources/api-snapshots/project-detail.json'))[0].body
  assert.equal(isPublicProjectDetailPayload(sample), true)
  const withSystem = { ...sample, project: { ...sample.project, system: { type: 'hybrid', typeLabel: 'Hybrid có lưu trữ', kwp: 10.5, storageKwh: null, outputKwhMonth: 1300, savingMonth: null, buildDays: 2 } } }
  assert.equal(isPublicProjectDetailPayload(withSystem), true)
  assert.equal(isPublicProjectDetailPayload({ ...withSystem, project: { ...withSystem.project, system: { ...withSystem.project.system, kwp: -3 } } }), false)
  const view = read('app/components/project-detail/ProjectDetailSystem.vue')
  assert.match(view, /\.filter\(\(fact\) => fact !== null\)/)
  assert.match(read('app/components/project-detail/ProjectDetailPage.vue'), /<ProjectDetailSystem v-if="project\.system"/)
})
