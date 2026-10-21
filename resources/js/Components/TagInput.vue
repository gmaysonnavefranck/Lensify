<script setup lang="ts">
  import Icon from '@/Components/Icon.vue';
  import { ref } from 'vue';

  defineProps<{ label?: string; placeholder?: string }>();

  const tags = defineModel<string[]>({ default: () => [] });
  const draft = ref('');

  function add() {
    const value = draft.value.trim().replace(/,$/, '');
    if (value && !tags.value.includes(value)) tags.value = [...tags.value, value];
    draft.value = '';
  }

  function remove(index: number) {
    tags.value = tags.value.filter((_, i) => i !== index);
  }

  // Backspace on an empty input removes the last tag
  function onBackspace() {
    if (!draft.value && tags.value.length) remove(tags.value.length - 1);
  }
</script>

<template>
  <div>
    <label v-if="label" class="label">{{ label }}</label>
    <div
      class="flex flex-wrap gap-1.5 rounded-md border border-gray-200 px-2 py-1.5 focus-within:border-transparent focus-within:ring-2 focus-within:ring-primary/30"
    >
      <span
        v-for="(tag, i) in tags"
        :key="tag"
        class="flex items-center gap-1 rounded-full bg-primary/10 py-0.5 pl-2.5 pr-1 text-sm text-primary"
      >
        {{ tag }}
        <button type="button" class="rounded-full p-0.5 hover:bg-primary/20" @click="remove(i)">
          <Icon icon="x" class="h-3 w-3" />
        </button>
      </span>
      <input
        v-model="draft"
        :placeholder="tags.length ? '' : placeholder"
        class="min-w-[120px] flex-1 border-0 p-1 text-sm focus:outline-none focus:ring-0"
        @keydown.enter.prevent="add"
        @keydown.backspace="onBackspace"
        @keydown="(e: KeyboardEvent) => e.key === ',' && (e.preventDefault(), add())"
        @blur="add"
      />
    </div>
  </div>
</template>
