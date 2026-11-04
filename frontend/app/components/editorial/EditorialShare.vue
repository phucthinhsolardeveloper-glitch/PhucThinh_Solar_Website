<script setup lang="ts">
const props = defineProps<{ title: string; canonicalPath: string }>()
const requestUrl = useRequestURL()

const pageUrl = computed(() => new URL(props.canonicalPath, requestUrl.origin).toString())
const copied = ref(false)
let copyTimer: ReturnType<typeof setTimeout> | null = null

async function handleCopy(): Promise<void> {
  try {
    if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(pageUrl.value)
    } else if (typeof document !== 'undefined') {
      const input = document.createElement('textarea')
      input.value = pageUrl.value
      document.body.appendChild(input)
      input.select()
      document.execCommand('copy')
      document.body.removeChild(input)
    }
    copied.value = true
    if (copyTimer) clearTimeout(copyTimer)
    copyTimer = setTimeout(() => {
      copied.value = false
    }, 2200)
  } catch {
    // Clipboard permission denied or unsupported
  }
}

onBeforeUnmount(() => {
  if (copyTimer) clearTimeout(copyTimer)
})

const shareLinks = computed(() => {
  const url = encodeURIComponent(pageUrl.value)
  const title = encodeURIComponent(props.title)
  return [
    {
      type: 'facebook',
      label: 'Facebook',
      href: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
      className: 'bg-[#1877f2] text-white hover:bg-[#166fe5]',
    },
    {
      type: 'zalo',
      label: 'Zalo',
      href: `https://sp.zalo.me/plugins/share?url=${url}`,
      className: 'bg-[#0068ff] text-white hover:bg-[#0057d6]',
    },
    {
      type: 'x',
      label: 'X (Twitter)',
      href: `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
      className: 'bg-[#0f1419] text-white hover:bg-black',
    },
    {
      type: 'linkedin',
      label: 'LinkedIn',
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
      className: 'bg-[#0a66c2] text-white hover:bg-[#084e96]',
    },
    {
      type: 'pinterest',
      label: 'Pinterest',
      href: `https://pinterest.com/pin/create/button/?url=${url}&description=${title}`,
      className: 'bg-[#bd081c] text-white hover:bg-[#a00618]',
    },
    {
      type: 'email',
      label: 'Email',
      href: `mailto:?subject=${title}&body=${url}`,
      className: 'bg-gold-500 text-navy-950 hover:bg-gold-400',
    },
  ]
})
</script>

<template>
  <div class="mt-8 flex flex-wrap items-center gap-3 border-t border-slate-200/80 pt-6" aria-label="Chia sẻ bài viết">
    <span class="mr-1 text-sm font-bold text-navy-800">Chia sẻ:</span>
    <nav class="flex flex-wrap items-center gap-2.5" aria-label="Mạng xã hội chia sẻ">
      <a
        v-for="item in shareLinks"
        :key="item.label"
        class="inline-flex size-10 items-center justify-center rounded-full shadow-xs transition-all duration-200 hover:scale-106 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600 active:scale-95 motion-reduce:transform-none motion-reduce:transition-none"
        :class="item.className"
        :href="item.href"
        :aria-label="`Chia sẻ lên ${item.label}`"
        :title="`Chia sẻ lên ${item.label}`"
        target="_blank"
        rel="noopener noreferrer"
      >
        <!-- Facebook icon -->
        <svg v-if="item.type === 'facebook'" class="size-4.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
        </svg>

        <!-- Zalo icon -->
        <svg v-else-if="item.type === 'zalo'" class="size-5 fill-current" viewBox="0 0 48 48" aria-hidden="true">
          <path d="M24 4C12.95 4 4 12.95 4 24c0 4.14 1.26 7.99 3.42 11.19L4.5 43.5l8.61-2.82C16.14 42.6 19.93 44 24 44c11.05 0 20-8.95 20-20S35.05 4 24 4zm-7.5 15h15c.83 0 1.5.67 1.5 1.5 0 .5-.25.96-.66 1.24L21.4 30h11.1c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-15c-.83 0-1.5-.67-1.5-1.5 0-.5.25-.96.66-1.24L27.6 23H16.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5z" />
        </svg>

        <!-- X (Twitter) icon -->
        <svg v-else-if="item.type === 'x'" class="size-3.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
        </svg>

        <!-- LinkedIn icon -->
        <svg v-else-if="item.type === 'linkedin'" class="size-4 fill-current" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.4 1.4 0 1 0 0-2.8 1.4 1.4 0 0 0 0 2.8m1.4 9.74V10.13H5.06v8.37h2.8z" />
        </svg>

        <!-- Pinterest icon -->
        <svg v-else-if="item.type === 'pinterest'" class="size-4.5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M12.04 2C6.58 2 3.9 5.92 3.9 10.11c0 3.27 1.83 6.12 4.58 7.2-.02-.61-.01-1.35.15-2.02.17-.71 1.13-4.78 1.13-4.78s-.28-.56-.28-1.39c0-1.3.75-2.27 1.68-2.27.79 0 1.17.59 1.17 1.3 0 .79-.5 1.97-.76 3.06-.22.91.46 1.65 1.36 1.65 1.63 0 2.89-1.72 2.89-4.2 0-2.2-1.58-3.74-3.83-3.74-2.61 0-4.15 1.96-4.15 3.99 0 .79.3 1.63.68 2.09.07.09.08.17.06.26l-.25 1.02c-.04.16-.13.2-.3.12-1.11-.52-1.8-2.14-1.8-3.44 0-2.8 2.03-5.38 5.86-5.38 3.08 0 5.48 2.2 5.48 5.14 0 3.07-1.94 5.54-4.63 5.54-.9 0-1.74-.47-2.03-1.02l-.56 2.13c-.2.78-.74 1.75-1.1 2.34.83.26 1.71.4 2.63.4 5.46 0 9.38-3.92 9.38-9.38C21.42 5.92 17.5 2 12.04 2z" />
        </svg>

        <!-- Mail icon -->
        <svg v-else-if="item.type === 'email'" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect width="20" height="16" x="2" y="4" rx="2" />
          <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
        </svg>
      </a>

      <!-- Copy Link button with tooltip feedback -->
      <div class="relative inline-flex items-center">
        <button
          type="button"
        class="public-neutral-button inline-flex size-10 items-center justify-center rounded-full text-navy-800 transition-all duration-200 hover:scale-106 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600 active:scale-95 motion-reduce:transform-none motion-reduce:transition-none"
          :aria-label="copied ? 'Đã sao chép liên kết' : 'Sao chép liên kết'"
          :title="copied ? 'Đã sao chép liên kết' : 'Sao chép liên kết'"
          @click="handleCopy"
        >
          <svg v-if="!copied" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
          </svg>
          <svg v-else class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </button>

        <!-- Tooltip feedback -->
        <span
          v-if="copied"
          role="status"
          class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-card bg-navy-950 px-2.5 py-1 text-xs font-medium text-white shadow-md transition-all"
        >
          Đã sao chép!
        </span>
      </div>
    </nav>
  </div>
</template>
