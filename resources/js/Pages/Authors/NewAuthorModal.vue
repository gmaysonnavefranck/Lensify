<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import { Author } from '@/types';
  import { ref } from 'vue';
  import { router } from '@inertiajs/vue3';
  import Select from '@/Components/Select.vue';

  interface NewAuthorModalProps {
    showAddAuthorModal: boolean;
  }

  const emit = defineEmits<{ (e: 'close'): void }>();

  defineProps<NewAuthorModalProps>();

  const newAuthor = ref<Partial<Author>>({
    name: '',
  });

  const isLoading = ref(false);
  const isSavingAuthor = ref(false);
  const newGenres = ref([]);
  const newThemes = ref([]);
  const key_facts = ref([]);
  const famous_works = ref([]);

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
    await router.post('/authors', {
      ...newAuthor.value,
    });
    isSavingAuthor.value = false;
    emit('close');
    newAuthor.value = {
      name: '',
    };
  }

  function closeCreateAuthorModal() {
    emit('close');
    newAuthor.value = {
      name: '',
    };
    isSavingAuthor.value = false;
  }
</script>

<template>
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
          <Icon icon="sparkles" class="h-5 w-5" />
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
          :open-direction="'above'"
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
