<script setup lang="ts">
import type { PublicProductExtrasPayload, PublicProductReview } from '#shared/contracts/public-product-extras'
import { isPublicProductExtrasPayload } from '#shared/contracts/public-product-extras'

const props = defineProps<{ productId: number; slug: string; reviews: PublicProductExtrasPayload['reviews']; csrfToken: string }>()
const runtimeConfig = useRuntimeConfig()
const writing = ref(false)
const submitting = ref(false)
const success = ref('')
const error = ref('')
const form = reactive({ star: 5, title: '', content: '', fullname: '', phone: '', email: '', orderCode: '' })
const photoFiles = ref<File[]>([])
const videoFile = ref<File | null>(null)
const videoPoster = ref<File | null>(null)
const photoInput = ref<HTMLInputElement | null>(null)
const videoInput = ref<HTMLInputElement | null>(null)
const posterInput = ref<HTMLInputElement | null>(null)
const currentReviews = ref<PublicProductExtrasPayload['reviews']>({ ...props.reviews, items: [...props.reviews.items] })
const loadingMoreReviews = ref(false)
const reviewPageError = ref('')
const loadingReplyIds = ref<Set<number>>(new Set())
const replyErrors = ref<Record<number, string>>({})

watch(() => props.reviews, (reviews) => {
  currentReviews.value = { ...reviews, items: [...reviews.items] }
}, { deep: true })

function stars(value: number): string { return '★'.repeat(value) + '☆'.repeat(5 - value) }
function percent(star: number): number {
  const key = String(star) as '1' | '2' | '3' | '4' | '5'
  return currentReviews.value.total ? Math.round((currentReviews.value.counts[key] / currentReviews.value.total) * 100) : 0
}
function dateText(timestamp: number): string {
  if (!timestamp) return ''
  return new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', timeZone: 'Asia/Ho_Chi_Minh' }).format(new Date(timestamp * 1000))
}

function reviewVideoSource(pathname: string): string {
  if (!/^\/upload\/video\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:mp4|mkv)$/i.test(pathname)) return ''
  try {
    const origin = new URL(runtimeConfig.public.phpPublicOrigin)
    if (!['http:', 'https:'].includes(origin.protocol) || origin.username || origin.password
      || origin.pathname !== '/' || origin.search || origin.hash) return ''
    return new URL(pathname, origin).toString()
  } catch {
    return ''
  }
}

function selectPhotos(event: Event) {
  error.value = ''
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  const allowed = /\.(?:jpe?g|png)$/i
  if (files.length > 3) error.value = 'Chỉ được chọn tối đa 3 hình ảnh.'
  else if (files.some((file) => !allowed.test(file.name) || file.size > 30 * 1024 * 1024
    || file.name.includes(',') || file.name.includes('"') || file.name.includes('\\') || /[\[\]{}]/.test(file.name))) {
    error.value = 'Hình ảnh phải là JPG/PNG, tối đa 30 MB mỗi tệp; tên tệp không được chứa dấu phẩy hoặc ngoặc kép.'
  } else photoFiles.value = files
  if (error.value) {
    photoFiles.value = []
    input.value = ''
  }
}

function selectVideo(event: Event) {
  error.value = ''
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (file && (!/\.(?:mp4|mkv)$/i.test(file.name) || file.size > 100 * 1024 * 1024)) {
    error.value = 'Video phải có định dạng MP4/MKV và không vượt quá 100 MB.'
    videoFile.value = null
    input.value = ''
    return
  }
  videoFile.value = file
}

function selectPoster(event: Event) {
  error.value = ''
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] ?? null
  if (file && (!/\.(?:jpe?g|png)$/i.test(file.name) || file.size > 30 * 1024 * 1024)) {
    error.value = 'Ảnh đại diện video phải là JPG/PNG và không vượt quá 30 MB.'
    videoPoster.value = null
    input.value = ''
    return
  }
  videoPoster.value = file
}

function errorMessage(reason: any): string {
  const errors = reason?.data?.errors
  if (Array.isArray(errors) && errors.every((message: unknown) => typeof message === 'string')) return errors.join(' ')
  return reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đánh giá.'
}

async function loadMoreReviews() {
  if (loadingMoreReviews.value || !currentReviews.value.hasMore) return
  loadingMoreReviews.value = true
  reviewPageError.value = ''
  try {
    const payload = await $fetch<unknown>('/_migration/product-extras', {
      query: { slug: props.slug, reviewOffset: String(currentReviews.value.items.length) },
    })
    if (!isPublicProductExtrasPayload(payload)) throw new Error('Invalid review page contract')
    currentReviews.value = { ...payload.reviews, items: [...currentReviews.value.items, ...payload.reviews.items] }
  } catch (reason) {
    reviewPageError.value = errorMessage(reason)
  } finally {
    loadingMoreReviews.value = false
  }
}

async function loadMoreReplies(review: PublicProductReview) {
  if (loadingReplyIds.value.has(review.id) || review.replies.length >= review.repliesTotal) return
  loadingReplyIds.value = new Set(loadingReplyIds.value).add(review.id)
  const nextErrors = { ...replyErrors.value }
  delete nextErrors[review.id]
  replyErrors.value = nextErrors
  try {
    const payload = await $fetch<unknown>('/_migration/product-extras', {
      query: { slug: props.slug, replyParent: String(review.id), replyOffset: String(review.replies.length) },
    })
    if (!isPublicProductExtrasPayload(payload) || !payload.replyPage || payload.replyPage.parentId !== review.id) {
      throw new Error('Invalid review reply page contract')
    }
    const page = payload.replyPage
    currentReviews.value.items = currentReviews.value.items.map((item): PublicProductReview => item.id === review.id
      ? { ...item, replies: [...item.replies, ...page.items], repliesTotal: page.total }
      : item)
  } catch (reason) {
    replyErrors.value = { ...replyErrors.value, [review.id]: errorMessage(reason) }
  } finally {
    const next = new Set(loadingReplyIds.value)
    next.delete(review.id)
    loadingReplyIds.value = next
  }
}

async function sendReview() {
  success.value = ''
  error.value = ''
  if (Boolean(videoFile.value) !== Boolean(videoPoster.value)) {
    error.value = 'Video cần có cả tệp video và hình ảnh đại diện.'
    return
  }
  submitting.value = true
  try {
    let result: any
    if (photoFiles.value.length || videoFile.value || videoPoster.value) {
      // Multipart uploads go directly to the PHP review adapter so its existing
      // Comments::add validation, upload handling, session and IP rate limit stay authoritative.
      const body = new FormData()
      body.set('csrfToken', props.csrfToken)
      body.set('form_csrf', props.csrfToken)
      body.set('productId', String(props.productId))
      body.set('star', String(form.star))
      body.set('title', form.title)
      body.set('content', form.content)
      body.set('fullname', form.fullname)
      body.set('phone', form.phone)
      body.set('email', form.email)
      body.set('orderCode', form.orderCode)
      if (photoFiles.value.length) body.set('fileuploader-list-review-file-photo', JSON.stringify(photoFiles.value.map((file) => file.name)))
      photoFiles.value.forEach((file) => body.append('review-file-photo[]', file, file.name))
      if (videoFile.value) body.append('review-file-video', videoFile.value, videoFile.value.name)
      if (videoPoster.value) body.append('review-poster-video', videoPoster.value, videoPoster.value.name)
      result = await $fetch('/api/pts_v1_review.php', { method: 'POST', credentials: 'same-origin', body })
    } else {
      result = await $fetch('/api/pts_v1_review.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: { csrfToken: props.csrfToken, productId: props.productId, ...form },
      })
    }
    success.value = result.message || 'Đã nhận đánh giá. Nội dung sẽ hiển thị sau khi được duyệt.'
    form.title = ''
    form.content = ''
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.orderCode = ''
    form.star = 5
    photoFiles.value = []
    videoFile.value = null
    videoPoster.value = null
    if (photoInput.value) photoInput.value.value = ''
    if (videoInput.value) videoInput.value.value = ''
    if (posterInput.value) posterInput.value.value = ''
    writing.value = false
  } catch (reason) {
    error.value = errorMessage(reason)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="scroll-mt-24 border-t border-gray-200 py-8" aria-labelledby="product-reviews-title">
    <details id="danh-gia-san-pham" class="border-y border-gray-200">
      <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-4 font-black text-navy-800">
        <span>Đánh giá sản phẩm <small class="ml-1 font-semibold text-slate-500">({{ currentReviews.total }} nhận xét)</small></span>
        <span class="text-2xl font-normal text-gold-700" aria-hidden="true">+</span>
      </summary>
      <div class="pb-6 pt-2">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h2 id="product-reviews-title" class="mb-2 text-[clamp(24px,3vw,34px)] font-black tracking-tight">Đánh giá sản phẩm</h2>
        <p class="mb-0 text-slate-500">{{ currentReviews.total }} nhận xét đã được duyệt</p>
      </div>
      <button type="button" class="min-h-11 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950" @click="writing = !writing">
        {{ writing ? 'Đóng biểu mẫu' : 'Viết nhận xét' }}
      </button>
    </div>

    <div class="grid gap-6 rounded-btn bg-slate-50 p-5 tb:grid-cols-[220px_minmax(0,1fr)]">
      <div class="text-center tb:border-r tb:border-gray-200 tb:pr-6">
        <strong class="block text-4xl">{{ currentReviews.average.toFixed(1) }}/5</strong>
        <span class="mt-2 block text-xl tracking-[.12em] text-gold-700" :aria-label="currentReviews.average + ' trên 5 sao'">{{ stars(Math.round(currentReviews.average)) }}</span>
      </div>
      <div class="space-y-2">
        <div v-for="star in [5,4,3,2,1]" :key="star" class="grid grid-cols-[48px_minmax(0,1fr)_48px] items-center gap-3 text-sm">
          <span>{{ star }} sao</span>
          <span class="h-2 overflow-hidden rounded-full bg-gray-200"><span class="block h-full bg-gold-500" :style="{ width: percent(star) + '%' }" /></span>
          <span class="text-right text-slate-500">{{ percent(star) }}%</span>
        </div>
      </div>
    </div>

    <p v-if="!currentReviews.total" class="mt-6 rounded-chip border border-gray-200 p-5 text-slate-500">Sản phẩm chưa có đánh giá đã được duyệt. Anh/chị có thể gửi nhận xét đầu tiên.</p>

    <div v-else class="mt-7 divide-y divide-gray-200 border-y border-gray-200">
      <article v-for="review in currentReviews.items" :key="review.id" class="py-6">
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
        <video v-if="review.video" controls preload="metadata" :poster="review.video.poster || undefined" class="mt-4 max-h-[420px] w-full max-w-2xl bg-black">
          <source :src="reviewVideoSource(review.video.url)">
          Trình duyệt không phát được video này.
        </video>
        <div v-if="review.replies.length" class="mt-4 space-y-3 border-l-2 border-gold-400 pl-4">
          <div v-for="(reply, index) in review.replies" :key="index">
            <strong class="text-sm">{{ reply.isAdmin ? 'Phúc Thịnh Solar' : (reply.name || 'Phản hồi') }}</strong>
            <p class="mb-0 mt-1 text-sm leading-6 text-slate-600">{{ reply.content }}</p>
          </div>
          <div v-if="replyErrors[review.id]" class="text-sm text-red-700" role="alert">{{ replyErrors[review.id] }}</div>
          <button
            v-if="review.repliesTotal > review.replies.length"
            type="button"
            class="text-sm font-bold text-navy-700 underline"
            :disabled="loadingReplyIds.has(review.id)"
            @click="loadMoreReplies(review)"
          >{{ loadingReplyIds.has(review.id) ? 'Đang tải…' : 'Xem thêm phản hồi' }}</button>
        </div>
      </article>
    </div>
    <div v-if="currentReviews.hasMore || reviewPageError" class="mt-5 text-center">
      <p v-if="reviewPageError" class="text-sm text-red-700" role="alert">{{ reviewPageError }}</p>
      <button type="button" class="min-h-10 rounded-chip border border-gray-300 px-4 font-bold" :disabled="loadingMoreReviews" @click="loadMoreReviews">
        {{ loadingMoreReviews ? 'Đang tải…' : 'Tải thêm nhận xét' }}
      </button>
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
        <label class="grid gap-1.5 text-sm font-bold">Email <span v-if="!currentReviews.requireOrderCode" class="font-normal text-slate-400">(nếu có)</span>
          <input v-model.trim="form.email" type="email" :required="currentReviews.requireOrderCode" maxlength="150" autocomplete="email" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
        </label>
        <label class="grid gap-1.5 text-sm font-bold">Mã đơn hàng <span v-if="!currentReviews.requireOrderCode" class="font-normal text-slate-400">(nếu có)</span>
          <input v-model.trim="form.orderCode" :required="currentReviews.requireOrderCode" maxlength="40" class="h-11 rounded-chip border border-gray-300 px-3 font-normal">
          <small class="font-normal text-slate-500">{{ currentReviews.requireOrderCode ? 'Mã đơn nằm trên trang cảm ơn hoặc email xác nhận đặt hàng.' : 'Nhập đúng mã đơn và thông tin liên hệ để nhận nhãn “Đã mua hàng”.' }}</small>
        </label>
        <div class="grid gap-4 rounded-chip bg-slate-50 p-4 tb:col-span-2">
          <label class="grid gap-1.5 text-sm font-bold">Hình ảnh (tối đa 3 ảnh JPG/PNG, 30 MB mỗi ảnh)
            <input ref="photoInput" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" multiple class="block text-sm font-normal" @change="selectPhotos">
            <small v-if="photoFiles.length" class="font-normal text-slate-500">{{ photoFiles.map((file) => file.name).join(', ') }}</small>
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Video (MP4/MKV, tối đa 100 MB)
            <input ref="videoInput" type="file" accept="video/mp4,video/x-matroska,.mp4,.mkv" class="block text-sm font-normal" @change="selectVideo">
            <small v-if="videoFile" class="font-normal text-slate-500">{{ videoFile.name }}</small>
          </label>
          <label class="grid gap-1.5 text-sm font-bold">Ảnh đại diện video (JPG/PNG)
            <input ref="posterInput" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" class="block text-sm font-normal" @change="selectPoster">
            <small v-if="videoPoster" class="font-normal text-slate-500">{{ videoPoster.name }}</small>
          </label>
        </div>
      </div>
      <p class="mb-0 mt-3 text-xs text-slate-500">Đánh giá được đưa vào hàng chờ duyệt. Mã đơn khớp thông tin mua hàng sẽ được gắn nhãn “Đã mua hàng”.</p>
      <p v-if="success" class="mt-4 rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
      <p v-if="error" class="mt-4 rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
      <button type="submit" :disabled="submitting" class="mt-5 min-h-11 rounded-chip bg-gold-500 px-5 font-extrabold text-navy-950 disabled:opacity-60">
        {{ submitting ? 'Đang gửi…' : 'Gửi nhận xét' }}
      </button>
    </form>
      </div>
    </details>
  </section>
</template>
