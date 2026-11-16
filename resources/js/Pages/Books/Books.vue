<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { Book } from '@/types/index';
  import { Link } from '@inertiajs/vue3';
  import { ref } from 'vue';
  import BookModal from './BookModal.vue';

  defineProps<{ books: Book[] }>();
  defineOptions({ layout: AuthLayout });

  const isAdding = ref(false);
</script>

<template>
  <Head title="Books" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto w-full max-w-6xl px-8 py-8">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-textPrimary">Your Books</h1>
          <p class="mt-1 text-sm text-secondary">Whole works, split into chapters you can annotate</p>
        </div>
        <Button type="primary" icon="plus" text="Add Book" @click="isAdding = true" />
      </div>

      <div v-if="books.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="book in books"
          :key="book.id"
          class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md"
        >
          <Link :href="`/books/${book.id}`" class="card-background relative flex h-32 items-center justify-center px-6">
            <div
              class="absolute inset-0 opacity-15"
              :style="{
                backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                backgroundSize: '24px 24px',
              }"
            />
            <span
              class="relative line-clamp-2 text-center font-playfair text-xl font-semibold leading-snug text-textLight"
              :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
            >
              {{ book.title }}
            </span>
          </Link>
          <div class="flex flex-1 flex-col p-4">
            <div class="flex items-center justify-between gap-2 text-xs text-textSecondary">
              <span class="truncate font-semibold text-textPrimary">{{ book.author?.name ?? 'Unknown author' }}</span>
              <span class="shrink-0">{{ book.chapters_count }} chapters</span>
            </div>
            <Link
              :href="book.last_read ? `/texts/${book.last_read.id}` : `/books/${book.id}`"
              class="mt-4 flex items-center justify-between gap-2 border-t border-gray-100 pt-3 text-sm font-medium text-primary hover:underline"
            >
              <span class="truncate">{{ book.last_read ? `Continue: ${book.last_read.title}` : 'Start reading' }}</span>
              <Icon icon="chevronRight" class="h-4 w-4 shrink-0" />
            </Link>
          </div>
        </div>
      </div>

      <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-12 text-center">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-200">
          <Icon icon="books" class="h-7 w-7 text-primary" />
        </div>
        <p class="mb-1 text-lg font-medium text-textPrimary">No books yet</p>
        <p class="mb-6 text-sm text-textSecondary">Import an EPUB or plain-text file to read and annotate it here</p>
        <Button class="mx-auto" icon="plus" text="Add Book" @click="isAdding = true" />
      </div>
    </div>
  </div>
  <BookModal v-if="isAdding" @close="isAdding = false" />
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
