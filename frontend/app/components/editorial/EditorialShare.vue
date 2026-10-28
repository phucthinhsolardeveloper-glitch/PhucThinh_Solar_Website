<script setup lang="ts">
const props = defineProps<{ title: string; canonicalPath: string }>()
const requestUrl = useRequestURL()

const pageUrl = computed(() => new URL(props.canonicalPath, requestUrl.origin).toString())
const shareLinks = computed(() => {
  const url = encodeURIComponent(pageUrl.value)
  const title = encodeURIComponent(props.title)
  return [
    { label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${url}` },
    { label: 'X', href: `https://twitter.com/intent/tweet?url=${url}&text=${title}` },
    { label: 'LinkedIn', href: `https://www.linkedin.com/sharing/share-offsite/?url=${url}` },
    { label: 'Email', href: `mailto:?subject=${title}&body=${url}` },
  ]
})
</script>

<template>
  <nav class="mt-8 flex flex-wrap items-center gap-3" aria-label="Chia sẻ bài viết">
    <span class="mr-1 font-bold text-navy-800">Chia sẻ</span>
    <a
      v-for="item in shareLinks"
      :key="item.label"
      class="inline-flex min-h-11 items-center border border-gray-200 px-3 font-semibold text-navy-800 no-underline transition-colors hover:border-gold-500 hover:text-gold-700 focus-visible:outline-2"
      :href="item.href"
      :aria-label="`Chia sẻ lên ${item.label}`"
      target="_blank"
      rel="noopener noreferrer"
    >{{ item.label }}</a>
  </nav>
</template>
