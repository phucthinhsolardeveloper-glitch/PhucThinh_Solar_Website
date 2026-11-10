// Products a visitor picked to compare. Kept in this browser only (a convenience, not shared state):
// at most four, newest last; losing the list just means picking again.
export interface CompareItem { id: number; name: string; image: string }

const KEY = 'pts-compare-v1'
export const COMPARE_MAX = 4

function read(): CompareItem[] {
  try {
    const raw = JSON.parse(localStorage.getItem(KEY) || '[]')
    if (!Array.isArray(raw)) return []
    return raw.filter((item): item is CompareItem => item && Number.isSafeInteger(item.id) && item.id > 0 && typeof item.name === 'string' && typeof item.image === 'string')
      .slice(-COMPARE_MAX)
  } catch {
    return []
  }
}

export function useProductCompare() {
  const items = useState<CompareItem[]>('product-compare', () => [])
  const loaded = useState('product-compare-loaded', () => false)
  const full = ref(false)
  if (import.meta.client && !loaded.value) {
    loaded.value = true
    items.value = read()
    window.addEventListener('storage', (event) => { if (event.key === KEY) items.value = read() })
  }
  const save = () => {
    try { localStorage.setItem(KEY, JSON.stringify(items.value)) } catch { /* private window: the list lives until the tab closes */ }
  }
  const has = (id: number) => items.value.some((item) => item.id === id)
  function toggle(item: CompareItem): boolean {
    full.value = false
    if (has(item.id)) {
      items.value = items.value.filter((entry) => entry.id !== item.id)
    } else if (items.value.length >= COMPARE_MAX) {
      full.value = true
      return false
    } else {
      items.value = [...items.value, item]
    }
    save()
    return true
  }
  function remove(id: number) {
    items.value = items.value.filter((entry) => entry.id !== id)
    save()
  }
  function clear() {
    items.value = []
    save()
  }
  const href = computed(() => items.value.length ? `/so-sanh-san-pham/?ids=${items.value.map((item) => item.id).join(',')}` : '/so-sanh-san-pham/')
  return { items, has, toggle, remove, clear, full, href }
}
