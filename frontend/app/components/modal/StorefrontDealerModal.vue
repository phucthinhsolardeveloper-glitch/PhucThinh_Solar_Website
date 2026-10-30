<script setup lang="ts">
import { useStorefrontModal } from '~/composables/useStorefrontModal'
import { useLeadForm } from '~/composables/useLeadForm'

const { isDealerOpen, closeDealer } = useStorefrontModal()
const { security, ensureSecurity, refreshSecurity, submit } = useLeadForm()

const form = reactive({
  fullname: '',
  phone: '',
  email: '',
  address: '',
  company: '',
  notes: '',
})

const submitting = ref(false)
const securityLoading = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')

async function loadSecurity(refresh = false) {
  securityLoading.value = true
  securityError.value = ''
  try {
    const payload = refresh ? await refreshSecurity() : await ensureSecurity()
    if (!payload?.cities?.length) {
      securityError.value = 'Chưa thể tải danh sách tỉnh/thành phố. Vui lòng bấm thử lại.'
    }
  } catch {
    securityError.value = 'Không thể tải phiên bảo mật. Vui lòng bấm thử lại.'
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

  submitting.value = true
  try {
    const companyText = form.notes.trim()
      ? `${form.company.trim()} (${form.notes.trim()})`.slice(0, 180)
      : form.company.trim()

    const result = await submit('dealer', {
      fullname: form.fullname,
      phone: form.phone,
      email: form.email,
      address: form.address,
      company: companyText,
    })

    success.value = result.message || 'Đăng ký đại lý thành công. Bộ phận phát triển đại lý Phúc Thịnh Solar sẽ liên hệ với bạn.'
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.address = ''
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
      content: 'w-[calc(100vw-2rem)] max-w-lg rounded-[6px] border border-slate-200 bg-white shadow-2xl divide-y-0 text-navy-950',
      header: 'px-6 pt-6 pb-2 border-b-0 relative',
      title: 'text-[20px] font-black tracking-tight text-navy-900',
      description: 'text-sm text-slate-500 mt-1 leading-relaxed',
      body: 'px-6 pt-2 pb-6',
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
            class="min-h-[42px] w-full rounded-[4px] bg-[#c58a2d] px-5 text-sm font-bold text-white transition-colors hover:bg-[#b07824]"
            @click="closeDealer"
          >
            Đóng cửa sổ
          </button>
        </div>
      </div>

      <form v-else class="space-y-4" @submit.prevent="send">
        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Họ và tên <span class="text-red-600">*</span></span>
            <input
              v-model.trim="form.fullname"
              required
              maxlength="100"
              autocomplete="name"
              placeholder="Nguyễn Văn A"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>

          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Số điện thoại <span class="text-red-600">*</span></span>
            <input
              v-model.trim="form.phone"
              required
              maxlength="20"
              autocomplete="tel"
              inputmode="tel"
              placeholder="09xx xxx xxx"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>
        </div>

        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Email <span class="text-red-600">*</span></span>
            <input
              v-model.trim="form.email"
              required
              type="email"
              maxlength="150"
              autocomplete="email"
              placeholder="email@doanhnghiep.vn"
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
          </label>

          <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
            <span>Tỉnh/Thành phố <span class="text-red-600">*</span></span>
            <select
              v-model="form.address"
              required
              class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
            >
              <option value="">{{ securityLoading ? 'Đang tải tỉnh/thành…' : '-- Chọn tỉnh/thành --' }}</option>
              <option v-for="city in security?.cities ?? []" :key="city.id" :value="city.name">
                {{ city.name }}
              </option>
            </select>
          </label>
        </div>

        <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
          <span>Khu vực kinh doanh / Tên cửa hàng <span class="text-red-600">*</span></span>
          <input
            v-model.trim="form.company"
            required
            maxlength="180"
            autocomplete="organization"
            placeholder="Tên doanh nghiệp, cửa hàng hoặc khu vực dự kiến kinh doanh"
            class="h-10 w-full rounded-[4px] border border-slate-300 bg-white px-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
          >
        </label>

        <label class="grid gap-1 text-xs font-bold uppercase tracking-wider text-navy-900">
          <span>Ghi chú thêm <span class="font-normal lowercase text-slate-400">(không bắt buộc)</span></span>
          <textarea
            v-model.trim="form.notes"
            rows="3"
            maxlength="1000"
            placeholder="Năng lực hiện tại, sản phẩm chính quan tâm (đèn mặt trời, điện mặt trời, inverter...)"
            class="w-full rounded-[4px] border border-slate-300 bg-white p-3 font-normal text-sm text-navy-950 outline-none transition-colors placeholder:text-slate-400 focus:border-[#c58a2d] focus:ring-1 focus:ring-[#c58a2d]"
          />
        </label>

        <p v-if="error" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          {{ error }}
        </p>

        <div v-if="securityError" class="rounded-[4px] bg-red-50 p-2.5 text-xs font-semibold text-red-700" role="alert">
          <span>{{ securityError }}</span>
          <button v-if="!securityLoading" type="button" class="ml-2 font-bold underline" @click="loadSecurity(true)">
            Thử tải lại
          </button>
        </div>

        <div class="pt-1">
          <button
            type="submit"
            :disabled="submitting || securityLoading || !security?.cities.length"
            class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-[4px] bg-[#c58a2d] px-6 text-sm font-extrabold text-white transition-colors hover:bg-[#b07824] disabled:cursor-wait disabled:opacity-60"
          >
            <svg
              v-if="submitting"
              class="h-4 w-4 animate-spin text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            <span>{{ submitting ? 'Đang gửi đăng ký…' : 'Đăng ký ngay' }}</span>
          </button>
        </div>

        <p class="text-center text-[11px] text-slate-400">
          Chính sách đại lý & chiết khấu độc quyền sẽ được gửi tới thông tin liên hệ của bạn.
        </p>
      </form>
    </template>
  </UModal>
</template>
