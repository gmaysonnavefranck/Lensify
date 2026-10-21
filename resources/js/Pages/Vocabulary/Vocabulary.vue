<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Pagination from '@/Components/Pagination.vue';
  import { PaginatedResponse, VocabularyWord } from '@/types/index';
  import { Link, router } from '@inertiajs/vue3';
  import { ref, watch } from 'vue';

  const props = defineProps<{
    words: PaginatedResponse<VocabularyWord>;
    filters: { search?: string };
  }>();

  defineOptions({ layout: AuthLayout });

  const search = ref(props.filters.search || '');
  watch(search, (value) => router.get('/vocabulary', { search: value }, { preserveState: true, replace: true }));

  const editingId = ref<number | null>(null);
  const draft = ref('');

  function startEditing(word: VocabularyWord) {
    editingId.value = word.id;
    draft.value = word.definition ?? '';
  }

  function saveEdit() {
    router.patch(
      `/vocabulary/${editingId.value}`,
      { definition: draft.value },
      { preserveScroll: true, preserveState: true, onSuccess: () => (editingId.value = null) },
    );
  }

  function deleteWord(word: VocabularyWord) {
    if (!confirm(`Remove “${word.word}” from your vocabulary?`)) return;
    router.delete(`/vocabulary/${word.id}`, { preserveScroll: true, preserveState: true });
  }
</script>

<template>
  <Head title="Vocabulary" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto w-full max-w-4xl px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-textPrimary">Your Vocabulary</h1>
        <p class="mt-1 text-sm text-secondary">Words you've saved while reading</p>
      </div>

      <InputText v-model="search" class="mb-6" placeholder="Search words or definitions..." />

      <div v-if="words.data.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
        <div v-for="word in words.data" :key="word.id" class="group px-5 py-4">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <p class="font-newsreader text-xl font-semibold text-textPrimary">{{ word.word }}</p>

              <div v-if="editingId === word.id" class="mt-2">
                <textarea
                  v-model="draft"
                  rows="3"
                  class="w-full resize-y rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
                  @keydown.esc="editingId = null"
                />
                <div class="mt-2 flex justify-end gap-2">
                  <Button type="ghost" text="Cancel" @click="editingId = null" />
                  <Button text="Save" @click="saveEdit" />
                </div>
              </div>
              <p v-else-if="word.definition" class="mt-1 text-sm text-textPrimary">{{ word.definition }}</p>
              <p v-else class="mt-1 text-sm italic text-secondary">No definition yet. Click the pencil to add one.</p>

              <p v-if="word.context" class="mt-2 line-clamp-2 text-sm italic text-textSecondary">“{{ word.context }}”</p>
              <p class="mt-2 flex items-center gap-1.5 text-xs text-secondary">
                <template v-if="word.text">
                  <Icon icon="texts" class="h-3.5 w-3.5" />
                  <Link :href="`/texts/${word.text.id}`" class="hover:text-primary hover:underline">{{ word.text.title }}</Link>
                  <span>·</span>
                </template>
                <span>{{ new Date(word.created_at).toLocaleDateString() }}</span>
              </p>
            </div>
            <div
              v-if="editingId !== word.id"
              class="flex shrink-0 gap-0.5 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100"
            >
              <Button type="ghost" icon="squarePen" class="!p-1.5" title="Edit definition" @click="startEditing(word)" />
              <Button type="ghost" icon="trash" class="!p-1.5 hover:!text-error" title="Delete" @click="deleteWord(word)" />
            </div>
          </div>
        </div>
      </div>

      <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-10 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-200">
          <Icon icon="bookA" class="h-6 w-6 text-primary" />
        </div>
        <p class="font-medium text-textPrimary">{{ search ? 'No words match your search' : 'No words yet' }}</p>
        <p v-if="!search" class="mt-1 text-sm text-textSecondary">
          While reading a text, select a word and choose “Add to vocabulary”.
        </p>
      </div>

      <Pagination
        v-if="words.last_page > 1"
        :current_page="words.current_page"
        :per_page="words.per_page"
        :total="words.total"
        :last_page="words.last_page"
        @update:current_page="(page) => router.get('/vocabulary', { page, search }, { preserveState: true })"
        entity="words"
      />
    </div>
  </div>
</template>
