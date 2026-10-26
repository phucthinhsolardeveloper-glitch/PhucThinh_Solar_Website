<script setup lang="ts">
defineProps<{ name: string; media: string[] }>()
</script>

<template>
  <div>
    <!--
      Ảnh chính vuông, nền xám nhạt. Ảnh production cho thấy tấm pin nằm lọt
      trong khung nền sáng với lề rộng, nên object-contain chứ không phải cover
      — cover sẽ cắt mất mép module, đúng chi tiết khách nhìn để nhận diện hàng.
    -->
    <div v-if="media.length" class="grid aspect-square place-items-center overflow-hidden rounded-chip bg-slate-50">
      <img :src="media[0]" :alt="name" class="h-full w-full object-contain" width="720" height="720">
    </div>
    <p v-else class="text-slate-500">CMS chưa có ảnh sản phẩm hợp lệ.</p>

    <!--
      Dải ảnh nhỏ cuộn ngang. 72px là ngưỡng còn nhận ra được nội dung; nhỏ hơn
      thì các tấm pin trông giống hệt nhau.
      CÒN THIẾU so với ảnh production: bấm ảnh nhỏ chưa đổi ảnh chính và chưa có
      phóng to. Bản PHP dùng MagicZoom; hành vi đó cần đối chiếu riêng nên không
      gắn vội một cơ chế khác.
    -->
    <div v-if="media.length > 1" class="flex gap-2 overflow-x-auto pt-3" aria-label="Thư viện ảnh sản phẩm">
      <img
        v-for="image in media.slice(1)"
        :key="image"
        :src="image"
        :alt="name"
        class="aspect-square flex-none basis-[72px] rounded-chip border border-gray-200 object-contain"
        width="112"
        height="112"
        loading="lazy"
      >
    </div>
  </div>
</template>
