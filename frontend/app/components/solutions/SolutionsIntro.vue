<script setup lang="ts">
import type { PublicSolutionsIntro } from '../../../shared/contracts/public-solutions'

defineProps<{ intro: PublicSolutionsIntro }>()
const links = useMigrationLinks()
</script>

<template>
  <!--
    PHÁT HIỆN: ảnh production (/giai-phap/, 1920px) không có ảnh minh hoạ nào
    trong khối intro — chỉ hai cột CHỮ: trái là tiêu đề, phải là mô tả + nút.
    `intro.image` rỗng ở dữ liệu mẫu nên không quan sát được nó nằm ở đâu khi
    có giá trị. Đặt ảnh (nếu có) thành dải full-width phía trên hai cột thay vì
    ép nó làm cột thứ hai như bản cũ — ép vậy sẽ đẩy mô tả xuống dưới ảnh, sai
    với bố cục hai cột chữ đã quan sát được.
  -->
  <section class="pb-[42px]" aria-labelledby="solutions-title">
    <figure v-if="intro.image" class="m-0 mb-8 aspect-[21/9] overflow-hidden rounded-btn">
      <img :src="intro.image" :alt="intro.title" class="h-full w-full object-cover" width="1200" height="514" fetchpriority="high">
    </figure>

    <div class="grid grid-cols-1 items-start gap-8 tb:grid-cols-2 tb:gap-12">
      <div>
        <!--
          PHP (sources/solution_hub.php) tính $solutionHeroEyebrow nhưng
          templates/news/service_list_tpl.php không bao giờ in nó ra - đây là
          field chết ở PHP. Không render để khớp bố cục thật, dù API vẫn trả
          giá trị (giữ nguyên contract, chỉ ẩn ở UI).
        -->
        <h1 id="solutions-title" class="m-0 text-[clamp(28px,2.9vw,42px)] font-black leading-[1.22] tracking-tight text-navy-800">{{ intro.title }}</h1>
      </div>

      <div>
        <p class="m-0 text-[17px] leading-relaxed text-slate-500">{{ intro.description }}</p>
        <div class="mt-[27px] flex flex-col items-stretch gap-[15px] ph:flex-row ph:items-center ph:gap-6">
          <a class="inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-hdr-gold px-[22px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(intro.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : intro.links.consult.href)">
            {{ intro.links.consult.label }} <span aria-hidden="true">→</span>
          </a>
          <a class="text-center font-extrabold text-navy-800 no-underline ph:text-left" :href="links.generic(intro.links.explore.href)">{{ intro.links.explore.label }}</a>
        </div>
      </div>
    </div>
  </section>
</template>
