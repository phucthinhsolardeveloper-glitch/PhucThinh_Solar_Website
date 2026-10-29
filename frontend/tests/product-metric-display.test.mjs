import { test } from 'node:test'
import assert from 'node:assert/strict'
import { formatProductMetricValue } from '../shared/product-metric-display.ts'

test('appends units to plain numbers and numeric ranges only', () => {
  assert.equal(formatProductMetricValue('3000', 'lm'), '3000 lm')
  assert.equal(formatProductMetricValue('3.2 - 20', 'V'), '3.2 - 20 V')
  assert.equal(formatProductMetricValue('12 đến 24', 'tháng'), '12 đến 24 tháng')
})

test('preserves descriptive CMS values and values that already include units', () => {
  assert.equal(formatProductMetricValue('Li-ion 3.2V 20AH', 'Wh'), 'Li-ion 3.2V 20AH')
  assert.equal(formatProductMetricValue('2 năm', 'tháng'), '2 năm')
  assert.equal(formatProductMetricValue('120 Wh', 'Wh'), '120 Wh')
})
