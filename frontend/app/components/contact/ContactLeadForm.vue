<script setup lang="ts">
const { security, loadingSecurity, ensureSecurity, refreshSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', email: '', subject: '', content: '' })
const subjectOptions = [
  'Đèn năng lượng mặt trời',
  'Điện mặt trời gia đình',
  'Điện mặt trời doanh nghiệp',
  'Thiết bị (inverter, tấm pin, pin lưu trữ...)',
  'Hợp tác đại lý',
  'Hỗ trợ kỹ thuật / Bảo hành',
]

const subjectIcons: Record<string, string> = {
  'Đèn năng lượng mặt trời': 'i-lucide-lamp',
  'Điện mặt trời gia đình': 'i-lucide-house',
  'Điện mặt trời doanh nghiệp': 'i-lucide-factory',
  'Thiết bị (inverter, tấm pin, pin lưu trữ...)': 'i-lucide-cpu',
  'Hợp tác đại lý': 'i-lucide-handshake',
  'Hỗ trợ kỹ thuật / Bảo hành': 'i-lucide-wrench',
}

const promptSuggestions = computed(() => {
  if (form.subject.includes('Đèn')) {
    return [
      'Cần tư vấn đèn đường / sân vườn NLMT',
      'Số lượng dự kiến: 5–20 bộ',
      'Cần báo giá sỉ / công trình',
    ]
  }
  if (form.subject.includes('doanh nghiệp')) {
    return [
      'Tiền điện hàng tháng trên 15 triệu',
      'Mái nhà xưởng diện tích > 200m²',
      'Cần khảo sát & tính hoàn vốn ROI',
    ]
  }
  if (form.subject.includes('đại lý')) {
    return [
      'Đang kinh doanh thiết bị điện / chiếu sáng',
      'Xin bảng giá và chiết khấu đại lý khu vực',
      'Cần hỗ trợ mẫu trưng bày & catalogue',
    ]
  }
  return [
    'Hóa đơn điện ~2–5 triệu/tháng',
    'Mái tôn / mái ngói diện tích ~60m²',
    'Muốn có lưu trữ dùng khi mất điện',
    'Cần báo giá thiết bị & lắp đặt trọn gói',
  ]
})

function toggleSubject(option: string) {
  form.subject = form.subject === option ? '' : option
}

function appendPrompt(chip: string) {
  const current = form.content.trim()
  if (current.includes(chip)) return
  form.content = current ? `${current}; ${chip}` : chip
}

const submitting = ref(false)
const success = ref('')
const error = ref('')
const securityError = ref('')

async function loadSecurity(refresh = false) {
  securityError.value = ''
  try {
    if (refresh) await refreshSecurity()
    else await ensureSecurity()
  } catch {
    securityError.value = 'Không thể chuẩn bị biểu mẫu. Vui lòng thử tải lại.'
  }
}

onMounted(() => void loadSecurity())

async function send() {
  success.value = ''
  error.value = ''
  if (loadingSecurity.value || !security.value) {
    if (!loadingSecurity.value) await loadSecurity()
    return
  }
  submitting.value = true
  try {
    const result = await submit('contact', form)
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.email = ''
    form.subject = ''
    form.content = ''
  } catch (reason: any) {
    error.value = reason?.data?.statusMessage || reason?.statusMessage || reason?.message || 'Không thể gửi yêu cầu tư vấn.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form id="yeu-cau-tu-van" class="grid min-w-0 gap-8 rounded-chip border-t-[3px] border-gold-500 bg-slate-50 px-5 py-9 tb:px-8 lg:grid-cols-[minmax(230px,.75fr)_minmax(0,1.25fr)] lg:gap-12 lg:px-12 lg:py-12" @submit.prevent="send">
    <div class="flex min-w-0 flex-col justify-between gap-7">
      <div>
        <h2 class="m-0 max-w-md text-[clamp(28px,2.7vw,40px)] font-black leading-tight tracking-tight text-navy-900">Đăng ký nhận tư vấn</h2>
        <p class="mb-0 mt-3.5 max-w-md text-base leading-7 text-slate-600">Cho chúng tôi biết nhu cầu của bạn. Đội ngũ kỹ sư Phúc Thịnh Solar sẽ liên hệ để cùng chọn giải pháp phù hợp nhất.</p>

        <ol class="m-0 mt-7 grid list-none gap-4 border-t border-slate-200/90 pt-6 p-0">
          <li class="flex items-start gap-3">
            <span class="grid size-7 flex-none place-items-center rounded-[3px] bg-navy-900 tabular-nums text-xs font-extrabold text-gold-400">01</span>
            <div class="min-w-0 text-sm">
              <strong class="block font-extrabold text-navy-900">Tiếp nhận & phân tích tải tiêu thụ</strong>
              <span class="text-slate-600">Phản hồi nhanh trong giờ hành chính theo đúng khu vực.</span>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="grid size-7 flex-none place-items-center rounded-[3px] bg-navy-900 tabular-nums text-xs font-extrabold text-gold-400">02</span>
            <div class="min-w-0 text-sm">
              <strong class="block font-extrabold text-navy-900">Đề xuất cấu hình & thiết bị chính hãng</strong>
              <span class="text-slate-600">So sánh rõ công suất, thời gian hoàn vốn và phương án thi công.</span>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <span class="grid size-7 flex-none place-items-center rounded-[3px] bg-navy-900 tabular-nums text-xs font-extrabold text-gold-400">03</span>
            <div class="min-w-0 text-sm">
              <strong class="block font-extrabold text-navy-900">Báo giá minh bạch & bảo hành dài hạn</strong>
              <span class="text-slate-600">Hỗ trợ kỹ thuật xuyên suốt từ kho Gia Lai, Đà Nẵng, TP.HCM.</span>
            </div>
          </li>
        </ol>
      </div>

      <div class="grid gap-2.5 rounded-[4px] bg-navy-950 p-4 text-white">
        <span class="text-sm font-bold text-gold-400">Tổng đài hỗ trợ trực tiếp</span>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
          <a href="tel:0944555957" class="flex items-center gap-2.5 rounded-[3px] bg-white/[.07] px-3 py-2 text-white no-underline transition-colors hover:bg-white/15">
            <UIcon name="i-lucide-phone-call" class="size-4 flex-none text-gold-400" aria-hidden="true" />
            <span class="min-w-0 leading-tight">
              <small class="block text-[11px] text-blue-100/80">Tư vấn bán hàng</small>
              <strong class="text-sm font-black tabular-nums">0944 555 957</strong>
            </span>
          </a>
          <a href="tel:0945536899" class="flex items-center gap-2.5 rounded-[3px] bg-white/[.07] px-3 py-2 text-white no-underline transition-colors hover:bg-white/15">
            <UIcon name="i-lucide-wrench" class="size-4 flex-none text-gold-400" aria-hidden="true" />
            <span class="min-w-0 leading-tight">
              <small class="block text-[11px] text-blue-100/80">Hỗ trợ kỹ thuật</small>
              <strong class="text-sm font-black tabular-nums">0945 536 899</strong>
            </span>
          </a>
        </div>
      </div>
    </div>

    <div class="grid min-w-0 content-start gap-5 tb:grid-cols-2">
      <div class="grid min-w-0 gap-2 tb:col-span-2">
        <span class="text-[15px] font-extrabold text-navy-900">Anh/chị quan tâm đến</span>
        <div class="grid grid-cols-2 gap-2 min-[1101px]:grid-cols-3" role="group" aria-label="Nhu cầu quan tâm">
          <button
            v-for="option in subjectOptions"
            :key="option"
            type="button"
            class="flex min-h-[60px] items-center gap-2.5 rounded-[4px] border px-3 py-2 text-left text-sm font-bold leading-snug transition-colors cursor-pointer focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600"
            :class="form.subject === option
              ? 'border-navy-950 bg-navy-950 text-white'
              : 'border-slate-300 bg-white text-navy-900 hover:border-gold-500'"
            :aria-pressed="form.subject === option"
            @click="toggleSubject(option)"
          >
            <UIcon :name="subjectIcons[option] || 'i-lucide-message-circle'" class="size-5 flex-none" :class="form.subject === option ? 'text-gold-400' : 'text-gold-700'" aria-hidden="true" />
            {{ option }}
          </button>
        </div>
      </div>

      <UFormField label="Họ và tên" name="fullname" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <UInput v-model.trim="form.fullname" name="fullname" required maxlength="100" autocomplete="name" placeholder="Nguyễn Văn A" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Số điện thoại" name="phone" required class="grid min-w-0 content-start gap-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <UInput v-model.trim="form.phone" name="phone" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="090xxxxxxx" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Email" hint="Không bắt buộc" name="email" class="grid min-w-0 content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-800', hint: 'text-xs font-normal text-slate-500' }">
        <UInput v-model.trim="form.email" name="email" type="email" maxlength="150" autocomplete="email" placeholder="abc@gmail.com" variant="none" class="w-full" :ui="{ base: 'h-12 w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>
      <UFormField label="Chi tiết nhu cầu tư vấn" name="content" required class="grid min-w-0 content-start gap-2 tb:col-span-2" :ui="{ label: 'text-sm font-semibold text-navy-800' }">
        <div class="mb-1.5 flex flex-wrap gap-1.5" aria-label="Gợi ý mô tả nhanh">
          <button
            v-for="chip in promptSuggestions"
            :key="chip"
            type="button"
            class="min-h-[34px] rounded-full border border-dashed border-navy-900/30 bg-white px-3 text-[13.5px] font-semibold text-navy-800 transition-colors hover:border-gold-600 cursor-pointer"
            @click="appendPrompt(chip)"
          >
            + {{ chip }}
          </button>
        </div>
        <UTextarea v-model.trim="form.content" name="content" required maxlength="5000" :rows="4" placeholder="Ví dụ: cần tư vấn điện mặt trời cho gia đình, mức sử dụng điện hàng tháng..." variant="none" class="w-full" :ui="{ base: 'min-h-[132px] w-full min-w-0 rounded-chip border border-slate-300 bg-white px-3 py-3 text-base font-normal text-navy-800 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/30' }" />
      </UFormField>

      <p v-if="success" class="rounded-chip bg-emerald-50 p-3 text-sm font-semibold text-emerald-800 tb:col-span-2" role="status">{{ success }}</p>
      <p v-if="error" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">{{ error }}</p>
      <div v-if="securityError" class="rounded-chip bg-red-50 p-3 text-sm font-semibold text-red-800 tb:col-span-2" role="alert">
        <p class="mb-2">{{ securityError }}</p>
        <button v-if="!loadingSecurity" type="button" class="font-bold underline" @click="loadSecurity(true)">Thử tải lại</button>
      </div>
      <p v-else-if="loadingSecurity" class="text-sm text-slate-500 tb:col-span-2" role="status">Đang chuẩn bị biểu mẫu…</p>
      <UButton type="submit" color="primary" size="xl" :loading="submitting" :disabled="submitting || loadingSecurity || !security" class="inline-flex min-h-[52px] w-full items-center justify-center gap-2 rounded-chip bg-gold-500 px-6 font-extrabold text-navy-950 transition-colors hover:bg-gold-400 disabled:cursor-wait disabled:opacity-60 tb:col-span-2">
        {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
        <UIcon v-if="!submitting" name="i-lucide-arrow-right" class="size-5" aria-hidden="true" />
      </UButton>
    </div>
  </form>
</template>
