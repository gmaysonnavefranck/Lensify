<template>
  <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center gap-4">
      <InputText v-model="internalSearch" class="flex-grow self-center" :placeholder="searchPlaceholder" />
      <Button class="self-end" type="outline" @click="isFiltersOpen = !isFiltersOpen">
        <div class="flex items-center gap-2 px-1 py-0.5">
          <Icon icon="filter" class="h-5 w-5 text-gray-600" />
          Filters
          <Icon
            icon="chevronUp"
            class="h-4 w-4 text-gray-600 transition-transform duration-300 ease-in-out"
            :class="{ 'rotate-180': isFiltersOpen }"
          />
        </div>
      </Button>
    </div>
    <transition name="filter-slide" appear>
      <div v-if="isFiltersOpen" class="mt-4 border-t border-gray-200 pt-4">
        <slot />
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from './InputText.vue';
  import { ref, watch } from 'vue';

  interface FilterProps {
    searchPlaceholder?: string;
    modelValue?: string;
  }

  const props = withDefaults(defineProps<FilterProps>(), {
    searchPlaceholder: 'Search...',
    modelValue: '',
  });

  const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

  const internalSearch = ref(props.modelValue);

  watch(
    () => props.modelValue,
    (newValue) => {
      internalSearch.value = newValue;
    },
  );

  // Emit changes when `internalSearch` is updated
  watch(internalSearch, (newValue) => {
    emit('update:modelValue', newValue);
  });

  const isFiltersOpen = ref(false);
</script>

<style scoped>
  .filter-slide-enter-active,
  .filter-slide-leave-active {
    transition: all 0.3s ease-in-out;
  }

  .filter-slide-enter-from {
    opacity: 0;
    transform: translateY(-10px);
  }

  .filter-slide-enter-to {
    opacity: 1;
    transform: translateY(0);
  }

  .filter-slide-leave-from {
    opacity: 1;
    transform: translateY(0);
  }

  .filter-slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
  }
</style>
