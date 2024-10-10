<script setup lang="ts">
  import { Link } from '@inertiajs/vue3';
  import Icon from '@/Components/Icon.vue';

  interface NavLinkProps {
    href: string;
    selected?: boolean;
    text: string;
    icon: string;
  }

  const props = defineProps<NavLinkProps>();
</script>

<template>
  <Link :href="href" class="navLink" :class="{ selected: selected }">
    <Icon :icon="icon" :alt="text" />
    <span class="font-semibold text-gray-900">{{ text }}</span>
  </Link>
</template>

<style lang="postcss" scoped>
  .navLink {
    @apply mb-2 flex items-center gap-4 rounded px-4 py-2;

    &:hover:not(.selected) {
      @apply bg-slate-100;
    }
  }
  .selected {
    @apply bg-slate-200;
  }
</style>
