<script setup lang="ts">
import type { PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import type { PublicCatalogPayload, PublicCatalogProduct } from '#shared/contracts/public-catalog'

const props = defineProps<{ article: PublicNewsDetailPayload['article'] }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()

// Topic -> catalog category. The article is scored on its own words, title weighted highest.
const topics = [
  { slug: 'den-nlmt', name: 'Đèn năng lượng mặt trời', keys: ['đèn', 'chiếu sáng', 'chip led', 'đèn pha', 'đèn đường'] },
  { slug: 'tam-pin-nang-luong-mat-troi', name: 'Tấm pin năng lượng mặt trời', keys: ['tấm pin', 'pin mặt trời', 'tấm quang điện', 'mái nhà', 'điện mặt trời'] },
  { slug: 'bien-tan-inverter', name: 'Biến tần (Inverter)', keys: ['biến tần', 'inverter'] },
  { slug: 'pin-luu-tru', name: 'Pin lưu trữ', keys: ['lưu trữ', 'pin lithium', 'bess', 'ắc quy'] },
  { slug: 'bien-tan-bom', name: 'Bơm nước năng lượng mặt trời', keys: ['bơm nước', 'máy bơm', 'tưới'] },
]
function plain(html: string) { return html.replace(/<[^>]+>/g, ' ').toLocaleLowerCase('vi') }
function count(text: string, key: string) { return text.split(key).length - 1 }
const topic = computed(() => {
  const title = props.article.title.toLocaleLowerCase('vi')
  const body = `${props.article.description} ${plain(props.article.content.html || '')}`.toLocaleLowerCase('vi')
  const scored = topics
    .map((t) => ({ t, score: t.keys.reduce((sum, key) => sum + count(title, key) * 5 + count(body, key), 0) }))
    .sort((a, b) => b.score - a.score)
  return scored[0] && scored[0].score >= 3 ? scored[0].t : null
})

const products = ref<PublicCatalogProduct[]>([])
const categoryUrl = ref('')
const rootRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | undefined

async function load() {
  if (!topic.value) return
  try {
    const payload = await $fetch<PublicCatalogPayload>('/_api/catalog', { query: { category: topic.value.slug, per_page: 12 } })
    const withImage = payload.products.filter((product) => product.media[0])
    // Priced items first: a visitor can act on them immediately.
    products.value = [...withImage.filter((p) => p.pricing?.mode === 'price'), ...withImage.filter((p) => p.pricing?.mode !== 'price')].slice(0, 4)
    categoryUrl.value = (payload.category && 'url' in payload.category ? payload.category.url : '') || `/danh-muc-san-pham/${topic.value.slug}/`
  } catch { products.value = [] }
}
onMounted(() => {
  if (!rootRef.value || !('IntersectionObserver' in window)) { void load(); return }
  observer = new IntersectionObserver((records) => {
    if (!records.some((r) => r.isIntersecting)) return
    observer?.disconnect()
    void load()
  }, { rootMargin: '800px 0px' })
  observer.observe(rootRef.value)
})
onBeforeUnmount(() => observer?.disconnect())

function thumb(src: string) { return src.startsWith('/upload/product/') ? phpThumb(src, 480, 480) : src }
function price(product: PublicCatalogProduct) {
  const p = product.pricing
  if (!p || p.mode !== 'price') return 'Liên hệ báo giá'
  const value = p.salePrice ?? p.regularPrice
  return value ? `${new Intl.NumberFormat('vi-VN').format(value)}đ` : 'Liên hệ báo giá'
}
</script>

<template>
  <div ref="rootRef">
    <section v-if="topic && products.length" class="article-products" aria-labelledby="article-products-title">
      <header class="article-products-head">
        <div>
          <h2 id="article-products-title">Sản phẩm cho chủ đề này</h2>
          <p>{{ topic.name }} đang có tại Phúc Thịnh Solar.</p>
        </div>
        <a :href="links.generic(categoryUrl)">Xem tất cả <span aria-hidden="true">→</span></a>
      </header>
      <ul>
        <li v-for="product in products" :key="product.id">
          <a class="article-product" :href="links.generic(product.url)">
            <span class="article-product-media"><img :src="thumb(product.media[0]!)" :alt="product.name" width="240" height="240" loading="lazy" decoding="async"></span>
            <strong>{{ product.name }}</strong>
            <span class="article-product-price" :class="{ 'is-quote': product.pricing?.mode !== 'price' }">{{ price(product) }}</span>
          </a>
        </li>
      </ul>
      <button type="button" class="article-products-cta" @click="openConsultation(`${topic.name} (từ bài: ${article.title})`)">
        Cần chọn đúng sản phẩm? Nhận tư vấn miễn phí <span aria-hidden="true">→</span>
      </button>
    </section>
  </div>
</template>

<style scoped>
.article-products { margin: clamp(40px, 5vw, 64px) 0 0; padding: clamp(22px, 3vw, 40px); border-radius: var(--radius-sm); background: linear-gradient(160deg, #0f2f5e, var(--color-navy-950) 75%); color: #fff; }
.article-products-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 12px 24px; margin-bottom: 22px; }
.article-products-head h2 { margin: 0; font-size: clamp(22px, 2vw, 30px); font-weight: 900; letter-spacing: -0.02em; }
.article-products-head p { margin: 6px 0 0; color: #b9c6d6; font-size: 15.5px; }
.article-products-head a { color: var(--color-gold-300); font-size: 15px; font-weight: 800; text-decoration: none; }
.article-products-head a:hover { color: #fff; }
.article-products ul { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 0; padding: 0; list-style: none; }
.article-product { display: flex; height: 100%; flex-direction: column; gap: 8px; padding: 12px; border-radius: var(--radius-btn); background: #fff; color: var(--color-navy-950); text-decoration: none; transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.3s ease; }
.article-product:hover { transform: translateY(-4px); box-shadow: 0 18px 30px -18px rgb(0 0 0 / 55%); }
.article-product:focus-visible { outline: 3px solid var(--color-gold-400); outline-offset: 3px; }
.article-product-media { display: block; aspect-ratio: 1; }
.article-product-media img { width: 100%; height: 100%; object-fit: contain; }
.article-product strong { display: -webkit-box; overflow: hidden; font-size: 14.5px; font-weight: 700; line-height: 1.4; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
.article-product-price { margin-top: auto; color: #d52525; font-size: 16px; font-weight: 900; }
.article-product-price.is-quote { color: var(--color-gold-700); font-size: 14.5px; font-weight: 800; }
.article-products-cta { margin-top: 18px; min-height: 50px; padding: 0 22px; border: 0; border-radius: var(--radius-btn); background: var(--pts-gold-gradient); color: var(--color-navy-950); font: inherit; font-size: 15px; font-weight: 800; cursor: pointer; }
.article-products-cta:hover { box-shadow: inset 0 0 0 999px rgb(255 255 255 / 9%); }
.article-products-cta:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }
@media (max-width: 860px) { .article-products ul { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (prefers-reduced-motion: reduce) { .article-product { transition: none; } .article-product:hover { transform: none; } }
</style>
