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
    <Icon :icon="icon" :alt="text" class="h-6 w-6 text-[#8491A5]" :class="{ '!text-white': selected }" />
    <span class="font-semibold leading-5">{{ text }}</span>
  </Link>
</template>

<style lang="postcss" scoped>
  .navLink {
    @apply mb-2 flex items-center gap-4 rounded-lg px-4 py-[10px] text-[#526581];

    &:hover:not(.selected) {
      @apply bg-slate-100;
    }
  }
  .selected {
    @apply bg-[#007AFF] text-white;
  }
</style>
