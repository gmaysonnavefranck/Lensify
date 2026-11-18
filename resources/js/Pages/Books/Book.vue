<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import { GLOSSARY_TYPES } from '@/glossary';
  import axios from 'axios';
  import { Book, Chapter, GlossaryEntry, GlossaryType } from '@/types/index';
  import { Link, router } from '@inertiajs/vue3';
  import { computed, ref } from 'vue';
  import BookModal from './BookModal.vue';
  import GlossaryEntryModal from './GlossaryEntryModal.vue';

  const props = defineProps<{
    book: Book & { last_read_text_id: number | null };
    chapters: Chapter[];
    glossary: GlossaryEntry[];
  }>();
  defineOptions({ layout: AuthLayout });

  const isEditing = ref(false);
  const tab = ref<'chapters' | 'glossary'>('chapters');
  const typeFilter = ref<GlossaryType | null>(null);
  const glossaryModal = ref<{ entry?: GlossaryEntry } | null>(null);
  const visibleEntries = computed(() =>
    typeFilter.value ? props.glossary.filter((entry) => entry.type === typeFilter.value) : props.glossary,
  );
  const resume = computed(() => props.chapters.find((c) => c.id === props.book.last_read_text_id) ?? null);
  const notesCount = computed(() => props.chapters.reduce((sum, c) => sum + c.text_notes_count, 0));

  // Search across all chapters, 10 hits at a time; a hit opens the chapter on its page with the words marked
  type SearchHit = {
    text_id: number;
    chapter: string;
    position: number;
    offset: number;
    length: number;
    before: string;
    match: string;
    after: string;
  };
  const query = ref('');
  const search = ref<{ q: string; page: number; results: SearchHit[]; hasMore: boolean; loading: boolean; error?: boolean } | null>(null);

  async function runSearch(page = 1) {
    const q = query.value.trim();
    if (q.length < 2 || (search.value?.loading && search.value.q === q)) return;
    search.value = { q, page, results: search.value?.q === q ? search.value.results : [], hasMore: false, loading: true };
    try {
      const { data } = await axios.get(`/books/${props.book.id}/search`, { params: { q, page } });
      if (search.value?.q === q && search.value.page === page) search.value = { q, page, ...data, loading: false };
    } catch {
      if (search.value?.q === q) search.value = { q, page, results: [], hasMore: false, loading: false, error: true };
    }
  }

  function clearSearch() {
    query.value = '';
    search.value = null;
  }

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

      <div class="flex items-center gap-6 border-b border-gray-200">
        <button
          v-for="(label, key) in { chapters: `Chapters (${chapters.length})`, glossary: `Glossary (${glossary.length})` }"
          :key="key"
          class="-mb-px border-b-2 pb-2 text-sm font-medium transition-colors"
          :class="tab === key ? 'border-primary text-primary' : 'border-transparent text-textSecondary hover:text-primary'"
          @click="
            tab = key;
            clearSearch();
          "
        >
          {{ label }}
        </button>
        <form class="mb-2 ml-auto w-64" @submit.prevent="runSearch()">
          <InputText v-model="query" placeholder="Search in this book…" />
        </form>
      </div>

      <section v-if="search" class="flex flex-col gap-3">
        <div class="flex items-center justify-between text-sm text-textSecondary">
          <span v-if="search.loading">Searching…</span>
          <span v-else-if="search.error" class="text-error">Search failed. Please try again.</span>
          <span v-else-if="search.results.length">Matches for “{{ search.q }}”</span>
          <span v-else>No matches for “{{ search.q }}”</span>
          <button class="hover:text-primary" @click="clearSearch">Clear search</button>
        </div>
        <ul
          v-if="search.results.length"
          class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white"
          :class="search.loading && 'opacity-60'"
        >
          <li v-for="hit in search.results" :key="`${hit.text_id}-${hit.offset}`">
            <Link
              :href="`/texts/${hit.text_id}?at=${hit.offset}&len=${hit.length}`"
              class="block px-5 py-3 transition-colors hover:bg-gray-50"
            >
              <p class="text-xs font-medium text-primary">Chapter {{ hit.position }} · {{ hit.chapter }}</p>
              <p class="mt-1 font-newsreader text-textPrimary">
                {{ hit.before }}<mark class="rounded-sm bg-yellow-200 px-0.5 text-inherit">{{ hit.match }}</mark
                >{{ hit.after }}
              </p>
            </Link>
          </li>
        </ul>
        <div v-if="search.page > 1 || search.hasMore" class="flex items-center justify-between text-sm">
          <Button
            type="ghost"
            icon="chevronLeft"
            text="Previous"
            :class="search.page === 1 && 'invisible'"
            @click="runSearch(search.page - 1)"
          />
          <span class="text-textSecondary">Page {{ search.page }}</span>
          <Button type="ghost" text="Next" :class="!search.hasMore && 'invisible'" @click="runSearch(search.page + 1)" />
        </div>
      </section>

      <section v-else-if="tab === 'glossary'" class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-4">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="(meta, type) in { all: { label: 'All', icon: '' }, ...GLOSSARY_TYPES }"
              :key="type"
              class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors"
              :class="
                (typeFilter ?? 'all') === type
                  ? 'border-primary bg-primary/10 text-primary'
                  : 'border-gray-200 text-textSecondary hover:border-primary/40'
              "
              @click="typeFilter = type === 'all' ? null : (type as GlossaryType)"
            >
              <Icon v-if="meta.icon" :icon="meta.icon" class="h-3.5 w-3.5" />
              {{ meta.label }}
            </button>
          </div>
          <Button icon="plus" text="Add entry" @click="glossaryModal = {}" />
        </div>

        <ul v-if="visibleEntries.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
          <li
            v-for="entry in visibleEntries"
            :key="entry.id"
            class="flex cursor-pointer gap-4 px-5 py-4 transition-colors hover:bg-gray-50"
            @click="glossaryModal = { entry }"
          >
            <Icon :icon="GLOSSARY_TYPES[entry.type].icon" class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
            <div class="min-w-0 flex-1">
              <p class="font-medium text-textPrimary">
                {{ entry.name }}
                <span v-if="entry.aliases?.length" class="text-sm font-normal text-textSecondary">
                  · {{ entry.aliases.join(', ') }}
                </span>
              </p>
              <p v-if="entry.relation" class="text-sm text-primary">{{ entry.relation }}</p>
              <p v-if="entry.description" class="mt-1 text-sm text-textSecondary">{{ entry.description }}</p>
              <p v-if="entry.notes" class="mt-2 whitespace-pre-line border-l-2 border-gray-200 pl-3 text-sm text-textSecondary">
                {{ entry.notes }}
              </p>
            </div>
          </li>
        </ul>
        <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-10 text-center">
          <p class="mb-1 font-medium text-textPrimary">
            {{ glossary.length ? 'Nothing of this type yet' : 'No glossary entries yet' }}
          </p>
          <p class="text-sm text-textSecondary">
            While reading, select a name and choose <span class="font-medium">Glossary</span> to keep track of who's who
          </p>
        </div>
      </section>

      <ol v-else class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
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
  <GlossaryEntryModal
    v-if="glossaryModal"
    :book-id="book.id"
    :entry="glossaryModal.entry"
    @close="glossaryModal = null"
  />
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
