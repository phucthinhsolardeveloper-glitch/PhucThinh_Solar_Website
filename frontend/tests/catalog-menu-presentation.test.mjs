import { test } from 'node:test'
import assert from 'node:assert/strict'
import {
  CATALOG_GROUP_ORDER,
  catalogGroupMeta,
  catalogItemIcon,
  catalogMenuSearchText,
  sortCatalogGroups,
} from '../shared/catalog-menu-presentation.ts'

test('catalog presentation mirrors PHP mega-menu group order and lighting subtitle', () => {
  assert.deepEqual([...CATALOG_GROUP_ORDER], ['lighting', 'solar', 'devices'])
  assert.equal(catalogGroupMeta('lighting').subtitle, 'Đèn năng lượng mặt trời')
  assert.equal(catalogGroupMeta('lighting').href, '/danh-muc-san-pham/den-nlmt/')
  assert.equal(catalogGroupMeta('lighting').icon, 'light')
  assert.equal(catalogItemIcon('Đèn pha năng lượng mặt trời'), 'flood')
  assert.equal(catalogItemIcon('Biến tần / Inverter'), 'inverter')
  assert.equal(catalogItemIcon('Đèn UFO nhập khẩu', '/danh-muc-san-pham/den-nlmt/den-ufo/'), 'ufo')
  assert.equal(catalogMenuSearchText('Pin lưu trữ', [{ label: 'Lithium' }]), 'Pin lưu trữ Lithium')
  assert.deepEqual(
    sortCatalogGroups([{ id: 'devices' }, { id: 'lighting' }, { id: 'solar' }]).map((group) => group.id),
    ['lighting', 'solar', 'devices'],
  )
})
