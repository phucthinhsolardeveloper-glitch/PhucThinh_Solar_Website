<script setup lang="ts">
import { publicEmailHref, publicPhoneHref } from '#shared/contact-links'
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ contact: PublicHomePayload['contact'] }>()
const phoneHref = computed(() => publicPhoneHref(props.contact.hotline))
const emailHref = computed(() => publicEmailHref(props.contact.email))
const { ensureSecurity, submit } = useLeadForm()
const form = reactive({ fullname: '', phone: '', address: '', subject: '', content: '' })
const leadIntent = useState<string>('public-home-lead-intent', () => '')
const submitting = ref(false)
const success = ref('')
const error = ref('')

watch(leadIntent, (requested) => {
  if (!requested) return
  const options = [
    'Đèn năng lượng mặt trời',
    'Điện mặt trời gia đình',
    'Điện mặt trời doanh nghiệp',
    'Tấm pin và biến tần',
    'Pin lưu trữ',
    'Hợp tác đại lý',
    'Khác',
  ]
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
    class="bg-hdr-surface py-[clamp(64px,6vw,92px)] pb-[clamp(88px,7vw,108px)]"
    aria-labelledby="home-contact-title"
  >
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <header class="mb-10 max-w-[760px]">
        <h2 id="home-contact-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950">
          Bạn đang cần giải pháp nào?
        </h2>
        <p class="mt-[13px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">
          Để lại thông tin và nhu cầu, Phúc Thịnh Solar sẽ liên hệ trao đổi với bạn.
        </p>
      </header>

      <div class="grid grid-cols-[minmax(310px,.72fr)_minmax(0,1.28fr)] overflow-hidden rounded-[4px] bg-white max-[860px]:grid-cols-1">
        <div class="bg-navy-950 p-[clamp(30px,3.2vw,48px)] text-[#d4dee8]">
          <h3 class="m-0 text-2xl font-bold text-white">Thông tin liên hệ</h3>
          <p class="mt-3 leading-[1.65] text-[#d4dee8]">Đội ngũ Phúc Thịnh Solar phản hồi trong giờ làm việc.</p>

          <div v-if="contact.hotline" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Hotline</small>
            <a v-if="phoneHref" class="font-bold text-white no-underline" :href="phoneHref">{{ contact.hotline }}</a>
            <strong v-else>{{ contact.hotline }}</strong>
          </div>
          <div v-if="contact.email" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Email</small>
            <a v-if="emailHref" class="font-bold text-white no-underline" :href="emailHref">{{ contact.email }}</a>
            <strong v-else>{{ contact.email }}</strong>
          </div>
          <p v-if="contact.address" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Địa chỉ</small><strong>{{ contact.address }}</strong>
          </p>
          <p v-if="contact.workingHours" class="my-[18px] flex flex-col gap-0.5 text-white">
            <small class="text-xs text-[#9fb0c1]">Giờ làm việc</small><strong>{{ contact.workingHours }}</strong>
          </p>
        </div>

        <form class="p-[clamp(30px,3.2vw,48px)]" @submit.prevent="send">
          <div class="grid gap-x-4 tb:grid-cols-2">
            <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
              Họ và tên <span>*</span>
              <input v-model.trim="form.fullname" class="mt-2 block w-full rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/25 transition-all" type="text" maxlength="100" autocomplete="name" required>
            </label>
            <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
              Số điện thoại <span>*</span>
              <input v-model.trim="form.phone" class="mt-2 block w-full rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/25 transition-all" type="tel" maxlength="20" autocomplete="tel" inputmode="tel" required>
            </label>
            <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
              Tỉnh/Thành phố
              <input v-model.trim="form.address" class="mt-2 block w-full rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/25 transition-all" type="text" maxlength="255" autocomplete="address-level1" placeholder="Ví dụ: Gia Lai">
            </label>
            <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
              Anh/chị quan tâm đến
              <select v-model="form.subject" class="mt-2 block min-h-[46px] w-full rounded-[3px] border-[1.5px] border-[#cfd6de] bg-white px-[13px] py-[11px] font-inherit outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/25 transition-all">
                <option value="">-- Chọn nhu cầu --</option>
                <option value="Đèn năng lượng mặt trời">Đèn năng lượng mặt trời</option>
                <option value="Điện mặt trời gia đình">Điện mặt trời gia đình</option>
                <option value="Điện mặt trời doanh nghiệp">Điện mặt trời doanh nghiệp</option>
                <option value="Tấm pin và biến tần">Tấm pin và biến tần</option>
                <option value="Pin lưu trữ">Pin lưu trữ</option>
                <option value="Hợp tác đại lý">Hợp tác đại lý</option>
                <option value="Khác">Khác</option>
              </select>
            </label>
          </div>
          <label class="mb-[17px] block text-[14.5px] font-semibold text-navy-950">
            Nội dung cần trao đổi <span>*</span>
            <textarea v-model.trim="form.content" class="mt-2 block w-full resize-y rounded-[3px] border-[1.5px] border-[#cfd6de] px-[13px] py-[11px] font-inherit outline-none focus:border-gold-500 focus:ring-2 focus:ring-gold-500/25 transition-all" rows="4" maxlength="5000" required placeholder="Mô tả ngắn nhu cầu của bạn" />
          </label>
          <p v-if="success" class="mb-3 rounded-[3px] bg-emerald-50 p-3 text-sm font-semibold text-emerald-800" role="status">{{ success }}</p>
          <p v-if="error" class="mb-3 rounded-[3px] bg-red-50 p-3 text-sm font-semibold text-red-800" role="alert">{{ error }}</p>
          <button :disabled="submitting" class="inline-flex min-h-[50px] items-center justify-center rounded-[4px] border border-hdr-gold bg-hdr-gold px-[25px] font-bold text-navy-950 shadow-xs transition-all hover:bg-hdr-gold-hover hover:shadow-md active:scale-[0.98] disabled:cursor-wait disabled:opacity-60 cursor-pointer" type="submit">
            {{ submitting ? 'Đang gửi…' : 'Gửi yêu cầu tư vấn' }}
          </button>
        </form>
      </div>
    </div>
  </section>
</template>
