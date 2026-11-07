<script setup lang="ts">
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'

defineOptions({ name: 'CatalogCategoryBranch' })
defineProps<{ node: PublicProductCategoryNode; selected: string }>()
const links = usePublicLinks()

function categoryLink(url: string) {
  return links.generic(url)
}
</script>

<template>
  <!--
    Cấp con thụt vào bằng đường kẻ dọc bên trái thay vì bullet: cây danh mục
    sản phẩm sâu tới 4 cấp (danh-muc-san-pham/list/cat/item/sub), bullet lồng
    nhau ở độ sâu đó đọc rất rối.
    `[&+li]:mt-2` thay cho `li + li` của CSS cũ — mỗi mục tự biết khoảng cách
    với mục liền trước, không cần rule ở component cha.
  -->
  <li class="[&+li]:mt-2">
    <NuxtLink
      class="flex min-h-11 min-w-0 items-center truncate py-2 text-[15px] leading-5 text-navy-800 no-underline transition-colors nav:text-base
             hover:text-gold-700 hover:underline hover:underline-offset-[3px]
             focus-visible:text-gold-700 focus-visible:underline focus-visible:underline-offset-[3px]"
      :class="selected === node.url ? 'text-gold-700 underline underline-offset-[3px]' : ''"
      :aria-current="selected === node.url ? 'page' : undefined"
      external
      :to="categoryLink(node.url)"
    >
      {{ node.name }}
    </NuxtLink>
    <!-- border-gray-200 = #e5e7eb, đúng bằng token --pts-line của bản PHP. -->
    <ul v-if="node.children.length" class="mt-2 mb-3 ml-3 list-none border-l border-gray-200 pl-3">
      <CatalogCategoryBranch
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :selected="selected"
      />
    </ul>
  </li>
</template>
