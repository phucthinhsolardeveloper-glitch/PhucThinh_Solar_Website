<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'

defineProps<{ form: PublicDealerPayload['form'] }>()

function continueOnPhp() {
  if (import.meta.client) window.location.assign('/dai-ly/#dang-ky-dai-ly')
}
</script>

<template>
  <aside id="dang-ky-dai-ly" class="bg-navy-800 p-6 text-white shadow-xl nav:sticky nav:top-6" aria-labelledby="dealer-registration-title">
    <h2 id="dealer-registration-title" class="text-3xl font-extrabold leading-tight tracking-[-0.03em]">{{ form.title }}</h2>
    <p class="mt-3 text-sm leading-6 text-white/70">{{ form.note }}</p>
    <form class="mt-6 grid gap-4 tb:grid-cols-2" @submit.prevent="continueOnPhp">
      <div><label class="mb-1.5 block text-xs font-bold" for="dealer-fullname">Họ và tên</label><UInput id="dealer-fullname" class="w-full" placeholder="Nguyễn Văn A" autocomplete="name" required /></div>
      <div><label class="mb-1.5 block text-xs font-bold" for="dealer-phone">Số điện thoại</label><UInput id="dealer-phone" class="w-full" type="tel" inputmode="tel" placeholder="09xx xxx xxx" autocomplete="tel" required /></div>
      <div><label class="mb-1.5 block text-xs font-bold" for="dealer-email">Email</label><UInput id="dealer-email" class="w-full" type="email" placeholder="email@doanhnghiep.vn" autocomplete="email" required /></div>
      <div><label class="mb-1.5 block text-xs font-bold" for="dealer-city">Tỉnh/thành phố</label><UInput id="dealer-city" class="w-full" placeholder="Tỉnh/thành phố" autocomplete="address-level1" required /></div>
      <div class="tb:col-span-2"><label class="mb-1.5 block text-xs font-bold" for="dealer-company">Doanh nghiệp hoặc cửa hàng</label><UInput id="dealer-company" class="w-full" placeholder="Tên đơn vị của bạn" autocomplete="organization" required /></div>
      <div class="tb:col-span-2"><UButton type="submit" class="min-h-11 w-full justify-center" color="primary">Đăng ký ngay <UIcon name="i-lucide-arrow-right" class="size-4" /></UButton><p class="mt-3 text-xs leading-5 text-white/55">Biểu mẫu và xác thực tiếp tục được xử lý an toàn trên trang PHP.</p></div>
    </form>
  </aside>
</template>
