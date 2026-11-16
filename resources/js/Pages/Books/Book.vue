<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { Book, Chapter } from '@/types/index';
  import { Link, router } from '@inertiajs/vue3';
  import { computed, ref } from 'vue';
  import BookModal from './BookModal.vue';

  const props = defineProps<{ book: Book & { last_read_text_id: number | null }; chapters: Chapter[] }>();
  defineOptions({ layout: AuthLayout });

  const isEditing = ref(false);
  const resume = computed(() => props.chapters.find((c) => c.id === props.book.last_read_text_id) ?? null);
  const notesCount = computed(() => props.chapters.reduce((sum, c) => sum + c.text_notes_count, 0));

  function deleteBook() {
    const warning = notesCount.value ? ` Its ${notesCount.value} footnotes will be deleted too.` : '';
    if (confirm(`Delete “${props.book.title}” and all ${props.chapters.length} chapters?${warning}`)) {
      router.delete(`/books/${props.book.id}`);
    }
  }
</script>

<template>
  <Head :title="book.title" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-8 py-8">
      <div class="flex items-center justify-between">
        <Link href="/books" class="flex items-center gap-2 text-textSecondary transition-colors hover:text-primary">
          <Icon icon="chevronLeft" class="h-5 w-5" />
          <span class="text-sm font-medium">Back to Books</span>
        </Link>
        <div class="flex gap-2">
          <Button type="outline" icon="squarePen" text="Edit" @click="isEditing = true" />
          <Button type="danger" icon="trash" text="Delete" @click="deleteBook" />
        </div>
      </div>

      <header class="card-background relative overflow-hidden rounded-2xl px-8 py-10 text-center shadow-md">
        <div
          class="absolute inset-0 opacity-15"
          :style="{
            backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
            backgroundSize: '24px 24px',
          }"
        />
        <div class="relative flex flex-col items-center gap-3">
          <h1
            class="font-playfair text-4xl font-semibold leading-tight text-textLight"
            :style="{ textShadow: '0 2px 8px rgba(0,0,0,0.25)' }"
          >
            {{ book.title }}
          </h1>
          <p class="text-textLight/80">{{ book.author?.name ?? 'Unknown author' }}</p>
          <p class="text-sm text-textLight/70">
            {{ chapters.length }} chapters · {{ notesCount }} footnote{{ notesCount === 1 ? '' : 's' }}
          </p>
          <Link
            v-if="chapters.length"
            :href="`/texts/${(resume ?? chapters[0]).id}`"
            class="mt-2 flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-primary shadow-sm hover:bg-white/90"
          >
            {{ resume ? `Continue: ${resume.title}` : 'Start reading' }}
            <Icon icon="chevronRight" class="h-4 w-4" />
          </Link>
        </div>
      </header>

      <ol class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
        <li v-for="(chapter, index) in chapters" :key="chapter.id">
          <Link
            :href="`/texts/${chapter.id}`"
            class="flex items-center gap-4 px-5 py-3 transition-colors hover:bg-gray-50"
            :class="chapter.id === book.last_read_text_id && 'bg-primary/5'"
          >
            <span class="w-8 shrink-0 text-right text-sm tabular-nums text-textSecondary">{{ index + 1 }}</span>
            <span class="min-w-0 flex-1 truncate text-textPrimary">{{ chapter.title }}</span>
            <span v-if="chapter.id === book.last_read_text_id" class="shrink-0 text-xs font-medium text-primary">
              Last read
            </span>
            <span v-if="chapter.text_notes_count" class="flex shrink-0 items-center gap-1 text-xs text-textSecondary">
              <Icon icon="message" class="h-3.5 w-3.5" />
              {{ chapter.text_notes_count }}
            </span>
          </Link>
        </li>
      </ol>
    </div>
  </div>
  <BookModal v-if="isEditing" :book="book" @close="isEditing = false" />
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
