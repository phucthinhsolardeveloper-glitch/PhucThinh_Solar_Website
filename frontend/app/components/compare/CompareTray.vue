<script setup lang="ts">
// Follows the visitor around the site once something is picked to compare; hidden on the compare page itself and while paying.
const compare = useProductCompare()
const route = useRoute()
const mounted = ref(false)
onMounted(() => { mounted.value = true })
const visible = computed(() => mounted.value && compare.items.value.length > 0 && !/^\/(?:so-sanh-san-pham|gio-hang|thanh-toan|cam-on)(?:\/|$)/.test(route.path))
const label = computed(() => compare.items.value.length > 1 ? `So sánh ${compare.items.value.length} sản phẩm` : 'Chọn thêm để so sánh')
</script>

<template>
  <Transition name="tray">
    <div v-if="visible" class="cmp-tray m-glass print:hidden">
      <NuxtLink :to="compare.href.value" class="cmp-tray__open" :aria-label="`${label}: ${compare.items.value.map((item) => item.name).join(', ')}`">
        <span class="cmp-tray__thumbs" aria-hidden="true">
          <span v-for="item in compare.items.value" :key="item.id" class="cmp-tray__thumb">
            <img v-if="item.image" :src="item.image.startsWith('/upload/product/') ? thumbUrl(item.image, 96, 96) : item.image" alt="" width="32" height="32">
          </span>
        </span>
        <span class="cmp-tray__label">{{ label }}</span>
      </NuxtLink>
      <button type="button" class="cmp-tray__close" aria-label="Xóa danh sách so sánh" @click="compare.clear()">
        <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" />
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.cmp-tray { position: fixed; left: 24px; bottom: calc(24px + env(safe-area-inset-bottom)); z-index: 890; display: flex; align-items: center; gap: 4px; padding: 6px; border-radius: 16px; background: rgb(10 34 81 / 88%); color: #fff; box-shadow: 0 18px 40px -18px rgb(10 34 81 / 70%); }
@media (max-width: 1023.98px) { .cmp-tray { left: 12px; bottom: calc(88px + env(safe-area-inset-bottom)); } }
.cmp-tray__open { display: flex; min-height: 44px; align-items: center; gap: 10px; padding: 0 10px 0 4px; color: #fff; font-size: 14px; font-weight: 800; text-decoration: none; }
.cmp-tray__thumbs { display: flex; }
.cmp-tray__thumb { display: grid; width: 34px; height: 34px; place-items: center; overflow: hidden; border: 2px solid rgb(255 255 255 / 85%); border-radius: 10px; background: #fff; }
.cmp-tray__thumb + .cmp-tray__thumb { margin-left: -10px; }
.cmp-tray__thumb img { width: 100%; height: 100%; object-fit: contain; }
.cmp-tray__close { display: grid; width: 36px; height: 36px; place-items: center; border: 0; border-radius: 10px; background: rgb(255 255 255 / 12%); color: #fff; cursor: pointer; }
.cmp-tray__close:hover { background: rgb(255 255 255 / 22%); }
.tray-enter-active, .tray-leave-active { transition: opacity .2s ease, transform .2s ease; }
.tray-enter-from, .tray-leave-to { opacity: 0; transform: translateY(8px); }
@media (prefers-reduced-motion: reduce) { .tray-enter-active, .tray-leave-active { transition: none; } }
</style>
