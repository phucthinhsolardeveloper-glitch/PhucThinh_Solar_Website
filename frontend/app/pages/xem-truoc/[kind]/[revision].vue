<script setup lang="ts">
import { DRAFT_PREVIEW_KINDS, draftPreviewQuery } from '#shared/draft-preview'
import PolicyView from '~/components/views/PolicyView.vue'
import ProjectDetailView from '~/components/views/ProjectDetailView.vue'
import SolutionDetailView from '~/components/views/SolutionDetailView.vue'

// Draft of a solution, project or policy shown exactly as the public page, from a signed Admin link.
const route = useRoute()
const kindKey = String(route.params.kind || '') as keyof typeof DRAFT_PREVIEW_KINDS
const kind = Object.hasOwn(DRAFT_PREVIEW_KINDS, kindKey) ? DRAFT_PREVIEW_KINDS[kindKey] : null
if (!kind) throw createError({ statusCode: 404, statusMessage: 'Not Found' })
const preview = draftPreviewQuery(route.params.revision, route.query)
const view = { solution: SolutionDetailView, project: ProjectDetailView, policy: PolicyView }[kind]
const label = { solution: 'giải pháp', project: 'dự án', policy: 'chính sách' }[kind]

useSeoMeta({ robots: 'noindex, nofollow, noarchive' })
if (import.meta.server) {
  useResponseHeader('Cache-Control').value = 'private, no-store'
  useResponseHeader('X-Robots-Tag').value = 'noindex, nofollow, noarchive'
  useResponseHeader('Referrer-Policy').value = 'no-referrer'
  if (!preview) setResponseStatus(403, 'Preview unavailable')
}
</script>

<template>
  <div>
    <div class="pts-container pt-4">
      <div class="rounded-md border border-gold-500/30 bg-amber-50 px-5 py-3 text-sm font-semibold text-navy-800" role="status">
        Bản xem trước {{ label }} · Chưa xuất bản, chỉ người có liên kết tạm thời mới xem được
      </div>
    </div>
    <div v-if="!preview" class="pts-container py-20 text-center">
      <h1 class="text-2xl font-bold">Liên kết xem trước không còn hiệu lực</h1>
      <p class="mt-3 text-slate-600">Mở lại bản nháp trong Admin để lấy liên kết mới.</p>
    </div>
    <component :is="view" v-else :preview="preview" />
  </div>
</template>
