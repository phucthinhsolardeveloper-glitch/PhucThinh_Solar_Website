import type { Ref } from 'vue'
import type { PublicSearchPayload } from '#shared/contracts/public-search'

// Vietnamese typed without accents still has to match, so comparisons run on a folded copy of the same length.
export function foldText(value: string) {
  return [...value].map((char) => char.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().charAt(0) || char).join('')
}

const cache = new Map<string, PublicSearchPayload>()
const CACHE_LIMIT = 30
const DEBOUNCE_MS = 220
const MIN_LENGTH = 2
const RESULT_COUNT = 6

// Live product suggestions: debounced, older requests cancelled, and the previous list stays until the next
// one arrives, so typing never blinks through an empty panel.
export function useProductSuggest(keyword: Ref<string>) {
  const result = shallowRef<PublicSearchPayload | null>(null)
  const pending = ref(false)
  const failed = ref(false)
  const query = computed(() => keyword.value.trim())
  const active = computed(() => query.value.length >= MIN_LENGTH)
  let timer: ReturnType<typeof setTimeout> | undefined
  let controller: AbortController | undefined

  async function load(term: string) {
    const cached = cache.get(term)
    if (cached) {
      result.value = cached
      pending.value = false
      failed.value = false
      return
    }
    controller?.abort()
    const current = new AbortController()
    controller = current
    try {
      const payload = await $fetch<PublicSearchPayload>('/_api/search', {
        query: { keyword: term, per_page: String(RESULT_COUNT) },
        signal: current.signal,
      })
      if (cache.size >= CACHE_LIMIT) cache.delete(cache.keys().next().value as string)
      cache.set(term, payload)
      if (controller === current) {
        result.value = payload
        failed.value = false
        pending.value = false
      }
    } catch {
      if (controller === current) {
        failed.value = true
        pending.value = false
      }
    }
  }

  watch(query, (term) => {
    clearTimeout(timer)
    controller?.abort()
    if (term.length < MIN_LENGTH) {
      result.value = null
      pending.value = false
      failed.value = false
      return
    }
    pending.value = true
    timer = setTimeout(() => load(term), cache.has(term) ? 0 : DEBOUNCE_MS)
  }, { immediate: import.meta.client })

  onBeforeUnmount(() => {
    clearTimeout(timer)
    controller?.abort()
  })

  return { result, pending, failed, active, query }
}

const HISTORY_KEY = 'pts-recent-searches'
const HISTORY_LIMIT = 6

// Kept only in this visitor's browser; the page works the same when storage is unavailable.
export function useSearchHistory() {
  const items = useState<string[]>('recent-searches', () => [])

  function save() {
    try { localStorage.setItem(HISTORY_KEY, JSON.stringify(items.value)) } catch { /* private window */ }
  }

  onMounted(() => {
    try {
      const stored = JSON.parse(localStorage.getItem(HISTORY_KEY) ?? '[]')
      if (Array.isArray(stored)) items.value = stored.filter((item) => typeof item === 'string').slice(0, HISTORY_LIMIT)
    } catch { /* unreadable storage starts empty */ }
  })

  function remember(term: string) {
    const clean = term.trim()
    if (clean.length < MIN_LENGTH) return
    items.value = [clean, ...items.value.filter((item) => foldText(item) !== foldText(clean))].slice(0, HISTORY_LIMIT)
    save()
  }

  function clear() {
    items.value = []
    save()
  }

  return { items, remember, clear }
}
