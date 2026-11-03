<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ contact: PublicHomePayload['contact'] }>()
const phoneHref = computed(() => publicPhoneHref(props.contact.hotline))
const emailHref = computed(() => publicEmailHref(props.contact.email))
const { ensureSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', address: '', subject: '', content: '' })
const subjectOptions = [
  'Đèn năng lượng mặt trời',
  'Điện mặt trời gia đình',
  'Điện mặt trời doanh nghiệp',
  'Tấm pin và biến tần',
  'Pin lưu trữ',
  'Hợp tác đại lý',
  'Khác',
].map((label) => ({ label, value: label }))
const inputUi = {
  base: 'h-12 w-full rounded-[3px] border border-slate-300 bg-white px-3.5 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const textareaUi = {
  base: 'min-h-[136px] w-full rounded-[3px] border border-slate-300 bg-white px-3.5 py-3 text-base font-normal text-navy-950 placeholder:text-slate-400 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25',
}
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const submitting = ref(false)
const success = ref('')
const error = ref('')

watch(leadIntent, (requested) => {
  if (!requested) return
  const options = subjectOptions.map((option) => option.value)
  const needle = requested.trim().toLocaleLowerCase()
  form.subject = options.find((option) => option === requested || needle.includes(option.toLocaleLowerCase())) || ''
  leadIntent.value = ''
})

onMounted(() => void ensureSecurity().catch(() => {}))

async function send() {
  success.value = ''
  error.value = ''
  submitting.value = true
  try {
    const result = await submit('contact', {
      fullname: form.fullname,
      phone: form.phone,
      email: '',
      address: form.address,
      subject: form.subject,
      content: form.content,
    })
    success.value = result.message
    form.fullname = ''
    form.phone = ''
    form.address = ''
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
  <section
    id="yeu-cau-tu-van"
    class="bg-hdr-surface py-[clamp(56px,5vw,84px)] pb-[clamp(80px,7vw,104px)]"
    aria-labelledby="home-contact-title"
  >
    <div class="pts-container">
      <header class="mb-8 max-w-[760px]">
        <h2 id="home-contact-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] tracking-[-.025em] text-navy-950">
          Bạn đang cần giải pháp nào?
        </h2>
        <p class="mt-2.5 text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-500">
          Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ trao đổi với bạn.
        </p>
      </header>

      <div class="grid grid-cols-[minmax(300px,.75fr)_minmax(0,1.25fr)] overflow-hidden rounded-[6px] border border-slate-200/90 bg-white shadow-2xs max-[860px]:grid-cols-1">
        <!-- Contact Information Panel -->
        <div class="bg-navy-950 p-[clamp(28px,3vw,44px)] text-[#d4dee8]">
          <h3 class="m-0 text-xl font-bold text-white">Thông tin liên hệ</h3>
          <p class="mt-2 text-sm leading-[1.6] text-slate-300">Đội ngũ Phúc Thịnh Solar phản hồi trong giờ làm việc.</p>

          <div class="mt-6 space-y-4">
            <div v-if="contact.hotline" class="flex flex-col gap-0.5 text-white">
              <small class="text-xs uppercase tracking-wider text-gold-400 font-semibold">Hotline hỗ trợ</small>
              <a v-if="phoneHref" class="text-lg font-bold text-white no-underline hover:text-gold-300 transition-colors" :href="phoneHref">{{ contact.hotline }}</a>
              <strong v-else class="text-lg">{{ contact.hotline }}</strong>
            </div>

            <div v-if="contact.email" class="flex flex-col gap-0.5 text-white">
              <small class="text-xs uppercase tracking-wider text-gold-400 font-semibold">Hòm thư điện tử</small>
              <a v-if="emailHref" class="text-base font-semibold text-white no-underline hover:text-gold-300 transition-colors" :href="emailHref">{{ contact.email }}</a>
              <strong v-else class="text-base">{{ contact.email }}</strong>
            </div>

            <div v-if="contact.address" class="flex flex-col gap-0.5 text-white">
              <small class="text-xs uppercase tracking-wider text-gold-400 font-semibold">Địa chỉ trụ sở</small>
              <span class="text-sm leading-[1.5] text-slate-200">{{ contact.address }}</span>
            </div>

            <div v-if="contact.workingHours" class="flex flex-col gap-0.5 text-white">
              <small class="text-xs uppercase tracking-wider text-gold-400 font-semibold">Giờ làm việc</small>
              <span class="text-sm text-slate-200">{{ contact.workingHours }}</span>
            </div>
          </div>
        </div>

        <!-- Consultation Request Form -->
        <form class="p-[clamp(28px,3vw,44px)]" @submit.prevent="send">
          <div class="grid gap-x-4 tb:grid-cols-2">
            <UFormField label="Họ và tên" name="fullname" required class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.fullname" name="fullname" type="text" maxlength="100" autocomplete="name" required variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
            <UFormField label="Số điện thoại" name="phone" required class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.phone" name="phone" type="tel" maxlength="20" autocomplete="tel" inputmode="tel" required variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
            <UFormField label="Tỉnh/Thành phố" name="address" class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <UInput v-model.trim="form.address" name="address" type="text" maxlength="255" autocomplete="address-level1" placeholder="Ví dụ: Gia Lai" variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
            <UFormField label="Anh/chị quan tâm đến" name="subject" class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
              <USelect v-model="form.subject" name="subject" :items="subjectOptions" placeholder="-- Chọn nhu cầu --" color="neutral" variant="none" class="w-full" :ui="inputUi" />
            </UFormField>
          </div>
          <UFormField label="Nội dung cần trao đổi" name="content" required class="mb-4 grid min-w-0 content-start gap-1.5" :ui="{ label: 'text-sm font-bold text-navy-950' }">
            <UTextarea v-model.trim="form.content" name="content" :rows="4" maxlength="5000" required placeholder="Mô tả ngắn nhu cầu của bạn..." variant="none" class="w-full" :ui="textareaUi" />
          </UFormField>
          <p v-if="success" class="mb-3 rounded-[3px] bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
          <p v-if="error" class="mb-3 rounded-[3px] bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
          <UButton :loading="submitting" :disabled="submitting" class="min-h-[48px] items-center justify-center rounded-[4px] border border-gold-500 bg-gold-400 px-[26px] text-sm font-bold text-navy-950 shadow-2xs transition-all hover:bg-gold-500 hover:shadow-sm active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer" type="submit">
            {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
          </UButton>
        </form>
      </div>
    </div>
  </section>
</template>
