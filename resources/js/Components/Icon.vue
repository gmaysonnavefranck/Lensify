<template>
  <div v-if="iconSrc">
    <img :src="iconSrc" :alt="alt" :width="`${width || 24}px`" :height="`${height || 24}px`" />
  </div>
</template>

<script setup lang="ts">
  import { ref, onMounted } from 'vue';

  // Define props for the component
  interface IconProps {
    icon: string;
    alt: string;
    width?: number;
    height?: number;
  }

  const props = defineProps<IconProps>();

  const iconSrc = ref<string | null>(null);

  onMounted(() => {
    try {
      iconSrc.value = new URL(`../Icons/${props.icon}.svg`, import.meta.url);
    } catch (error) {
      console.error(`Icon '${props.icon}' not found`);
      iconSrc.value = null;
    }
  });
</script>
