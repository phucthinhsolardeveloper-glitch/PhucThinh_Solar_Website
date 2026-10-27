<script setup lang="ts">
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'

const props = defineProps<{ productId: number; reviews: PublicProductExtrasPayload['reviews']; csrfToken: string }>()
const writing = ref(false)
const submitting = ref(false)
const success = ref('')
const error = ref('')
const form = reactive({ star: 5, title: '', content: '', fullname: '', phone: '', email: '', orderCode: '' })

function stars(value: number): string { return '★'.repeat(value) + '☆'.repeat(5 - value) }
function percent(star: number): number {
  const key = String(star) as '1' | '2' | '3' | '4' | '5'
  return props.reviews.total ? Math.round((props.reviews.counts[key] / props.reviews.total) * 100) : 0
}
function dateText(timestamp: number): string {
  if (!timestamp) return ''
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium' }).format(new Date(timestamp * 1000))
}
async function sendReview() {
  success.value = ''
  error.value = ''
  submitting.value = true
  try {
    const result: any = await $fetch('/_migration/review', {
      method: 'POST',
      body: { csrfToken: props.csrfToken, productId: props.productId, ...form },
    })
    success.value = result.message
    form.title = ''
    form.content = ''
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.orderCode = ''
    form.star = 5
    writing.value = false
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đánh giá.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section id="danh-gia-san-pham" class="scroll-mt-24 border-t border-gray-200 py-10" aria-labelledby="product-reviews-title">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="product-reviews-title" class="mb-2 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Đánh giá sản phẩm</h2>
        <p class="mb-0 text-slate-500">{{ reviews.total }} nhận xét đã được duyệt</p>
      </div>
      <button type="button" class="min-h-11 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950" @click="writing = !writing">
        {{ writing ? 'Đóng biểu mẫu' : 'Viết nhận xét' }}
      </button>
    </div>

    <div class="grid gap-6 rounded-btn bg-slate-50 p-5 tb:grid-cols-[220px_minmax(0,1fr)]">
      <div class="text-center tb:border-r tb:border-gray-200 tb:pr-6">
        <strong class="block text-4xl">{{ reviews.average.toFixed(1) }}/5</strong>
        <span class="mt-2 block text-xl tracking-[.12em] text-gold-700" :aria-label="reviews.average + ' trên 5 sao'">{{ stars(Math.round(reviews.average)) }}</span>
      </div>
      <div class="space-y-2">
        <div v-for="star in [5,4,3,2,1]" :key="star" class="grid grid-cols-[48px_minmax(0,1fr)_48px] items-center gap-3 text-sm">
          <span>{{ star }} sao</span>
          <span class="h-2 overflow-hidden rounded-full bg-gray-200"><span class="block h-full bg-gold-500" :style="{ width: percent(star) + '%' }" /></span>
          <span class="text-right text-slate-500">{{ percent(star) }}%</span>
        </div>
      </div>
    </div>

    <p v-if="!reviews.total" class="mt-6 rounded-chip border border-gray-200 p-5 text-slate-500">Sản phẩm chưa có đánh giá đã được duyệt. Anh/chị có thể gửi nhận xét đầu tiên.</p>

    <div v-else class="mt-7 divide-y divide-gray-200 border-y border-gray-200">
      <article v-for="review in reviews.items" :key="review.id" class="py-6">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
          <strong>{{ review.name || 'Khách hàng' }}</strong>
          <span v-if="review.verified" class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Đã mua hàng</span>
          <time v-if="review.date" class="text-xs text-slate-400">{{ dateText(review.date) }}</time>
        </div>
        <div class="mt-2 text-gold-700" :aria-label="review.star + ' trên 5 sao'">{{ stars(review.star) }}</div>
        <strong v-if="review.title" class="mt-2 block">{{ review.title }}</strong>
        <p class="mb-0 mt-2 whitespace-pre-line leading-7 text-slate-600">{{ review.content }}</p>
        <div v-if="review.photos.length" class="mt-4 flex flex-wrap gap-2">
          <a v-for="photo in review.photos" :key="photo" :href="photo" target="_blank" rel="noopener noreferrer" class="block h-20 w-20 overflow-hidden rounded-chip border border-gray-200">
            <img :src="photo" alt="Ảnh đánh giá của khách hàng" class="h-full w-full object-cover" loading="lazy">
          </a>
        </div>
        <div v-if="review.replies.length" class="mt-4 space-y-3 border-l-2 border-gold-400 pl-4">
          <div v-for="(reply, index) in review.replies" :key="index">
            <strong class="text-sm">{{ reply.isAdmin ? 'Phúc Thịnh Solar' : (reply.name || 'Phản hồi') }}</strong>
            <p class="mb-0 mt-1 text-sm leading-6 text-slate-600">{{ reply.content }}</p>
          </div>
        </div>
      </article>
    </div>

    <form v-if="writing" class="mt-8 rounded-btn border border-gray-200 p-5 nav:p-7" @submit.prevent="sendReview">
      <h3 class="mb-5 text-xl font-black">Gửi nhận xét của bạn</h3>
      <div class="grid gap-4 tb:grid-cols-2">
        <label class="grid gap-1.5 text-sm font-bold">Đánh giá sao
          <select v-model.number="form.star" required class="h-11 rounded-chip border border-gray-300 bg-white px-3 font-normal">
            <option v-for="star in [5,4,3,2,1]" :key="star" :value="star">{{ star }} sao</option>
          </select>
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Tiêu đề nhận xét
          <input v-model.trim="form.title" required maxlength="180" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
        <label class="grid gap-1.5 text-sm font-bold tb:col-span-2">Nội dung
          <textarea v-model.trim="form.content" required maxlength="5000" rows="5" class="rounded-chip border border-gray-300 px-3 py-2 font-normal" />
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Họ tên
          <input v-model.trim="form.fullname" required maxlength="100" autocomplete="name" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Số điện thoại
          <input v-model.trim="form.phone" required maxlength="20" autocomplete="tel" inputmode="tel" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Email <span class="font-normal text-slate-400">(nếu có)</span>
          <input v-model.trim="form.email" type="email" maxlength="150" autocomplete="email" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Mã đơn hàng <span class="font-normal text-slate-400">(nếu có)</span>
          <input v-model.trim="form.orderCode" maxlength="40" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
      </div>
      <p class="mb-0 mt-3 text-xs text-slate-500">Đánh giá được đưa vào hàng chờ duyệt. Mã đơn khớp thông tin mua hàng sẽ được gắn nhãn “Đã mua hàng”.</p>
      <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
      <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
      <button type="submit" :disabled="submitting" class="mt-5 min-h-11 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 disabled:opacity-60">
        {{ submitting ? 'Đang gửi…' : 'Gửi nhận xét' }}
      </button>
    </form>
  </section>
</template>
