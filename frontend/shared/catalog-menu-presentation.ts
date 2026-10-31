/**
 * Presentation metadata for the desktop catalog mega-menu.
 * Mirrors domains/.../templates/layout/menu.php (icons, subtitles, slot order).
 * Content (labels/hrefs/children) still comes from the public site catalog API.
 */

export const CATALOG_GROUP_ORDER = ['lighting', 'solar', 'devices'] as const

export type CatalogMenuIconName =
  | 'light' | 'street' | 'flood' | 'ufo' | 'ceiling' | 'garden'
  | 'panel' | 'inverter' | 'battery' | 'pump' | 'fan' | 'cog' | 'headset' | 'shield' | 'truck'

/** SVG path content for viewBox="0 0 24 24", stroke icons (fill=none). */
export const CATALOG_MENU_ICON_PATHS: Record<CatalogMenuIconName, string> = {
  light: '<path d="M7 3h10l3 13H4L7 3Zm3 14v2a2 2 0 0 0 4 0v-2M6 7h12"/>',
  street: '<path d="M8 22V7l8-3m-9 4 1-3 7-2 4 2-1 2-6 1M5 22h6"/>',
  flood: '<rect x="4" y="3" width="16" height="13" rx="2"/><rect x="7" y="6" width="10" height="7" rx="1"/><path d="m8 17-2 4h12l-2-4"/>',
  ufo: '<path d="M9 3h6v3l3 5H6l3-5V3Zm-3 8h12l2 4H4l2-4Zm2 7h8M10 15v3m4-3v3"/>',
  ceiling: '<ellipse cx="12" cy="10" rx="9" ry="5"/><path d="M3 10v3c0 7 18 7 18 0v-3"/>',
  garden: '<path d="m4 7 8-4 8 4H4Zm2 1 2 9h8l2-9M8 10h8M12 8v13M8 22h8"/>',
  panel: '<path d="m5 3-3 13h20L19 3H5ZM3 10h18M4 6h16M9 3 8 16m7-13 1 13M12 16v5m-5 0h10"/>',
  inverter: '<rect x="5" y="2" width="14" height="20" rx="2"/><rect x="8" y="5" width="8" height="5" rx="1"/><path d="m13 12-3 4h4l-3 4"/>',
  battery: '<path d="M9 3V1h6v2"/><rect x="5" y="3" width="14" height="19" rx="2"/><path d="m13 7-4 6h6l-4 5"/>',
  pump: '<path d="M7 5h10v15H7V5Zm3 0V2h4v3m-4 15v2m4-2v2M7 14H3v-4h4m10 4h4v5"/><circle cx="12" cy="12" r="2"/>',
  fan: '<circle cx="12" cy="10" r="8"/><circle cx="12" cy="10" r="1.5"/><path d="M12 8c-5-8-8 2-2 2m4 0c9 0 3-9 0-2m-2 4c-3 8 7 6 2 0M12 18v4m-5 0h10"/>',
  cog: '<circle cx="12" cy="12" r="3"/><path d="M10 2h4l.7 2.4a8 8 0 0 1 1.5.9l2.4-.7 2 3.5-1.8 1.7a8 8 0 0 1 0 1.8l1.8 1.7-2 3.5-2.4-.7a8 8 0 0 1-1.5.9L14 22h-4l-.7-2.4a8 8 0 0 1-1.5-.9l-2.4.7-2-3.5 1.8-1.7a8 8 0 0 1 0-1.8L3.4 10.7l2-3.5 2.4.7a8 8 0 0 1 1.5-.9L10 2Z"/>',
  headset: '<path d="M4 13v-3a8 8 0 0 1 16 0v3M4 12H2v7h4v-7H4Zm16 0h2v7h-4v-7h2ZM19 19v2h-7"/>',
  shield: '<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/>',
  truck: '<path d="M3 6h11v12H3zM14 10h4l3 3v5h-7z"/><circle cx="7.5" cy="18" r="1.5"/><circle cx="17.5" cy="18" r="1.5"/>',
}

const GROUP_META: Record<string, { icon: CatalogMenuIconName; subtitle: string; href?: string }> = {
  lighting: { icon: 'light', subtitle: 'Đèn năng lượng mặt trời', href: '/danh-muc-san-pham/den-nlmt/' },
  solar: { icon: 'panel', subtitle: '' },
  devices: { icon: 'cog', subtitle: '' },
}

const ITEM_ICONS: Record<string, CatalogMenuIconName> = {
  'Đèn pha năng lượng mặt trời': 'flood',
  'Đèn đường năng lượng mặt trời': 'street',
  'Đèn sân vườn': 'garden',
  'Đèn UFO năng lượng mặt trời': 'ufo',
  'Đèn ốp trần': 'ceiling',
  'Tấm pin năng lượng mặt trời': 'panel',
  'Biến tần / Inverter': 'inverter',
  'Pin lưu trữ': 'battery',
  'Thiết bị & phụ kiện': 'cog',
  'Bơm nước năng lượng mặt trời': 'pump',
  'Quạt năng lượng mặt trời': 'fan',
}

export function catalogGroupMeta(groupId: string) {
  return GROUP_META[groupId] ?? { icon: 'cog' as const, subtitle: '' }
}

export function resolveEquipmentIcon(label: string, url = ''): CatalogMenuIconName {
  const direct = ITEM_ICONS[label]
  if (direct) return direct
  const haystack = `${label} ${url}`.toLowerCase()
  if (haystack.includes('inverter') || haystack.includes('bien-tan') || haystack.includes('biến tần')) return 'inverter'
  if (haystack.includes('pin-luu-tru') || haystack.includes('lưu trữ') || haystack.includes('battery')) return 'battery'
  if (haystack.includes('tam-pin') || haystack.includes('tấm pin') || haystack.includes('panel')) return 'panel'
  if (haystack.includes('den-duong') || haystack.includes('đèn đường')) return 'street'
  if (haystack.includes('den-pha') || haystack.includes('đèn pha')) return 'flood'
  if (haystack.includes('san-vuon') || haystack.includes('sân vườn')) return 'garden'
  if (haystack.includes('ufo')) return 'ufo'
  if (haystack.includes('op-tran') || haystack.includes('ốp trần')) return 'ceiling'
  if (haystack.includes('den-') || haystack.includes('đèn')) return 'light'
  if (haystack.includes('quat') || haystack.includes('quạt')) return 'fan'
  if (haystack.includes('bom') || haystack.includes('bơm')) return 'pump'
  return 'cog'
}

export function catalogItemIcon(label: string, url = ''): CatalogMenuIconName {
  return resolveEquipmentIcon(label, url)
}

export function catalogMenuSearchText(label: string, children: Array<{ label: string }>): string {
  return [label, ...children.map((child) => child.label)].join(' ')
}

export function sortCatalogGroups<T extends { id: string }>(groups: T[]): T[] {
  const rank = new Map<string, number>(CATALOG_GROUP_ORDER.map((id, index) => [id, index]))
  return [...groups].sort((left, right) => (rank.get(left.id) ?? 99) - (rank.get(right.id) ?? 99))
}
