<template>
  <svg v-if="svgContent" v-html="svgContent" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img"></svg>
</template>

<script setup lang="ts">
  import { computed } from 'vue';

  const props = defineProps<{
    icon: string;
  }>();

  // Bundle every icon up front (they're tiny) instead of fetching each one after render
  const icons = import.meta.glob<string>('../Icons/*.svg', { query: '?raw', import: 'default', eager: true });

  const svgContent = computed(() => {
    const svg = icons[`../Icons/${props.icon}.svg`];
    if (!svg) console.error(`Icon "${props.icon}" not found.`);
    return svg ?? null;
  });
</script>
