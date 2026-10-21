<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import Filter from '@/Components/Filter.vue';
  import Pagination from '@/Components/Pagination.vue';
  import Select from '@/Components/Select.vue';
  import { Author, PaginatedResponse, Text } from '@/types/index';
  import { Link } from '@inertiajs/vue3';
  import { onMounted, ref, watch } from 'vue';
  import { router } from '@inertiajs/vue3';
  import NewTextModal from './NewTextModal.vue';

  interface TextsPageProps {
    texts: PaginatedResponse<Text>;
    authors: Author[];
    filters: {
      author: string;
      search: string;
    };
  }

  defineOptions({ layout: AuthLayout });
  const props = defineProps<TextsPageProps>();

  const showAddTextModal = ref(false);

  const filter = ref<{
    author: Author | null;
    search: string;
  }>({
    author: null,
    search: props.filters?.search || '',
  });

  onMounted(() => {
    filter.value.author = props.filters.author
      ? props.authors.find((p) => p.id === Number(props.filters.author)) || null
      : null;
  });

  watch(
    () => filter,
    (newFilter) => {
      router.get(
        '/texts',
        {
          author: newFilter.value?.author?.id,
          search: newFilter.value.search,
        },
        { preserveState: true, replace: true },
      );
    },
    { deep: true },
  );
</script>

<template>
  <Head title="Texts" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto w-full max-w-6xl px-8 py-8">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-textPrimary">Your Texts</h1>
          <p class="mt-1 text-sm text-secondary">Texts you've added to your collection</p>
        </div>
        <Button type="primary" icon="plus" text="Add Text" @click="showAddTextModal = true" />
      </div>
      <Filter @update:model-value="(search: string) => (filter.search = search)" v-model="filter.search">
        <Select
          class="max-w-sm"
          label="Author"
          placeholder="Any author"
          v-model="filter.author"
          :options="authors"
          track-by="name"
          display-by="name"
        />
      </Filter>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          v-for="text in texts.data"
          :key="text.id"
          :href="'/texts/' + text.id"
          class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md"
        >
          <div class="card-background relative flex h-24 items-center justify-center overflow-hidden px-6">
            <div
              class="absolute inset-0 opacity-15"
              :style="{
                backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                backgroundSize: '24px 24px',
              }"
            />
            <span
              v-if="text.type"
              class="absolute right-2 top-2 rounded bg-white/20 px-1.5 py-0.5 text-[11px] capitalize text-white"
            >
              {{ text.type }}
            </span>
            <span
              class="relative line-clamp-2 text-center font-playfair text-lg font-semibold leading-snug text-textLight"
              :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
            >
              {{ text.title }}
            </span>
          </div>
          <div class="flex flex-1 flex-col p-4">
            <div class="flex items-center justify-between gap-2 text-xs text-textSecondary">
              <span class="truncate font-semibold text-textPrimary">{{ text.author?.name ?? 'Unknown author' }}</span>
              <span class="shrink-0">{{ text.year }}</span>
            </div>
            <p class="mt-2 line-clamp-2 flex-1 text-sm italic text-textSecondary">“{{ text.content }}…”</p>
            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-textSecondary">
              <span class="flex items-center gap-1">
                <Icon icon="message" class="h-3.5 w-3.5" />
                {{ text.text_notes }} footnote{{ text.text_notes !== 1 ? 's' : '' }}
              </span>
              <span v-if="text.language" class="rounded bg-gray-100 px-1.5 py-0.5">{{ text.language }}</span>
            </div>
          </div>
        </Link>
      </div>

      <Pagination
        :current_page="texts.current_page"
        :per_page="texts.per_page"
        :total="texts.total"
        :last_page="texts.last_page"
        @update:current_page="
          (newPage) =>
            router.get(
              '/texts',
              { page: newPage, author: filter.author?.id, search: filter.search },
              { preserveState: true },
            )
        "
        entity="texts"
      />
    </div>
  </div>
  <NewTextModal
    v-if="showAddTextModal"
    :authors="authors"
    :showAddTextModal="showAddTextModal"
    @close="showAddTextModal = false"
  />
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
