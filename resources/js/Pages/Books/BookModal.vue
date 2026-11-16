<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import { Book } from '@/types';
  import { useForm } from '@inertiajs/vue3';

  // Without `book` the modal uploads a new one; with it, it edits the title and author
  const props = defineProps<{ book?: Book }>();
  const emit = defineEmits<{ (e: 'close'): void }>();

  const form = useForm({
    file: null as File | null,
    title: props.book?.title ?? '',
    author_name: props.book?.author?.name ?? '',
  });

  function save() {
    // Button's `disabled` only styles it, so guard here too
    if (form.processing || (props.book ? !form.title.trim() : !form.file)) return;
    const options = { preserveScroll: true, onSuccess: () => emit('close') };
    if (props.book) form.patch(`/books/${props.book.id}`, options);
    else form.transform(({ file }) => ({ file })).post('/books', options);
  }
</script>

<template>
  <Modal show @close="emit('close')" max-width="lg">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
      <h1 class="text-lg font-medium text-gray-900">{{ book ? 'Edit Book' : 'Add a Book' }}</h1>
      <Button type="ghost" icon="x" icon-class="h-5 w-5" @click="emit('close')" />
    </div>

    <div v-if="book" class="space-y-4 p-6">
      <InputText v-model="form.title" label="Title" placeholder="Title" />
      <InputText v-model="form.author_name" label="Author" placeholder="Leave empty if unknown" />
    </div>

    <div v-else class="space-y-4 p-6">
      <label
        class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed border-gray-200 px-6 py-10 text-center transition-colors hover:border-primary/40"
      >
        <Icon icon="books" class="h-8 w-8 text-primary" />
        <span class="font-medium text-textPrimary">{{ form.file?.name ?? 'Choose an EPUB or TXT file' }}</span>
        <span class="text-xs text-textSecondary">Up to 50 MB. Chapters are detected automatically.</span>
        <input
          type="file"
          accept=".epub,.txt"
          class="sr-only"
          @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
        />
      </label>
      <p v-if="form.errors.file" class="text-sm text-error">{{ form.errors.file }}</p>
      <p class="flex gap-2 text-xs text-secondary">
        <Icon icon="info" class="h-4 w-4 shrink-0" />
        Only DRM-free files work. Public-domain classics are free on Project Gutenberg.
      </p>
    </div>

    <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
      <Button text="Cancel" type="ghost" @click="emit('close')" />
      <Button
        :text="form.processing ? (book ? 'Saving...' : 'Importing...') : book ? 'Save changes' : 'Import book'"
        :disabled="form.processing || (book ? !form.title.trim() : !form.file)"
        @click="save"
      />
    </div>
  </Modal>
</template>
