<template>
  <button class="button" :class="[type, disabled && 'disabled', text ? 'px-3 py-2' : 'p-2']">
    <Icon v-if="icon" :icon="icon" class="h-4 w-4" />
    <slot> {{ text }} </slot>
  </button>
</template>

<script setup lang="ts">
  import Icon from '@/Components/Icon.vue';
  interface ButtonProps {
    text?: string;
    type?: 'primary' | 'secondary' | 'ghost';
    icon?: string;
    disabled?: boolean;
  }

  withDefaults(defineProps<ButtonProps>(), {
    type: 'primary',
    disabled: false,
  });
</script>

<style lang="postcss" scoped>
  .disabled {
    @apply cursor-not-allowed opacity-50;
  }
  .button {
    @apply flex cursor-pointer items-center gap-2.5 rounded font-medium leading-[19px] tracking-wider;
  }
  .primary {
    @apply bg-primary text-textLight;

    &:hover {
      @apply bg-primaryHover;
    }

    &:active {
      @apply bg-primaryActive;
    }
  }

  .secondary {
    @apply bg-secondary text-textLight;

    &:hover {
      @apply bg-secondaryHover;
    }

    &:active {
      @apply bg-secondaryActive;
    }
  }

  .ghost {
    @apply bg-transparent font-normal text-gray-600;

    &:hover {
      @apply bg-gray-100;
    }

    &:active {
      @apply bg-gray-200;
    }
  }
</style>
