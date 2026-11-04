<script setup lang="ts">
defineProps<{ title: string; description?: string; searchLabel: string; inputId: string; category?: string }>()
const search = defineModel<string>({ default: '' })
const emit = defineEmits<{ submit: [] }>()
</script>

<template>
  <header class="news-masthead">
    <h1>{{ title }}</h1>
    <div class="news-masthead__side">
      <p v-if="description">{{ description }}</p>
      <form class="news-search flex h-14 w-full flex-none items-center" role="search" @submit.prevent="emit('submit')">
        <label class="sr-only" :for="inputId">{{ searchLabel }}</label>
        <UIcon name="i-lucide-search" class="ml-5 size-5 flex-none text-gold-600" aria-hidden="true" />
        <input :id="inputId" v-model="search" name="q" type="search" class="w-full min-w-0 border-0 bg-transparent px-3 text-base text-navy-800 outline-none" :placeholder="searchLabel" maxlength="120">
        <button type="submit" class="news-search__go h-11 w-11 flex-none border-0" :aria-label="searchLabel"><UIcon name="i-lucide-arrow-right" class="size-5" aria-hidden="true" /></button>
        <input v-if="category" type="hidden" name="category" :value="category">
      </form>
    </div>
  </header>
</template>
<style scoped>
.news-masthead { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 520px); align-items: end; gap: clamp(20px, 4vw, 72px); padding: clamp(32px, 5vw, 72px) 0 clamp(24px, 3vw, 40px); border-bottom: 2px solid var(--color-navy-950); }
.news-masthead h1 { margin: 0; color: var(--color-navy-950); font-size: clamp(48px, 7.2vw, 116px); font-weight: 850; line-height: 0.95; letter-spacing: -0.05em; }
.news-masthead__side p { margin: 0 0 18px; color: #52627a; font-size: clamp(16px, 1.2vw, 18px); line-height: 1.65; }
.news-search { border: 1px solid rgb(10 34 81 / 16%); border-radius: 999px; background: #fff; transition: border-color 0.25s ease, box-shadow 0.25s ease; }
.news-search:focus-within { border-color: var(--color-gold-500); box-shadow: 0 0 0 4px rgb(228 168 73 / 18%); }
.news-search__go { display: grid; margin-right: 6px; place-items: center; border-radius: 50%; background: var(--color-navy-950); color: var(--color-gold-300); cursor: pointer; transition: background-color 0.25s ease; }
.news-search__go:hover { background: var(--color-navy-800); }
.news-search__go:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 2px; }
@media (max-width: 900px) {
  .news-masthead { grid-template-columns: 1fr; }
}
</style>
