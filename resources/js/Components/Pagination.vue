<template>
  <div class="mt-6 flex items-center justify-between pt-6">
    <span class="text-textSecondary">
      Showing
      <span class="font-medium">
        {{ (current_page - 1) * per_page + 1 }}-{{ total < current_page * per_page ? total : current_page * per_page }}
      </span>
      of
      <span class="font-medium">{{ total }}</span>
      {{ entity }}
    </span>
    <div class="flex items-center justify-center gap-2">
      <Button
        type="ghost"
        icon="chevronsLeft"
        iconClass="h-[24px] w-[24px]"
        :disabled="current_page === 1"
        @click="current_page > 1 && emit('update:current_page', 1)"
      />
      <Button
        type="ghost"
        icon="chevronLeft"
        iconClass="h-[24px] w-[24px]"
        :disabled="current_page === 1"
        @click="current_page > 1 && emit('update:current_page', current_page - 1)"
      />
      <Button
        v-for="page in pages"
        class="h-[35px] w-[35px] justify-center"
        :type="page === current_page ? 'primary' : 'ghost'"
        @click="emit('update:current_page', page)"
      >
        {{ page }}
      </Button>
      <Button
        type="ghost"
        icon="chevronRight"
        iconClass="h-[24px] w-[24px]"
        :disabled="current_page === last_page"
        @click="current_page < last_page && emit('update:current_page', current_page + 1)"
      />
      <Button
        type="ghost"
        icon="chevronsRight"
        iconClass="h-[24px] w-[24px]"
        :disabled="current_page === last_page"
        @click="current_page < last_page && emit('update:current_page', last_page)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue';
  import Button from '@/Components/Button.vue';

  interface Pagination {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    entity: string;
  }

  const props = defineProps<Pagination>();
  const emit = defineEmits<{ (e: 'update:current_page', value: number): void }>();

  const pages = computed(() => {
    const totalPages = 5;
    const pages = [];

    let start = Math.max(props.current_page - Math.floor(totalPages / 2), 1);
    let end = start + totalPages - 1;

    if (end > props.last_page) {
      end = props.last_page;
      start = Math.max(end - totalPages + 1, 1);
    }

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    return pages;
  });
</script>
