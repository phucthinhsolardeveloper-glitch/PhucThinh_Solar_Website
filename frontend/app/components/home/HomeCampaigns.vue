<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ campaigns: PublicHomePayload['campaigns']; campaignsConfigured: boolean }>()
const phpFallback: PublicHomePayload['campaigns'] = [
  { name: 'Chiếu sáng năng lượng mặt trời cho công trình', description: 'Thiết bị đồng bộ cho đường nội bộ, khuôn viên và hạ tầng thương mại.', image: '/assets/images/site-visuals/home-street-lighting-v2.png', href: '/san-pham/' },
  { name: 'Hệ điện mặt trời và lưu trữ', description: 'Tấm pin, inverter và lưu trữ được lựa chọn theo đúng quy mô sử dụng.', image: '/assets/images/site-visuals/home-solar-storage.jpg', href: '/giai-phap/' },
  { name: 'Chiếu sáng sân vườn và lối đi', description: 'Tạo không gian an toàn, chỉn chu và vận hành độc lập bằng năng lượng sạch.', image: '/assets/images/site-visuals/home-outdoor-lighting.jpg', href: '/san-pham/' },
]
const items = computed(() => props.campaigns.length ? props.campaigns.slice(0, 3) : props.campaignsConfigured ? [] : phpFallback)
const links = useMigrationLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')
</script>

<template>
  <section v-if="items.length" class="bg-hdr-surface py-[clamp(64px,6vw,92px)]" aria-labelledby="home-campaign-title">
    <div class="mx-auto w-[min(calc(100%-clamp(40px,5vw,96px)),1560px)] max-[860px]:w-[min(calc(100%-48px),1440px)] max-[640px]:w-[calc(100%-40px)] max-[360px]:w-[calc(100%-32px)] min-[1600px]:w-[min(calc(100%-clamp(96px,8vw,220px)),2200px)]">
      <header class="mb-10 flex max-w-none items-end justify-between gap-7 max-[860px]:flex-col max-[860px]:items-start">
        <div>
          <h2 id="home-campaign-title" class="m-0 text-[clamp(31px,2.2vw,42px)] font-bold leading-[1.2] tracking-[-.025em] text-navy-950">Chiếu sáng phù hợp từng không gian</h2>
          <p class="mt-[13px] max-w-[760px] text-[clamp(16px,1.1vw,19px)] leading-[1.65] text-slate-500">Lựa chọn thiết bị theo khu vực sử dụng và điều kiện lắp đặt thực tế.</p>
        </div>
        <a class="mt-5 inline-flex min-h-[50px] items-center justify-center rounded-[4px] border border-hdr-gold bg-hdr-gold px-[25px] font-bold text-navy-950 no-underline" href="#yeu-cau-tu-van" @click="leadIntent = 'Đèn năng lượng mặt trời'">Tư vấn chọn đèn</a>
      </header>
      <div class="grid grid-cols-3 gap-[clamp(22px,2.5vw,36px)] max-[860px]:grid-cols-1">
        <article v-for="campaign in items" :key="campaign.href + campaign.name" class="min-w-0 max-[860px]:grid max-[860px]:grid-cols-[minmax(260px,.8fr)_minmax(0,1.2fr)] max-[860px]:items-center max-[860px]:gap-6 max-[640px]:block">
          <a class="block overflow-hidden rounded-[4px]" :href="links.generic(campaign.href)">
            <img class="aspect-[8/5] h-full w-full object-cover" :src="campaign.image" :alt="campaign.name" width="760" height="500" loading="lazy">
          </a>
          <div class="max-[860px]:py-2 max-[640px]:py-0">
            <h3 class="mt-[18px] text-[clamp(19px,1.35vw,23px)] font-bold leading-[1.3] text-navy-950 max-[860px]:mt-0">
              <a class="no-underline" :href="links.generic(campaign.href)">{{ campaign.name }}</a>
            </h3>
            <p v-if="campaign.description" class="mt-2 leading-[1.65] text-slate-500">{{ campaign.description }}</p>
            <a class="mt-3 inline-flex font-bold text-navy-950 no-underline" :href="links.generic(campaign.href)">Xem chi tiết →</a>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
