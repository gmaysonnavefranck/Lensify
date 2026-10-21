<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Filter from '@/Components/Filter.vue';
  import Icon from '@/Components/Icon.vue';
  import Pagination from '@/Components/Pagination.vue';
  import Select from '@/Components/Select.vue';
  import { Author, PaginatedResponse, Genre, Period } from '@/types/index';
  import { onMounted, ref, watch } from 'vue';
  import { Link } from '@inertiajs/vue3';
  import { router } from '@inertiajs/vue3';
  import NewAuthorModal from './NewAuthorModal.vue';

  interface AuthorsPageProps {
    authors: PaginatedResponse<Author>;
    genres: Genre[];
    periods: Period[];
    filters: {
      period: string;
      genre: string;
      search: string;
    };
  }

  const props = defineProps<AuthorsPageProps>();

  defineOptions({ layout: AuthLayout });

  const showAddAuthorModal = ref(false);

  const filter = ref<{
    period: Period | null;
    genre: Genre | null;
    search: string;
  }>({
    period: null,
    genre: null,
    search: props.filters?.search || '',
  });

  onMounted(() => {
    filter.value.period = props.filters.period
      ? props.periods.find((p) => p.id === Number(props.filters.period)) || null
      : null;
    filter.value.genre = props.filters.genre
      ? props.genres.find((g) => g.id === Number(props.filters.genre)) || null
      : null;
  });

  watch(
    () => filter,
    (newFilter) => {
      router.get(
        '/authors',
        {
          period: newFilter.value?.period?.id,
          genre: newFilter.value?.genre?.id,
          search: newFilter.value.search,
        },
        { preserveState: true, replace: true },
      );
    },
    { deep: true },
  );
</script>

<template>
  <Head title="Authors" />
  <div class="h-full overflow-y-auto">
    <div class="mx-auto w-full max-w-6xl px-8 py-8">
      <div class="mb-6 flex items-end justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-textPrimary">Your Authors</h1>
          <p class="mt-1 text-sm text-secondary">Authors you've added to your collection</p>
        </div>
        <Button type="primary" icon="plus" text="Add Author" @click="showAddAuthorModal = true" />
      </div>
      <Filter @update:model-value="(search: string) => (filter.search = search)" v-model="filters.search">
        <div class="grid gap-4 sm:grid-cols-2">
          <Select
            label="Literary Period"
            placeholder="Any period"
            v-model="filter.period"
            :options="periods"
            track-by="name"
            display-by="name"
          />
          <Select
            label="Genre"
            placeholder="Any genre"
            v-model="filter.genre"
            :options="genres"
            track-by="name"
            display-by="name"
          />
        </div>
      </Filter>

      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <Link
          v-for="author in authors.data"
          :key="author.id"
          :href="'/authors/' + author.id"
          class="overflow-hidden rounded-lg border border-gray-200 bg-white transition-shadow hover:shadow-md"
        >
          <div class="card-background relative flex h-20 items-center justify-center overflow-hidden px-6">
            <div
              class="absolute inset-0 opacity-15"
              :style="{
                backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                backgroundSize: '24px 24px',
              }"
            />
            <span
              class="relative truncate font-playfair text-xl font-semibold capitalize text-textLight"
              :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
            >
              {{ author.name }}
            </span>
          </div>
          <div class="flex items-center justify-between gap-2 px-4 py-3 text-xs text-textSecondary">
            <span class="truncate">
              {{
                [author.nationality, [author.born_year, author.death_year].filter(Boolean).join('–')]
                  .filter(Boolean)
                  .join(' · ') || 'No details yet'
              }}
            </span>
            <span class="flex shrink-0 items-center gap-1">
              <Icon icon="texts" class="h-3.5 w-3.5" />
              {{ author.texts?.length || 0 }} text{{ author.texts?.length === 1 ? '' : 's' }}
            </span>
          </div>
        </Link>
      </div>

      <Pagination
        :current_page="authors.current_page"
        :per_page="authors.per_page"
        :total="authors.total"
        :last_page="authors.last_page"
        @update:current_page="
          (newPage) =>
            router.get(
              '/authors',
              {
                page: newPage,
                period: filter.period?.id,
                genre: filter.genre?.id,
                search: filter.search,
              },
              { preserveState: true },
            )
        "
        entity="authors"
      />
    </div>
  </div>
  <NewAuthorModal
    v-if="showAddAuthorModal"
    :showAddAuthorModal="showAddAuthorModal"
    @close="showAddAuthorModal = false"
  />
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
