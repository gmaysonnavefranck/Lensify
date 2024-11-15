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
    <Icon :icon="icon" :alt="text" class="h-4 w-4" />
    <span class="font-semibold leading-4">{{ text }}</span>
  </Link>
</template>

<style lang="postcss" scoped>
  .navLink {
    @apply flex items-center gap-2 border-b-2 border-b-transparent px-3 py-2 text-primary;

    &:hover:not(.selected) {
      @apply border-gray-200 text-primaryHover;
    }

    &:active {
      @apply !text-primaryActive;
    }
  }
  .selected {
    @apply border-primary;
  }
</style>
