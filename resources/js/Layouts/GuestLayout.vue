<script setup lang="ts">
  import PublicHeader from '@/Components/PublicHeader.vue';
  import { onMounted } from 'vue';

  // Pages before the app are always light; logging out doesn't reload the page
  onMounted(() => document.documentElement.classList.remove('dark'));
</script>

<template>
  <div class="flex min-h-screen flex-col bg-surface">
    <PublicHeader />
    <main class="flex flex-1 items-center justify-center px-6 py-12">
      <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-8 py-10 shadow-sm">
        <slot />
      </div>
    </main>
  </div>
</template>
