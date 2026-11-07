<script setup lang="ts">
import type { PublicHomePayload } from '#shared/contracts/public-home'

const props = defineProps<{ campaigns: PublicHomePayload['campaigns']; campaignsConfigured: boolean }>()
const phpFallback: PublicHomePayload['campaigns'] = [
  { name: 'Chiếu sáng năng lượng mặt trời cho công trình', description: 'Thiết bị đồng bộ cho đường nội bộ, khuôn viên và hạ tầng thương mại.', image: '/assets/images/site-visuals/home-street-lighting-v2.png', href: '/san-pham/' },
  { name: 'Hệ điện mặt trời và lưu trữ', description: 'Tấm pin, inverter và lưu trữ được lựa chọn theo đúng quy mô sử dụng.', image: '/assets/images/site-visuals/home-solar-storage.jpg', href: '/giai-phap/' },
  { name: 'Chiếu sáng sân vườn và lối đi', description: 'Tạo không gian an toàn, chỉn chu và vận hành độc lập bằng năng lượng sạch.', image: '/assets/images/site-visuals/home-outdoor-lighting.jpg', href: '/san-pham/' },
]
const items = computed(() => props.campaigns.length ? props.campaigns.slice(0, 3) : props.campaignsConfigured ? [] : phpFallback)
const links = usePublicLinks()
const leadIntent = useState<string>('public-home-lead-intent', () => '')
</script>

<template>
  <section v-if="items.length" class="bg-hdr-surface py-[clamp(64px,6vw,104px)] max-tb:py-8" aria-labelledby="home-campaign-title">
    <div class="pts-container">
      <header class="mb-10 max-tb:mb-4 flex items-end justify-between gap-6 max-tb:gap-3 max-[860px]:flex-col max-[860px]:items-start">
        <div>
          <h2 id="home-campaign-title" class="m-0 max-w-[18ch] text-[clamp(30px,3vw,50px)] font-black leading-[1.06] tracking-[-.035em] text-navy-950">
            Chiếu sáng phù hợp từng không gian
          </h2>
          <p class="mt-4 max-tb:mt-2 max-w-[52ch] text-[clamp(15px,1.05vw,18px)] leading-[1.65] text-slate-600">
            Lựa chọn thiết bị theo khu vực sử dụng và điều kiện lắp đặt thực tế.
          </p>
        </div>
        <a
          class="inline-flex min-h-[52px] max-tb:w-full flex-none items-center justify-center rounded-btn border border-gold-500 bg-gold-400 px-6 text-[15px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-gold-500"
          href="#yeu-cau-tu-van"
          @click="leadIntent = 'Đèn năng lượng mặt trời'"
        >
          Tư vấn chọn đèn
        </a>
      </header>

      <div class="m-rail grid grid-cols-3 gap-[clamp(20px,2.4vw,36px)] max-[860px]:grid-cols-1 max-[860px]:gap-9">
        <a
          v-for="campaign in items"
          :key="campaign.href + campaign.name"
          class="space-card group flex flex-col text-navy-950 no-underline"
          :href="links.generic(campaign.href)"
        >
          <span class="block overflow-hidden rounded-btn max-tb:rounded-md bg-navy-950">
            <img
              class="block aspect-[4/3] w-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.05] motion-reduce:transition-none motion-reduce:group-hover:scale-100"
              :src="optimizedImage(campaign.image, 1200, 900)"
              :alt="campaign.name"
              width="760"
              height="570"
              loading="lazy"
            >
          </span>
          <span class="mt-5 max-tb:mt-3 block">
            <strong class="space-title text-[clamp(19px,1.45vw,24px)] font-extrabold leading-[1.25] tracking-[-.015em]">{{ campaign.name }}</strong>
          </span>
          <span v-if="campaign.description" class="mt-2.5 max-tb:mt-1.5 line-clamp-2 max-tb:line-clamp-none text-[15px] leading-[1.6] text-slate-600">{{ campaign.description }}</span>
          <span class="mt-4 max-tb:mt-1 max-tb:min-h-11 inline-flex items-center gap-1.5 text-[14px] font-extrabold text-gold-700 transition-colors group-hover:text-navy-950">
            Xem chi tiết <span class="transition-transform duration-300 group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
          </span>
        </a>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Gold rule draws under the title on hover/focus — the only motion beyond the image zoom. */
.space-title {
  background: linear-gradient(var(--color-gold-500), var(--color-gold-500)) no-repeat left bottom / 0 2px;
  padding-bottom: 2px;
  transition: background-size 0.45s cubic-bezier(0.22, 1, 0.36, 1);
}
.space-card:hover .space-title,
.space-card:focus-visible .space-title { background-size: 100% 2px; }
.space-card:focus-visible { outline: 2px solid var(--color-gold-600); outline-offset: 6px; border-radius: var(--radius-btn); }
@media (prefers-reduced-motion: reduce) { .space-title { transition: none; } }
</style>
