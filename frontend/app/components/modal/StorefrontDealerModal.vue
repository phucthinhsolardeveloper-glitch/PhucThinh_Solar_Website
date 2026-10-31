<script setup lang="ts">
import { useStorefrontModal } from '~/composables/useStorefrontModal'
import { useLeadForm } from '~/composables/useLeadForm'

const { isDealerOpen, closeDealer } = useStorefrontModal()
const { security, ensureSecurity, refreshSecurity, submit } = useLeadForm()

const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  cityId: '',
  company: '',
  notes: '',
})

const submitting = ref(false)
const securityLoading = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')
const cityOptions = computed(() => (security.value?.cities ?? []).map((city) => ({ label: city.name, value: String(city.id) })))
const inputUi = {
  base: 'h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const selectUi = {
  base: 'h-14 w-full rounded-btn border border-slate-300 bg-white px-4 text-base font-normal text-navy-950 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[144px] w-full rounded-btn border border-slate-300 bg-white px-4 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}

async function loadSecurity(refresh = false) {
  securityLoading.value = true
  securityError.value = ''
  try {
    const payload = refresh ? await refreshSecurity() : await ensureSecurity()
    if (!payload?.cities?.length) {
      securityError.value = 'Chưa thể tải danh sách tỉnh/thành phố. Vui lòng bấm thử lại.'
    }
  } catch {
      securityError.value = 'Không thể chuẩn bị biểu mẫu. Vui lòng thử tải lại.'
  } finally {
    securityLoading.value = false
  }
}

watch(
  () => isDealerOpen.value,
  (open) => {
    if (open) {
      success.value = ''
      error.value = ''
      void loadSecurity()
    }
  }
)

async function send() {
  success.value = ''
  error.value = ''
  securityError.value = ''

  if (securityLoading.value || !security.value) {
    await loadSecurity()
    if (!security.value) return
  }

  const selectedCity = security.value.cities.find((city) => String(city.id) === form.cityId)
  if (!selectedCity) {
    error.value = 'Vui lòng chọn tỉnh/thành phố trong danh sách.'
    return
  }

  submitting.value = true
  try {
    const companyText = form.notes.trim()
      ? `${form.company.trim()} (${form.notes.trim()})`.slice(0, 180)
      : form.company.trim()

    const result = await submit('dealer', {
      fullname: form.fullname,
      phone: form.phone,
      email: form.email,
      city_id: selectedCity.id,
      company: companyText,
    })

    success.value = result.message || 'Đăng ký đại lý thành công. Bộ phận phát triển đại lý Phúc Thịnh Solar sẽ liên hệ với bạn.'
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.cityId = ''
    form.company = ''
    form.notes = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi đăng ký đối tác & đại lý. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="isDealerOpen"
    :title="'Đăng ký đối tác & Đại lý phân phối'"
    :description="'Hợp tác phát triển năng lượng xanh cùng Phúc Thịnh Solar với chính sách ưu đãi.'"
    :ui="{
      overlay: 'bg-navy-950/70 backdrop-blur-md',
      content: 'w-[min(880px,calc(100vw-32px))] max-w-none max-h-[calc(100dvh-24px)] overflow-y-auto rounded-btn border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-6 pt-7 pb-3 border-b-0 relative sm:px-10 sm:pt-10',
      title: 'text-[clamp(24px,3vw,32px)] font-black tracking-tight text-navy-950',
      description: 'mt-2 max-w-3xl text-[16px] leading-7 text-slate-600',
      body: 'px-6 pt-3 pb-7 sm:px-10 sm:pb-10',
      close: 'text-slate-400 hover:text-slate-700 top-5 right-5',
    }"
  >
    <template #body>
      <div v-if="success" class="space-y-4 py-3 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="space-y-1">
          <h3 class="text-lg font-bold text-navy-900">Đăng ký thành công</h3>
          <p class="text-sm leading-relaxed text-slate-600">{{ success }}</p>
        </div>
        <div class="pt-2">
          <button
            type="button"
            class="min-h-[42px] w-full rounded-btn bg-gold-500 px-5 text-sm font-bold text-navy-950 transition-colors hover:bg-gold-400"
            @click="closeDealer"
          >
            Đóng cửa sổ
          </button>
        </div>
      </div>

      <form v-else class="grid gap-7" @submit.prevent="send">
        <div class="grid min-w-0 gap-x-6 gap-y-5 sm:grid-cols-2">
          <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="09xx xxx xxx" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Email" name="email" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <UInput v-model.trim="form.email" name="email" type="email" required maxlength="150" autocomplete="email" placeholder="email@doanhnghiep.vn" variant="none" class="w-full" :ui="inputUi" />
          </UFormField>
          <UFormField label="Tỉnh/Thành phố" name="cityId" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
            <USelect v-model="form.cityId" name="cityId" required :disabled="securityLoading || !cityOptions.length" :items="cityOptions" :placeholder="securityLoading ? 'Đang tải tỉnh/thành…' : '-- Chọn tỉnh/thành --'" color="neutral" variant="none" class="w-full" :ui="selectUi" />
          </UFormField>
        </div>

        <UFormField label="Khu vực kinh doanh / Tên cửa hàng" name="company" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800' }">
          <UInput v-model.trim="form.company" name="company" required maxlength="180" autocomplete="organization" placeholder="Tên doanh nghiệp, cửa hàng hoặc khu vực dự kiến kinh doanh" variant="none" class="w-full" :ui="inputUi" />
        </UFormField>

        <UFormField label="Ghi chú thêm" hint="Không bắt buộc" name="notes" class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-[15px] font-bold text-navy-800', hint: 'text-sm font-normal text-slate-500' }">
          <UTextarea v-model.trim="form.notes" name="notes" :rows="4" maxlength="1000" placeholder="Năng lực hiện tại, sản phẩm chính quan tâm (đèn mặt trời, điện mặt trời, inverter...)" variant="none" class="w-full" :ui="textareaUi" />
        </UFormField>

        <p v-if="error" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-[4px] bg-red-50 p-3 text-sm font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!securityLoading" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || securityLoading || !cityOptions.length" class="min-h-14 w-full justify-center rounded-btn bg-gold-500 px-6 text-base font-extrabold text-navy-950">
          {{ submitting ? 'Đang gửi đăng ký…' : 'Đăng ký ngay' }}
          <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
        </UButton>
      </form>
    </template>
  </UModal>
</template>
