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
  <section v-if="items.length" class="bg-hdr-surface py-[clamp(56px,5vw,84px)]" aria-labelledby="home-campaign-title">
    <div class="pts-container">
      <header class="mb-8 flex max-w-none items-end justify-between gap-6 max-[860px]:flex-col max-[860px]:items-start">
        <div>
          <h2 id="home-campaign-title" class="m-0 text-[clamp(28px,2.4vw,40px)] font-black leading-[1.2] tracking-[-.025em] text-navy-950">
            Chiếu sáng phù hợp từng không gian
          </h2>
          <p class="mt-2.5 max-w-[760px] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-500">
            Lựa chọn thiết bị theo khu vực sử dụng và điều kiện lắp đặt thực tế.
          </p>
        </div>
        <a
          class="mt-4 inline-flex min-h-[48px] items-center justify-center rounded-[4px] border border-gold-500 bg-gold-400 px-[24px] text-sm font-bold text-navy-950 no-underline shadow-2xs transition-all hover:bg-gold-500 hover:shadow-sm"
          href="#yeu-cau-tu-van"
          @click="leadIntent = 'Đèn năng lượng mặt trời'"
        >
          Tư vấn chọn đèn
        </a>
      </header>

      <div class="grid grid-cols-3 gap-[clamp(20px,2.4vw,32px)] max-[860px]:grid-cols-1">
        <article
          v-for="campaign in items"
          :key="campaign.href + campaign.name"
          class="group flex flex-col overflow-hidden rounded-[6px] border border-slate-200/80 bg-white p-4 shadow-2xs transition-all duration-300 hover:border-gold-500/60 hover:shadow-md hover:-translate-y-1 max-[860px]:grid max-[860px]:grid-cols-[minmax(240px,.8fr)_minmax(0,1.2fr)] max-[860px]:items-center max-[860px]:gap-5 max-[640px]:block max-[640px]:p-3.5"
        >
          <a class="block overflow-hidden rounded-[4px]" :href="links.generic(campaign.href)">
            <img
              class="aspect-[8/5] h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
              :src="campaign.image"
              :alt="campaign.name"
              width="760"
              height="500"
              loading="lazy"
            >
          </a>
          <div class="flex flex-1 flex-col justify-between pt-3 max-[860px]:py-1 max-[640px]:pt-3">
            <div>
              <h3 class="m-0 text-[clamp(17px,1.2vw,20px)] font-bold leading-[1.3] text-navy-950">
                <a class="no-underline transition-colors hover:text-gold-700" :href="links.generic(campaign.href)">{{ campaign.name }}</a>
              </h3>
              <p v-if="campaign.description" class="mt-2 text-sm leading-[1.6] text-slate-500 line-clamp-2">{{ campaign.description }}</p>
            </div>
            <a class="mt-3.5 inline-flex items-center gap-1.5 text-[13.5px] font-bold text-gold-700 no-underline transition-colors hover:text-navy-950" :href="links.generic(campaign.href)">
              Xem chi tiết <span aria-hidden="true">→</span>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>
