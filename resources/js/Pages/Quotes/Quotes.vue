<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Pagination from '@/Components/Pagination.vue';
  import { PaginatedResponse, Quote } from '@/types/index';
  import { Link, router } from '@inertiajs/vue3';
  import { ref, watch } from 'vue';

  const props = defineProps<{
    quotes: PaginatedResponse<Quote>;
    filters: { search?: string };
  }>();

  defineOptions({ layout: AuthLayout });

  const search = ref(props.filters.search || '');
  watch(search, (value) => router.get('/quotes', { search: value }, { preserveState: true, replace: true }));

  const copiedId = ref<number | null>(null);

  async function copyQuote(quote: Quote) {
    const author = quote.text?.author?.name;
    await navigator.clipboard.writeText(`“${quote.content}”${author ? ` — ${author}` : ''}`);
    copiedId.value = quote.id;
    setTimeout(() => (copiedId.value = null), 1500);
  }

  function deleteQuote(quote: Quote) {
    if (!confirm('Delete this quote?')) return;
    router.delete(`/quotes/${quote.id}`, { preserveScroll: true, preserveState: true });
  }
</script>

<template>
  <Head title="Quotes" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto w-full max-w-4xl px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-semibold text-textPrimary">Your Quotes</h1>
        <p class="mt-1 text-sm text-secondary">Lines worth keeping, saved while reading</p>
      </div>

      <InputText v-model="search" class="mb-6" placeholder="Search quotes..." />

      <div v-if="quotes.data.length" class="space-y-4">
        <figure
          v-for="quote in quotes.data"
          :key="quote.id"
          class="group relative rounded-lg border border-gray-200 bg-white px-6 py-5"
        >
          <Icon icon="quotes" class="absolute left-5 top-5 h-5 w-5 text-primary/30" />
          <blockquote class="whitespace-pre-line pl-8 font-newsreader text-lg leading-relaxed text-textPrimary">
            {{ quote.content }}
          </blockquote>
          <figcaption class="mt-3 flex items-center justify-between gap-4 pl-8 text-sm">
            <span class="min-w-0 truncate text-textSecondary">
              <span v-if="quote.text?.author" class="font-medium text-textPrimary">{{ quote.text.author.name }}</span>
              <template v-if="quote.text">
                <span v-if="quote.text.author"> · </span>
                <Link :href="`/texts/${quote.text.id}`" class="italic hover:text-primary hover:underline">
                  {{ quote.text.title }}
                </Link>
              </template>
            </span>
            <span
              class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100"
            >
              <Button
                type="ghost"
                :icon="copiedId === quote.id ? 'check' : 'copy'"
                class="!p-1.5"
                title="Copy quote"
                @click="copyQuote(quote)"
              />
              <Button
                type="ghost"
                icon="trash"
                class="!p-1.5 hover:!text-error"
                title="Delete"
                @click="deleteQuote(quote)"
              />
            </span>
          </figcaption>
        </figure>
      </div>

      <div v-else class="rounded-lg border-2 border-dashed border-gray-200 p-10 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-slate-200">
          <Icon icon="quotes" class="h-6 w-6 text-primary" />
        </div>
        <p class="font-medium text-textPrimary">{{ search ? 'No quotes match your search' : 'No quotes yet' }}</p>
        <p v-if="!search" class="mt-1 text-sm text-textSecondary">
          While reading a text, select a passage you love and choose “Quote”.
        </p>
      </div>

      <Pagination
        v-if="quotes.last_page > 1"
        :current_page="quotes.current_page"
        :per_page="quotes.per_page"
        :total="quotes.total"
        :last_page="quotes.last_page"
        @update:current_page="(page) => router.get('/quotes', { page, search }, { preserveState: true })"
        entity="quotes"
      />
    </div>
  </div>
</template>
