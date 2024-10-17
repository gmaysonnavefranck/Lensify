<template>
  <svg v-if="svgContent" v-html="svgContent" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img"></svg>
</template>

<script setup lang="ts">
  import { onBeforeMount, ref } from 'vue';

  const props = defineProps<{
    icon: string;
  }>();

  const svgContent = ref<string | null>(null);
  onBeforeMount(async () => {
    await loadSVG();
  });

  async function loadSVG() {
    try {
      // Dynamically import the SVG based on the name prop
      const svgModule = await import(`../Icons/${props.icon}.svg?raw`);
      svgContent.value = svgModule.default;
    } catch (error) {
      console.error(`Icon "${props.icon}" not found.`);
    }
  }
</script>
