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
  <div class="flex h-full w-full max-w-5xl flex-col place-self-center p-10">
    <div>
      <div class="mb-6 flex w-full items-center justify-between">
        <div>
          <h1 class="text-4xl font-bold text-textPrimary">Your Authors</h1>
          <span class="text-base font-normal text-secondary">Authors you've added to your collection</span>
        </div>
        <Button text="Add Author" type="primary" @click="showAddAuthorModal = true">
          <div class="flex items-center gap-2 p-1">
            <Icon icon="plus" class="h-5 w-5 text-textLight" />
            <span> Add Author </span>
          </div>
        </Button>
      </div>
      <Filter @update:model-value="(search: string) => (filter.search = search)" v-model="filters.search">
        <div class="flex gap-6">
          <Select
            class="w-1/2"
            label="Literary Period"
            placeholder="Any Period"
            v-model="filter.period"
            :options="periods"
            track-by="name"
            display-by="name"
          />
          <Select
            class="w-1/2"
            label="Genre"
            placeholder="Any Genre"
            v-model="filter.genre"
            :options="genres"
            track-by="name"
            display-by="name"
          />
        </div>
      </Filter>
    </div>
    <div class="flex-grow overflow-y-auto">
      <div class="grid w-full grid-cols-1 gap-6 md:grid-cols-3">
        <div
          v-for="author in authors.data"
          :key="author.id"
          class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 hover:shadow-md"
        >
          <Link :href="'/authors/' + author.id">
            <div class="card-background relative flex h-28 w-full items-center justify-center overflow-hidden">
              <div
                class="absolute inset-0 opacity-15"
                :style="{
                  backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                  backgroundSize: '24px 24px',
                }"
              />
              <span
                class="relative z-10 text-center font-playfair text-3xl font-semibold capitalize text-textLight"
                :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
              >
                {{ author.name }}
              </span>
            </div>
            <div class="p-5">
              <p class="text-center text-sm tracking-wider text-textSecondary">
                {{ author.nationality }} ({{ author?.born_year }}{{ author?.death_year && `-${author.death_year}` }})
              </p>
              <div class="my-4 h-px w-full bg-gray-100" />
              <div class="flex items-center justify-around text-xs text-secondary">
                <div class="flex flex-col">
                  <p class="text-center font-semibold">{{ author.texts?.length || 0 }}</p>
                  <p class="text-sm">Texts</p>
                </div>
                <div class="flex flex-col">
                  <p class="text-center font-semibold">{{ author.texts?.length || 0 }}</p>
                  <p class="text-sm">Quotes</p>
                </div>
                <div class="flex flex-col">
                  <p class="text-center font-semibold">{{ author.texts?.length || 0 }}</p>
                  <p class="text-sm">Books</p>
                </div>
              </div>
            </div>
          </Link>
        </div>
      </div>
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
