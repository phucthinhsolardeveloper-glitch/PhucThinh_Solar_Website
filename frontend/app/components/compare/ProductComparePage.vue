<script setup lang="ts">
import type { PublicCatalogPricing } from '#shared/contracts/public-catalog'
import { isPublicComparePayload } from '#shared/contracts/public-compare'
import { isPublicSearchPayload } from '#shared/contracts/public-search'

// The list lives in this browser (useProductCompare); the address carries it so a link can be shared or sent to staff.
const route = useRoute()
const compare = useProductCompare()
const ids = computed(() => typeof route.query.ids === 'string' && /^[1-9][0-9]{0,9}(?:,[1-9][0-9]{0,9}){0,3}$/.test(route.query.ids) ? route.query.ids : '')
const { data, error, pending } = await useFetch<unknown>('/_api/compare', { query: computed(() => ({ ids: ids.value })), immediate: !!ids.value, watch: [ids] })
const payload = computed(() => ids.value && isPublicComparePayload(data.value) ? data.value : null)
const products = computed(() => payload.value?.products ?? [])

// Opened from the floating button without ids: put the picked list in the address.
onMounted(() => {
  if (!ids.value && compare.items.value.length) navigateTo(compare.href.value, { replace: true })
})

const onlyDifferent = ref(false)
const filled = (values: string[]) => values.filter((value) => value !== '').length
// Rows only one product fills cannot be compared; they wait folded under the table.
const shared = computed(() => (payload.value?.rows ?? []).filter((row) => products.value.length < 2 || filled(row.values) > 1))
const rows = computed(() => shared.value.filter((row) => !onlyDifferent.value || !row.same))
const extraRows = computed(() => products.value.length < 2 ? [] : (payload.value?.rows ?? []).filter((row) => filled(row.values) === 1))
const extraOpen = ref(false)
const sameCount = computed(() => shared.value.filter((row) => row.same).length)
const max = computed(() => payload.value?.max ?? 4)

// Add from here: search and pick without leaving the table.
const picking = ref(false)
const query = ref('')
const found = ref<{ id: number; name: string; image: string; price: string }[]>([])
const searching = ref(false)
const pickInput = ref<HTMLInputElement | null>(null)
let searchTimer: ReturnType<typeof setTimeout> | undefined
let searchTicket = 0
watch(query, (text) => {
  clearTimeout(searchTimer)
  const keyword = text.trim()
  if (keyword.length < 2) { found.value = []; searching.value = false; return }
  searching.value = true
  searchTimer = setTimeout(async () => {
    const ticket = ++searchTicket
    const answer = await $fetch<unknown>('/_api/search', { query: { keyword, per_page: '8' } }).catch(() => null)
    if (ticket !== searchTicket) return
    searching.value = false
    found.value = isPublicSearchPayload(answer)
      ? answer.products.map((product) => ({ id: product.id, name: product.name, image: product.media[0] || '', price: product.pricing ? price(product.pricing) : '' }))
      : []
  }, 280)
})
onBeforeUnmount(() => clearTimeout(searchTimer))
function openPicker() {
  picking.value = true
  nextTick(() => { pickInput.value?.focus(); pickInput.value?.scrollIntoView({ block: 'center', behavior: 'smooth' }) })
}
const inTable = (id: number) => products.value.some((product) => product.id === id)
function pick(item: { id: number; name: string; image: string }) {
  if (inTable(item.id) || products.value.length >= max.value) return
  if (!compare.has(item.id)) compare.toggle({ id: item.id, name: item.name, image: item.image })
  const next = [...products.value.map((product) => product.id), item.id]
  query.value = ''
  if (next.length >= max.value) picking.value = false
  navigateTo(`/so-sanh-san-pham/?ids=${next.join(',')}`, { replace: true })
}

function drop(id: number) {
  compare.remove(id)
  const rest = products.value.map((product) => product.id).filter((entry) => entry !== id)
  navigateTo(rest.length ? `/so-sanh-san-pham/?ids=${rest.join(',')}` : '/so-sanh-san-pham/', { replace: true })
}
function clearAll() {
  compare.clear()
  navigateTo('/so-sanh-san-pham/', { replace: true })
}

const price = (pricing: PublicCatalogPricing) => {
  if (pricing.mode !== 'price') return 'Liên hệ báo giá'
  return `${new Intl.NumberFormat('vi-VN').format(pricing.salePrice ?? pricing.regularPrice ?? 0)}đ`
}
const oldPrice = (pricing: PublicCatalogPricing) => pricing.mode === 'price' && pricing.salePrice !== null && pricing.regularPrice !== null && pricing.regularPrice > pricing.salePrice
  ? `${new Intl.NumberFormat('vi-VN').format(pricing.regularPrice)}đ` : ''
const image = (src: string) => src.startsWith('/upload/product/') ? thumbUrl(src, 320, 320) : src

const { openConsultation } = useStorefrontModal()
</script>

<template>
  <div class="text-navy-800">
    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Sản phẩm', to: '/san-pham/' }, { label: 'So sánh sản phẩm' }]" />
        </div>
      </div>

      <div class="pts-container py-8 nav:py-12">
        <header class="cmp-head">
          <h1>So sánh sản phẩm</h1>
          <p v-if="products.length > 1">Thông số của {{ products.length }} sản phẩm đặt cạnh nhau. Chọn tối đa {{ payload?.max ?? 4 }} sản phẩm.</p>
        </header>

        <div v-if="error && ids" class="cmp-empty" role="status">
          <p>Bảng so sánh tạm thời chưa tải được. Vui lòng thử lại sau ít phút.</p>
        </div>
        <div v-else-if="pending && ids && !products.length" class="cmp-empty" role="status"><p>Đang tải thông số…</p></div>
        <div v-else-if="!products.length && !picking" class="cmp-empty">
          <p>Chưa có sản phẩm nào để so sánh. Gõ tên sản phẩm bên dưới, hoặc bấm <b>So sánh</b> trên ảnh sản phẩm trong danh sách.</p>
          <button type="button" class="cmp-btn cmp-btn--gold" @click="openPicker">Chọn sản phẩm để so sánh</button>
        </div>

        <section v-if="picking && products.length < max" class="cmp-picker" aria-label="Thêm sản phẩm để so sánh">
          <div class="cmp-picker__bar">
            <UIcon name="i-lucide-search" class="size-[18px] text-slate-400" aria-hidden="true" />
            <label class="sr-only" for="cmpPickInput">Tìm sản phẩm để thêm vào so sánh</label>
            <input id="cmpPickInput" ref="pickInput" v-model="query" type="search" autocomplete="off" placeholder="Gõ tên, mã hoặc công suất, ví dụ JD-8500">
            <button type="button" class="cmp-link" @click="picking = false; query = ''">Đóng</button>
          </div>
          <p v-if="searching" class="cmp-picker__note" role="status">Đang tìm…</p>
          <p v-else-if="query.trim().length >= 2 && !found.length" class="cmp-picker__note" role="status">Không thấy sản phẩm phù hợp.</p>
          <ul v-if="found.length" class="cmp-picker__list">
            <li v-for="item in found" :key="item.id">
              <button type="button" class="cmp-pick" :disabled="inTable(item.id)" @click="pick(item)">
                <img v-if="item.image" :src="image(item.image)" alt="" width="56" height="56" loading="lazy">
                <span class="cmp-pick__name">{{ item.name }}<small v-if="item.price">{{ item.price }}</small></span>
                <span class="cmp-pick__add">{{ inTable(item.id) ? 'Đã có' : '+ Thêm' }}</span>
              </button>
            </li>
          </ul>
        </section>

        <template v-if="products.length && !(error && ids)">
          <div class="cmp-tools">
            <label v-if="products.length > 1 && sameCount" class="cmp-switch">
              <input v-model="onlyDifferent" type="checkbox">
              <span class="cmp-switch__track" aria-hidden="true" />
              Chỉ hiện điểm khác nhau <small>(ẩn {{ sameCount }} dòng giống nhau)</small>
            </label>
            <span v-else />
            <button type="button" class="cmp-link" @click="clearAll">Xóa tất cả</button>
          </div>

          <div class="cmp-scroll" tabindex="0" aria-label="Bảng so sánh, vuốt ngang để xem thêm">
            <table class="cmp-table" :style="{ '--cmp-cols': products.length }">
              <thead>
                <tr>
                  <th scope="col" class="cmp-corner"><span class="sr-only">Thông số</span></th>
                  <th v-for="product in products" :key="product.id" scope="col" class="cmp-product">
                    <button type="button" class="cmp-remove" :aria-label="`Bỏ ${product.name} khỏi so sánh`" @click="drop(product.id)">
                      <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" />
                    </button>
                    <NuxtLink :to="product.url" class="cmp-product__link">
                      <span class="cmp-product__image">
                        <img v-if="product.image" :src="image(product.image)" :alt="product.name" width="160" height="160" loading="lazy">
                      </span>
                      <span class="cmp-product__name">{{ product.name }}</span>
                    </NuxtLink>
                    <span v-if="product.brand || product.code" class="cmp-product__meta">{{ [product.brand, product.code].filter(Boolean).join(' · ') }}</span>
                    <span class="cmp-product__price">
                      {{ price(product.pricing) }}
                      <s v-if="oldPrice(product.pricing)">{{ oldPrice(product.pricing) }}</s>
                    </span>
                  </th>
                  <th v-if="products.length < max" scope="col" class="cmp-add">
                    <button type="button" class="cmp-add__link" @click="openPicker">
                      <UIcon name="i-lucide-plus" class="size-6" aria-hidden="true" />
                      Thêm sản phẩm
                    </button>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in rows" :key="row.label" :class="{ 'is-same': row.same }">
                  <th scope="row">{{ row.label }}</th>
                  <td v-for="(value, index) in row.values" :key="index">{{ value || '—' }}</td>
                  <td v-if="products.length < max" class="cmp-add-cell" />
                </tr>
                <tr v-if="!rows.length">
                  <th scope="row">Thông số</th>
                  <td :colspan="products.length">{{ onlyDifferent ? 'Các thông số chung đều giống nhau.' : 'Các sản phẩm này chưa có thông số chung để so sánh.' }}</td>
                </tr>
              </tbody>
              <tbody v-if="extraRows.length && extraOpen" class="cmp-extra">
                <tr v-for="row in extraRows" :key="row.label">
                  <th scope="row">{{ row.label }}</th>
                  <td v-for="(value, index) in row.values" :key="index">{{ value || '—' }}</td>
                  <td v-if="products.length < max" class="cmp-add-cell" />
                </tr>
              </tbody>
            </table>
          </div>
          <button v-if="extraRows.length" type="button" class="cmp-more" :aria-expanded="extraOpen" @click="extraOpen = !extraOpen">
            <UIcon :name="extraOpen ? 'i-lucide-chevron-up' : 'i-lucide-chevron-down'" class="size-4" aria-hidden="true" />
            {{ extraOpen ? 'Ẩn' : 'Xem' }} {{ extraRows.length }} thông số chỉ một sản phẩm có ghi
          </button>

          <div class="cmp-cta">
            <p>Chưa chắc chọn loại nào? Kỹ thuật viên sẽ tư vấn theo công trình của bạn.</p>
            <button type="button" class="cmp-btn cmp-btn--gold" @click="openConsultation(`Tư vấn chọn giữa: ${products.map((product) => product.name).join(' / ')}`.slice(0, 250))">Nhận tư vấn chọn sản phẩm</button>
          </div>
        </template>
      </div>
    </main>
  </div>
</template>

<style scoped>
.cmp-head { max-width: 760px; margin-bottom: 20px; }
.cmp-head h1 { margin: 0 0 8px; color: var(--color-navy-950); font-size: clamp(26px, 3.2vw, 38px); font-weight: 900; line-height: 1.15; letter-spacing: -0.02em; }
.cmp-head p { margin: 0; text-align: left; color: var(--color-slate-600, #475569); font-size: 16px; line-height: 1.6; }
.cmp-empty { display: grid; justify-items: start; gap: 16px; min-height: 30vh; padding: 28px; border: 1px dashed rgb(10 34 81 / 18%); border-radius: var(--radius-lg, 14px); background: #fff; }
.cmp-empty p { margin: 0; max-width: 560px; color: #475569; font-size: 16px; line-height: 1.6; }
.cmp-btn { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; padding: 0 22px; border: 0; border-radius: var(--radius-md, 10px); font-size: 15px; font-weight: 800; text-decoration: none; cursor: pointer; }
.cmp-btn--gold { background: var(--pts-gold-gradient, var(--color-gold-500)); color: var(--color-navy-950); }
.cmp-tools { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.cmp-link { border: 0; background: none; color: var(--color-navy-800); font-size: 14px; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
.cmp-switch { position: relative; display: inline-flex; min-height: 44px; align-items: center; gap: 10px; color: var(--color-navy-900); font-size: 15px; font-weight: 700; cursor: pointer; }
.cmp-switch small { color: #64748b; font-size: 13.5px; font-weight: 600; }
.cmp-switch input { position: absolute; opacity: 0; pointer-events: none; }
.cmp-switch__track { position: relative; width: 44px; height: 26px; flex: none; border-radius: 999px; background: #cbd5e1; transition: background .2s ease; }
.cmp-switch__track::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / 25%); transition: transform .2s ease; }
.cmp-switch input:checked + .cmp-switch__track { background: var(--color-gold-500); }
.cmp-switch input:checked + .cmp-switch__track::after { transform: translateX(18px); }
.cmp-switch:has(input:focus-visible) .cmp-switch__track { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }

.cmp-scroll { overflow-x: auto; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-lg, 14px); background: #fff; box-shadow: 0 18px 40px -34px rgb(10 34 81 / 55%); overscroll-behavior-x: contain; }
.cmp-scroll:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
.cmp-table { width: 100%; min-width: calc(96px + var(--cmp-cols) * 132px); border-collapse: separate; border-spacing: 0; font-size: 15px; }
.cmp-table th, .cmp-table td { padding: 12px 14px; border-bottom: 1px solid #eef2f7; text-align: left; vertical-align: top; line-height: 1.5; }
.cmp-table tbody th { position: sticky; left: -1px; z-index: 1; box-shadow: inset 2px 0 0 #f8fafc, 8px 0 12px -10px rgb(10 34 81 / 35%); width: 96px; background: #f8fafc; color: #475569; font-size: 14px; font-weight: 700; }
.cmp-table tbody td { color: var(--color-navy-950); font-weight: 600; overflow-wrap: anywhere; }
.cmp-table tbody tr.is-same td { color: #64748b; font-weight: 500; }
.cmp-table tbody tr:last-child > * { border-bottom: 0; }
@media (min-width: 1024px) { .cmp-table tbody th { width: 200px; } .cmp-table { min-width: calc(200px + var(--cmp-cols) * 200px); } }
/* Stuck one pixel past the edge: at left:0 a sub-pixel strip of the scrolled cells showed through on phones. */
.cmp-corner { position: sticky; left: -1px; z-index: 2; background: #fff; box-shadow: inset 2px 0 0 #fff; }
.cmp-product { position: relative; min-width: 132px; font-weight: 400; }
.cmp-product__link { display: grid; gap: 10px; color: var(--color-navy-950); text-decoration: none; }
.cmp-product__link:hover .cmp-product__name { color: var(--color-gold-700); }
.cmp-product__image { display: grid; aspect-ratio: 1; place-items: center; max-width: 160px; border-radius: var(--radius-md, 10px); background: #f8fafc; }
.cmp-product__image img { width: 100%; height: 100%; object-fit: contain; }
.cmp-product__name { display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 3; font-size: 15px; font-weight: 800; line-height: 1.4; }
.cmp-product__meta { display: block; margin-top: 6px; color: #64748b; font-size: 13.5px; }
.cmp-product__price { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px; margin-top: 8px; color: #b45309; font-size: 16px; font-weight: 900; }
.cmp-product__price s { color: #94a3b8; font-size: 13.5px; font-weight: 600; }
.cmp-remove { position: absolute; top: 8px; right: 8px; z-index: 1; display: grid; width: 34px; height: 34px; place-items: center; border: 1px solid rgb(10 34 81 / 12%); border-radius: 999px; background: #fff; color: #475569; cursor: pointer; }
.cmp-remove:hover { border-color: #dc2626; color: #dc2626; }
.cmp-add { min-width: 150px; }
.cmp-add__link { display: grid; aspect-ratio: 1; max-width: 160px; place-content: center; justify-items: center; gap: 6px; border: 1px dashed rgb(10 34 81 / 22%); border-radius: var(--radius-md, 10px); color: var(--color-navy-800); font-size: 14px; font-weight: 700; text-decoration: none; }
.cmp-add__link:hover { border-color: var(--color-gold-500); color: var(--color-gold-700); }
.cmp-more { display: inline-flex; min-height: 44px; align-items: center; gap: 8px; margin-top: 10px; padding: 0 14px; border: 1px dashed rgb(10 34 81 / 22%); border-radius: var(--radius-md, 10px); background: #fff; color: var(--color-navy-800); font-size: 14px; font-weight: 700; cursor: pointer; }
.cmp-more:hover { border-color: var(--color-gold-500); }
.cmp-extra tr:first-child > * { border-top: 2px solid #e2e8f0; }
.cmp-picker { margin-bottom: 16px; padding: 14px; border: 1px solid rgb(201 150 46 / 45%); border-radius: var(--radius-lg, 14px); background: #fffdf7; box-shadow: 0 18px 40px -34px rgb(10 34 81 / 55%); }
.cmp-picker__bar { display: flex; min-height: 50px; align-items: center; gap: 10px; padding: 0 14px; border: 1px solid rgb(10 34 81 / 16%); border-radius: 999px; background: #fff; }
.cmp-picker__bar:focus-within { border-color: var(--color-gold-500); box-shadow: 0 0 0 3px rgb(201 150 46 / 18%); }
.cmp-picker__bar input { flex: 1; min-width: 0; border: 0; background: transparent; color: var(--color-navy-950); font: inherit; font-size: 16px; outline: none; }
.cmp-picker__note { margin: 10px 4px 0; color: #64748b; font-size: 14px; }
.cmp-picker__list { display: grid; gap: 8px; margin: 12px 0 0; padding: 0; list-style: none; }
@media (min-width: 821px) { .cmp-picker__list { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.cmp-pick { display: flex; width: 100%; min-height: 64px; align-items: center; gap: 12px; padding: 6px 12px 6px 6px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md, 10px); background: #fff; color: var(--color-navy-950); text-align: left; cursor: pointer; }
.cmp-pick:hover:not(:disabled) { border-color: var(--color-gold-500); }
.cmp-pick:disabled { cursor: default; opacity: .6; }
.cmp-pick img { width: 52px; height: 52px; flex: none; border-radius: 8px; background: #f8fafc; object-fit: contain; }
.cmp-pick__name { display: grid; flex: 1; min-width: 0; font-size: 14px; font-weight: 700; line-height: 1.4; }
.cmp-pick__name small { color: #b45309; font-size: 13px; font-weight: 800; }
.cmp-pick__add { flex: none; color: var(--color-navy-800); font-size: 13.5px; font-weight: 800; }
.cmp-add__link { width: 100%; background: transparent; font: inherit; cursor: pointer; }
.cmp-cta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; margin-top: 24px; padding: 20px; border-radius: var(--radius-lg, 14px); background: var(--color-navy-950); color: #fff; }
.cmp-cta p { margin: 0; max-width: 560px; font-size: 16px; font-weight: 600; line-height: 1.5; }
@media (max-width: 639.98px) { .cmp-table th, .cmp-table td { padding: 10px; font-size: 14px; } .cmp-table tbody th { font-size: 13px; } .cmp-product__name { font-size: 14px; } .cmp-remove { width: 30px; height: 30px; top: 4px; right: 4px; } .cmp-add { min-width: 120px; } }
</style>
