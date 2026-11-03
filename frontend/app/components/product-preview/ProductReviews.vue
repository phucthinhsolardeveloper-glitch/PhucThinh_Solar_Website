<script setup lang="ts">
import type { PublicProductExtrasPayload, PublicProductReview } from '#shared/contracts/public-product-extras'
import { isPublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import { AnimatePresence, motion, useReducedMotion } from 'motion-v'

const props = defineProps<{ productId: number; slug: string; reviews: PublicProductExtrasPayload['reviews']; csrfToken: string }>()
const runtimeConfig = useRuntimeConfig()
const reducedMotion = useReducedMotion()
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
const starOptions = [5, 4, 3, 2, 1].map((value) => ({ label: `${value} sao`, value }))
const quickReviewTags = [
  'Thiết bị hoạt động ổn định, đúng công suất công bố',
  'Đóng gói chắc chắn, giao hàng nhanh',
  'Kỹ thuật viên hỗ trợ hướng dẫn lắp đặt nhiệt tình',
  'Hàng chính hãng, hoàn thiện sắc nét',
]

function applyQuickReviewTag(tag: string) {
  if (!form.title.trim()) form.title = tag
  const current = form.content.trim()
  if (current.includes(tag)) return
  form.content = current ? `${current}\n${tag}.` : `${tag}.`
}
const reviewInputUi = {
  base: 'h-[52px] w-full rounded-btn border border-slate-300 bg-white px-3.5 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const reviewTextareaUi = {
  base: 'min-h-[160px] w-full rounded-btn border border-slate-300 bg-white px-3.5 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}

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

function openPhotoPicker() { photoInput.value?.click() }
function openVideoPicker() { videoInput.value?.click() }
function openPosterPicker() { posterInput.value?.click() }

function clearPhotos() {
  photoFiles.value = []
  if (photoInput.value) photoInput.value.value = ''
}

function clearVideo() {
  videoFile.value = null
  if (videoInput.value) videoInput.value.value = ''
}

function clearPoster() {
  videoPoster.value = null
  if (posterInput.value) posterInput.value.value = ''
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
        <h2 id="product-reviews-title" class="mb-2 text-[clamp(24px,3vw,34px)] font-black tracking-tight text-navy-950">Khách hàng đánh giá</h2>
        <p class="mb-0 text-slate-500">{{ currentReviews.total }} nhận xét đã được duyệt</p>
      </div>
      <UButton type="button" color="primary" size="lg" class="min-h-12 rounded-btn bg-gold-500 px-5 font-bold text-navy-950" @click="writing = !writing">
        <UIcon :name="writing ? 'i-lucide-x' : 'i-lucide-pencil-line'" class="size-4" aria-hidden="true" />
        {{ writing ? 'Đóng biểu mẫu' : 'Viết nhận xét' }}
      </UButton>
    </div>

    <div class="grid gap-6 rounded-btn bg-slate-50 p-5 tb:grid-cols-[220px_minmax(0,1fr)]">
      <div class="text-center tb:border-r tb:border-gray-200 tb:pr-6">
        <strong class="block text-4xl">{{ currentReviews.average.toFixed(1) }}/5</strong>
        <span role="img" class="mt-2 block text-xl tracking-[.12em] text-gold-700" :aria-label="currentReviews.average + ' trên 5 sao'"><span aria-hidden="true">{{ stars(Math.round(currentReviews.average)) }}</span></span>
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
        <div role="img" class="mt-2 text-gold-700" :aria-label="review.star + ' trên 5 sao'"><span aria-hidden="true">{{ stars(review.star) }}</span></div>
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
      <button type="button" class="public-neutral-button min-h-10 rounded-chip px-4 font-bold" :disabled="loadingMoreReviews" @click="loadMoreReviews">
        {{ loadingMoreReviews ? 'Đang tải…' : 'Tải thêm nhận xét' }}
      </button>
    </div>

    <AnimatePresence>
      <motion.div
        v-if="writing"
        key="product-review-form"
        :initial="reducedMotion ? false : { opacity: 0, y: 12 }"
        :animate="{ opacity: 1, y: 0 }"
        :exit="{ opacity: 0, y: 8 }"
        :transition="{ duration: reducedMotion ? 0 : 0.2, ease: 'easeOut' }"
        class="mt-8"
      >
        <form class="grid gap-6 rounded-btn bg-slate-50 px-5 py-6 tb:px-7 tb:py-8" @submit.prevent="sendReview">
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 items-start gap-4">
              <span class="grid size-12 flex-none place-items-center rounded-btn bg-gold-500 text-navy-950">
                <UIcon name="i-lucide-star" class="size-6" aria-hidden="true" />
              </span>
              <div class="min-w-0">
                <h3 class="m-0 text-xl font-extrabold leading-tight text-navy-950">Chia sẻ trải nghiệm của bạn</h3>
                <p class="mb-0 mt-1 text-sm leading-6 text-slate-600">Nhận xét giúp khách hàng khác chọn đúng sản phẩm.</p>
              </div>
            </div>
                <UButton type="button" color="neutral" variant="outline" size="sm" class="public-neutral-button min-h-10 rounded-btn px-3 text-navy-800" @click="writing = false">
              Đóng
            </UButton>
          </div>

          <div class="grid min-w-0 gap-x-5 gap-y-5 tb:grid-cols-2">
            <UFormField label="Đánh giá sao" name="star" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
              <USelect v-model="form.star" name="star" required :items="starOptions" color="neutral" variant="none" class="w-full" :ui="{ base: 'h-[52px] w-full rounded-btn border border-slate-300 bg-white px-3.5 text-base text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25' }" />
            </UFormField>
            <UFormField label="Tiêu đề nhận xét" name="title" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
              <UInput v-model.trim="form.title" name="title" required maxlength="180" variant="none" class="w-full" :ui="reviewInputUi" />
            </UFormField>
            <UFormField label="Nội dung nhận xét" name="content" required class="grid min-w-0 content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
              <div class="mb-1.5 flex flex-wrap gap-1.5">
                <button
                  v-for="tag in quickReviewTags"
                  :key="tag"
                  type="button"
                  class="cursor-pointer rounded-[3px] border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-navy-800 transition-colors hover:border-gold-500 hover:bg-gold-500/15"
                  @click="applyQuickReviewTag(tag)"
                >
                  + {{ tag }}
                </button>
              </div>
              <UTextarea v-model.trim="form.content" name="content" required maxlength="5000" :rows="5" variant="none" class="w-full" :ui="reviewTextareaUi" />
            </UFormField>
            <UFormField label="Họ tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
              <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" variant="none" class="w-full" :ui="reviewInputUi" />
            </UFormField>
            <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
              <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" variant="none" class="w-full" :ui="reviewInputUi" />
            </UFormField>
            <UFormField label="Email" :hint="currentReviews.requireOrderCode ? 'Cần để đối chiếu đơn hàng' : 'Không bắt buộc'" name="email" :required="currentReviews.requireOrderCode" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs font-normal text-slate-500' }">
              <UInput v-model.trim="form.email" name="email" type="email" :required="currentReviews.requireOrderCode" maxlength="150" autocomplete="email" variant="none" class="w-full" :ui="reviewInputUi" />
            </UFormField>
            <UFormField label="Mã đơn hàng" :hint="currentReviews.requireOrderCode ? 'Mã đơn nằm trên trang cảm ơn hoặc email xác nhận.' : 'Không bắt buộc; mã khớp thông tin mua hàng sẽ nhận nhãn Đã mua hàng.'" name="orderCode" :required="currentReviews.requireOrderCode" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs leading-5 font-normal text-slate-500' }">
              <UInput v-model.trim="form.orderCode" name="orderCode" :required="currentReviews.requireOrderCode" maxlength="40" variant="none" class="w-full" :ui="reviewInputUi" />
            </UFormField>
          </div>

          <section class="grid min-w-0 gap-x-5 gap-y-5 border-t border-slate-200 pt-5 sm:grid-cols-2 xl:grid-cols-3" aria-labelledby="review-media-title">
            <h4 id="review-media-title" class="m-0 text-base font-bold text-navy-900 sm:col-span-2 xl:col-span-3">Ảnh và video <span class="font-normal text-slate-500">(không bắt buộc)</span></h4>
            <UFormField label="Hình ảnh" hint="Tối đa 3 ảnh JPG/PNG, 30 MB mỗi ảnh" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs leading-5 font-normal text-slate-500' }">
              <input ref="photoInput" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" multiple tabindex="-1" aria-hidden="true" class="sr-only" @change="selectPhotos">
              <div class="flex min-w-0 flex-wrap items-center gap-2">
                <UButton type="button" color="neutral" variant="outline" size="md" class="public-neutral-button min-h-11 rounded-btn px-3 font-semibold text-navy-800" @click="openPhotoPicker">
                  <UIcon name="i-lucide-image-plus" class="size-4" aria-hidden="true" /> Chọn ảnh
                </UButton>
                <span class="min-w-0 flex-1 text-sm text-slate-500" aria-live="polite">{{ photoFiles.length ? `Đã chọn ${photoFiles.length} ảnh` : 'Chưa chọn tệp' }}</span>
                <UButton v-if="photoFiles.length" type="button" color="neutral" variant="ghost" size="sm" class="min-h-10 rounded-btn px-2 text-navy-700" aria-label="Bỏ ảnh đã chọn" @click="clearPhotos">
                  <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" /> Xóa
                </UButton>
              </div>
              <p v-if="photoFiles.length" class="m-0 break-all text-xs leading-5 text-slate-600">{{ photoFiles.map((file) => file.name).join(', ') }}</p>
            </UFormField>
            <UFormField label="Video" hint="MP4/MKV, tối đa 100 MB" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs leading-5 font-normal text-slate-500' }">
              <input ref="videoInput" type="file" accept="video/mp4,video/x-matroska,.mp4,.mkv" tabindex="-1" aria-hidden="true" class="sr-only" @change="selectVideo">
              <div class="flex min-w-0 flex-wrap items-center gap-2">
                <UButton type="button" color="neutral" variant="outline" size="md" class="public-neutral-button min-h-11 rounded-btn px-3 font-semibold text-navy-800" @click="openVideoPicker">
                  <UIcon name="i-lucide-video" class="size-4" aria-hidden="true" /> Chọn video
                </UButton>
                <span class="min-w-0 flex-1 truncate text-sm text-slate-500" :title="videoFile?.name || undefined" aria-live="polite">{{ videoFile?.name || 'Chưa chọn tệp' }}</span>
                <UButton v-if="videoFile" type="button" color="neutral" variant="ghost" size="sm" class="min-h-10 rounded-btn px-2 text-navy-700" aria-label="Bỏ video đã chọn" @click="clearVideo">
                  <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" /> Xóa
                </UButton>
              </div>
            </UFormField>
            <UFormField label="Ảnh đại diện video" hint="JPG/PNG, tối đa 30 MB" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs leading-5 font-normal text-slate-500' }">
              <input ref="posterInput" type="file" accept="image/jpeg,image/png,.jpg,.jpeg,.png" tabindex="-1" aria-hidden="true" class="sr-only" @change="selectPoster">
              <div class="flex min-w-0 flex-wrap items-center gap-2">
                <UButton type="button" color="neutral" variant="outline" size="md" class="public-neutral-button min-h-11 rounded-btn px-3 font-semibold text-navy-800" @click="openPosterPicker">
                  <UIcon name="i-lucide-image" class="size-4" aria-hidden="true" /> Chọn ảnh bìa
                </UButton>
                <span class="min-w-0 flex-1 truncate text-sm text-slate-500" :title="videoPoster?.name || undefined" aria-live="polite">{{ videoPoster?.name || 'Chưa chọn tệp' }}</span>
                <UButton v-if="videoPoster" type="button" color="neutral" variant="ghost" size="sm" class="min-h-10 rounded-btn px-2 text-navy-700" aria-label="Bỏ ảnh đại diện video đã chọn" @click="clearPoster">
                  <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" /> Xóa
                </UButton>
              </div>
            </UFormField>
          </section>

          <p class="m-0 text-sm leading-6 text-slate-600">Đánh giá được đưa vào hàng chờ duyệt. Mã đơn khớp thông tin mua hàng sẽ được gắn nhãn “Đã mua hàng”.</p>
          <p v-if="success" class="rounded-btn bg-emerald-50 p-4 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
          <p v-if="error" class="rounded-btn bg-red-50 p-4 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
          <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting" class="min-h-14 w-full justify-center rounded-btn bg-gold-500 px-6 text-base font-extrabold text-navy-950 tb:w-auto tb:min-w-[220px]">
            {{ submitting ? 'Đang gửi nhận xét…' : 'Gửi nhận xét' }}
            <UIcon v-if="!submitting" name="i-lucide-send" class="size-5" aria-hidden="true" />
          </UButton>
        </form>
      </motion.div>
    </AnimatePresence>
      </div>
    </details>
  </section>
</template>
