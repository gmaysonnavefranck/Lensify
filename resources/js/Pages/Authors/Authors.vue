<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Filter from '@/Components/Filter.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import Pagination from '@/Components/Pagination.vue';
  import Select from '@/Components/Select.vue';
  import { Author, PaginatedResponse, Genre, Period } from '@/types/index';
  import { onMounted, ref, watch } from 'vue';
  import { Link } from '@inertiajs/vue3';
  import { router } from '@inertiajs/vue3';

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

  const newAuthor = ref<Author>({
    name: '',
  });
  const showAddAuthorModal = ref(false);
  const isLoading = ref(false);
  const isSavingAuthor = ref(false);
  const newGenres = ref([]);
  const newThemes = ref([]);
  const key_facts = ref([]);
  const famous_works = ref([]);
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

  async function findAuthorInformation() {
    if (!newAuthor.value.name) return;
    if (newAuthor.value.name.length < 3 || newAuthor.value.name.length > 20) return;

    isLoading.value = true;

    const response = await axios.post('/authors/fetch-author', {
      name: newAuthor.value.name,
    });

    const data = await response.data;

    newAuthor.value = { ...newAuthor.value, ...data };
    newGenres.value = data.genres;
    newThemes.value = data.themes;
    key_facts.value = data.key_facts;
    famous_works.value = data.famous_works;

    isSavingAuthor.value = true;
    isLoading.value = false;
  }

  async function createAuthor() {
    const response = await router.post('/authors', {
      ...newAuthor.value,
    });
    isSavingAuthor.value = false;
    showAddAuthorModal.value = false;
    newAuthor.value = {
      name: '',
    };
  }

  function getAuthorInitials(name: string) {
    return name.split(' ')[0][0] + (name.split(' ').length > 1 ? name.split(' ')[name.split(' ').length - 1][0] : '');
  }

  function closeCreateAuthorModal() {
    showAddAuthorModal.value = false;
    newAuthor.value = {
      name: '',
    };
    isSavingAuthor.value = false;
  }
</script>

<template>
  <Head title="Authors" />
  <div class="flex h-full flex-col">
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
      <div class="grid min-h-[300px] w-full grid-cols-1 gap-6 md:grid-cols-3">
        <div
          v-for="author in authors.data"
          :key="author.id"
          class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 hover:shadow-md"
        >
          <Link :href="'/authors/' + author.id">
            <div class="card-background relative flex h-32 w-full items-center justify-center overflow-hidden">
              <div
                class="absolute inset-0 opacity-15"
                :style="{
                  backgroundImage: `radial-gradient(circle at 2px 2px, white 1px, transparent 0)`,
                  backgroundSize: '24px 24px',
                }"
              />
              <span
                class="relative z-10 font-playfair text-5xl font-semibold uppercase text-textLight"
                :style="{ textShadow: '0 2px 4px rgba(0,0,0,0.2)' }"
              >
                {{ getAuthorInitials(author.name) }}
              </span>
            </div>
            <div class="p-6">
              <p class="mb-1 text-center text-lg font-medium capitalize leading-tight text-textPrimary">
                {{ author?.name }}
              </p>
              <p class="text-center text-sm tracking-wider text-textSecondary">
                {{ author?.born_year }}{{ author?.death_year && `-${author.death_year}` }}
              </p>
              <div class="my-4 h-px w-full bg-gray-100" />
              <div class="flex items-center justify-center gap-2">
                <div class="text-center">
                  <p class="flex gap-2 text-sm font-semibold text-textPrimary">
                    <Icon icon="texts" class="h-4 w-4 text-textPrimary" />
                    {{ author?.texts?.length }}
                  </p>
                  <p class="text-sm text-secondary">Texts</p>
                </div>
                <div class="text-center">
                  <p class="flex gap-2 text-sm font-semibold text-textPrimary">
                    <Icon icon="texts" class="h-4 w-4 text-textPrimary" />
                    {{ author?.texts?.length }}
                  </p>
                  <p class="text-sm text-secondary">Quotes</p>
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
  <Modal :show="showAddAuthorModal" @close="closeCreateAuthorModal" closeable>
    <div>
      <div class="flex items-center justify-between px-6 py-4">
        <h1 class="text-center text-lg font-medium text-gray-900">Add New Author</h1>
        <Icon icon="x" class="m-2 h-5 w-5 cursor-pointer text-textSecondary" @click="closeCreateAuthorModal" />
      </div>
      <hr />
      <div class="flex flex-col p-6" v-if="!isLoading && !isSavingAuthor">
        <InputText
          focus
          @keypress.enter="findAuthorInformation"
          v-model="newAuthor.name"
          placeholder="e.g., Dante Alighieri"
          label="Author Name"
        />
        <div class="mt-6 flex gap-3 p-4">
          <Icon icon="sparkles" class="h-5 w-5" @click="closeCreateAuthorModal" />
          <div class="flex flex-col">
            <span class="mb-1 text-sm font-medium leading-none text-textPrimary"
              >We'll use AI to fetch author information</span
            >
            <span class="text-sm text-textSecondary"
              >Don't worry - you'll be able to review and edit all information before adding the author.</span
            >
          </div>
        </div>
      </div>
      <div class="flex flex-col items-center justify-center py-12" v-if="isLoading">
        <Icon icon="loading" class="mb-4 h-8 w-8 animate-spin" />
        <p class="text-sm text-gray-600">Fetching author information...</p>
      </div>
      <div class="grid grid-cols-2 gap-5 p-6" v-if="!isLoading && isSavingAuthor">
        <div class="col-span-2 mb-2 flex gap-3 rounded-lg bg-blue-50 p-4">
          <Icon icon="info" class="h-5 w-5 text-blue-600" />
          <span class="text-sm text-blue-700"> Review the information below. You can edit it before saving. </span>
        </div>
        <p class="col-span-2 text-sm font-semibold text-gray-500">Basic Information</p>
        <InputText v-model="newAuthor.name" placeholder="Author name" label="Name" />
        <InputText v-model="newAuthor.nationality" placeholder="Author nationality" label="Nationality" />
        <InputText v-model="newAuthor.born_year" placeholder="Birth Year" label="Birth Year" />
        <InputText v-model="newAuthor.death_year" placeholder="Death Year" label="Death Year" />
        <InputText
          class="col-span-2"
          v-model="newAuthor.period"
          placeholder="Literary Period"
          label="Literary Period"
        />
        <p class="col-span-2 text-sm font-semibold text-gray-500">Literary Profile</p>
        <Select v-model="newAuthor.genres" placeholder="Genres" label="Genres" multiple taggable :options="newGenres" />
        <Select v-model="newAuthor.themes" placeholder="Themes" label="Themes" multiple taggable :options="newThemes" />
        <Select
          class="col-span-2"
          v-model="newAuthor.famous_works"
          placeholder="Famous Works"
          label="Famous Works"
          multiple
          taggable
          :options="famous_works"
        />
        <Select
          class="col-span-2"
          v-model="newAuthor.key_facts"
          placeholder="Key Facts"
          label="Key Facts"
          multiple
          taggable
          :options="key_facts"
        />
      </div>
      <hr />
      <div class="flex justify-end gap-3 px-6 py-4">
        <Button text="Cancel" type="ghost" @click="closeCreateAuthorModal" />
        <Button
          text="Continue"
          type="primary"
          v-if="!isSavingAuthor"
          :disabled="!newAuthor.name"
          @click="findAuthorInformation"
        />
        <Button text="Save" type="primary" v-if="isSavingAuthor" :disabled="!newAuthor.name" @click="createAuthor" />
      </div>
    </div>
  </Modal>
</template>

<style scoped>
  .card-background {
    background: linear-gradient(135deg, #395076 0%, #435d87 50%, #4f6d9e 100%);
  }
</style>
