<script setup lang="ts">
const props = defineProps<{ name: string; media: string[] }>()
const selectedIndex = ref(0)
const failedMedia = ref<Set<string>>(new Set())

watch(() => props.media, () => {
  selectedIndex.value = 0
  failedMedia.value = new Set()
})

const selectedMedia = computed(() => props.media[selectedIndex.value] ?? props.media[0] ?? '')
const selectedMediaAvailable = computed(() => Boolean(selectedMedia.value) && !failedMedia.value.has(selectedMedia.value))

function selectMedia(index: number) {
  selectedIndex.value = index
}

function markMediaFailed(image: string) {
  failedMedia.value = new Set(failedMedia.value).add(image)
  if (image !== selectedMedia.value) return
  const nextIndex = props.media.findIndex((candidate) => !failedMedia.value.has(candidate))
  if (nextIndex >= 0) selectedIndex.value = nextIndex
}
</script>

<template>
  <div>
    <!--
      Ảnh chính vuông, nền xám nhạt. Ảnh production cho thấy tấm pin nằm lọt
      trong khung nền sáng với lề rộng, nên object-contain chứ không phải cover
      — cover sẽ cắt mất mép module, đúng chi tiết khách nhìn để nhận diện hàng.
    -->
    <div v-if="selectedMediaAvailable" class="grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50">
      <img :src="selectedMedia" :alt="name" class="h-full w-full object-contain" width="720" height="720" @error="markMediaFailed(selectedMedia)">
    </div>
    <p v-else class="text-slate-500">CMS chưa có ảnh sản phẩm hợp lệ.</p>

    <!--
      Dải ảnh nhỏ cuộn ngang. 72px là ngưỡng còn nhận ra được nội dung; nhỏ hơn
      thì các tấm pin trông giống hệt nhau.
      CÒN THIẾU so với ảnh production: bấm ảnh nhỏ chưa đổi ảnh chính và chưa có
      phóng to. Bản PHP dùng MagicZoom; hành vi đó cần đối chiếu riêng nên không
      gắn vội một cơ chế khác.
    -->
    <div v-if="media.length > 1" class="flex gap-2 overflow-x-auto pt-3" aria-label="Thư viện ảnh sản phẩm">
      <button
        v-for="(image, index) in media"
        v-show="!failedMedia.has(image)"
        :key="image"
        type="button"
        class="aspect-square flex-none basis-[72px] overflow-hidden rounded-chip border bg-white"
        :class="selectedIndex === index ? 'border-navy-800' : 'border-gray-200'"
        :aria-label="`Xem ảnh ${index + 1} của ${media.length}`"
        :aria-pressed="selectedIndex === index"
        @click="selectMedia(index)"
      >
        <img :src="image" :alt="`${name} — ảnh ${index + 1}`" class="h-full w-full object-contain" width="112" height="112" loading="lazy" @error="markMediaFailed(image)">
      </button>
    </div>
  </div>
</template>
