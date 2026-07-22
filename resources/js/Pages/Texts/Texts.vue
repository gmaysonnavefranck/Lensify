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
  <div class="flex h-full w-full max-w-5xl flex-col place-self-center p-10">
    <div>
      <div class="mb-6 flex w-full items-center justify-between">
        <div>
          <h1 class="text-4xl font-bold text-textPrimary">Your Texts</h1>
          <span class="text-base font-normal text-secondary">Texts you've added to your collection</span>
        </div>
        <Button type="primary" @click="showAddTextModal = true">
          <div class="flex items-center gap-2 p-1">
            <Icon icon="plus" class="h-5 w-5 text-textLight" />
            <span> Add Text </span>
          </div>
        </Button>
      </div>
      <Filter @update:model-value="(search: string) => (filter.search = search)" v-model="filter.search">
        <div class="flex gap-6">
          <Select
            class="w-1/2"
            label="Author"
            placeholder="Select an author"
            v-model="filter.author"
            :options="authors"
            track-by="name"
            display-by="name"
          />
        </div>
      </Filter>
    </div>
    <div class="flex-grow overflow-y-auto">
      <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-3">
        <div
          v-for="text in texts.data"
          :key="text.id"
          class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 hover:shadow-md"
        >
          <Link :href="'/texts/' + text.id">
            <div class="card-background relative flex h-32 w-full items-center justify-center overflow-hidden">
              <div
                class="absolute inset-0 opacity-15"
                :style="{
                  backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                  backgroundSize: '24px 24px',
                }"
              />
              <span
                v-if="text.type"
                class="absolute right-2 top-2 rounded-md bg-white bg-opacity-20 px-2 py-1 text-xs capitalize text-white"
              >
                {{ text.type }}
              </span>
              <span
                class="relative z-10 line-clamp-2 p-2 text-center font-playfair text-2xl font-semibold capitalize text-textLight"
                :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
              >
                {{ text.title }}
              </span>
            </div>
            <div class="p-4">
              <p class="text-sm font-semibold">
                {{ text.author?.name ?? 'unknown' }}
              </p>
              <div class="mt-1 flex items-center justify-start gap-1">
                <Icon icon="calendar" class="h-3 w-3 text-textSecondary" />
                <span class="text-xs text-textSecondary">
                  {{ text?.year || 'unknown date' }}
                </span>
              </div>
              <p class="line-clamp-3 pb-4 pt-3 text-sm italic">"{{ text.content }}...</p>
              <hr />
              <div class="mt-3 flex items-start justify-between">
                <div class="flex gap-1">
                  <Icon icon="message" class="mt-0.5 h-3.5 w-3.5 text-textSecondary" />
                  <span class="text-xs text-textSecondary">
                    {{ text?.text_notes }} footnote{{ text?.text_notes !== 1 ? 's' : '' }}
                  </span>
                </div>
                <span class="rounded-md bg-gray-100 px-2 py-1 text-sm text-textSecondary">
                  {{ text.language }}
                </span>
              </div>
            </div>
          </Link>
        </div>
      </div>
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
            {
              page: newPage,
              period: filter.author?.id,
            },
            { preserveState: true },
          )
      "
      entity="texts"
    />
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
