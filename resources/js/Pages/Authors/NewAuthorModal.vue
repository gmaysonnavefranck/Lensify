<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import TagInput from '@/Components/TagInput.vue';
  import { Author } from '@/types';
  import { ref } from 'vue';
  import { router } from '@inertiajs/vue3';

  // With `author`, the modal edits that author instead of creating a new one
  const props = defineProps<{ showAddAuthorModal: boolean; author?: Author }>();

  const emit = defineEmits<{ (e: 'close'): void }>();

  const emptyAuthor = () => ({
    name: '',
    nationality: '',
    born_year: '',
    death_year: '',
    period: '',
    genres: [] as string[],
    themes: [] as string[],
    famous_works: [] as string[],
    key_facts: [] as string[],
  });

  const fromAuthor = (author: Author) => ({
    name: author.name,
    nationality: author.nationality ?? '',
    born_year: author.born_year ?? '',
    death_year: author.death_year ?? '',
    period: author.literary_periods?.[0]?.name ?? '',
    genres: author.genres?.map((genre) => genre.name) ?? [],
    themes: author.themes?.map((theme) => theme.name) ?? [],
    famous_works: [...(author.famous_works ?? [])],
    key_facts: [...(author.key_facts ?? [])],
  });

  const newAuthor = ref(props.author ? fromAuthor(props.author) : emptyAuthor());
  const isLoading = ref(false);
  const isSavingAuthor = ref(!!props.author);
  const fetchError = ref('');

  const toText = (value: unknown) => (value === null || value === undefined ? '' : String(value));
  const toList = (value: unknown) => (Array.isArray(value) ? value.map(String) : []);

  async function findAuthorInformation() {
    if (!newAuthor.value.name.trim()) return;

    isLoading.value = true;
    fetchError.value = '';

    try {
      const { data } = await axios.post('/authors/fetch-author', { name: newAuthor.value.name });
      newAuthor.value = {
        name: newAuthor.value.name,
        nationality: toText(data.nationality),
        born_year: toText(data.born_year),
        death_year: toText(data.death_year),
        period: toText(data.period),
        genres: toList(data.genres),
        themes: toList(data.themes),
        famous_works: toList(data.famous_works),
        key_facts: toList(data.key_facts),
      };
    } catch {
      fetchError.value = "We couldn't fetch this author's details. You can fill them in yourself.";
    }

    isSavingAuthor.value = true;
    isLoading.value = false;
  }

  function createAuthor() {
    const data = { ...newAuthor.value, key_facts: newAuthor.value.key_facts.filter((fact) => fact.trim()) };
    const options = { preserveScroll: true, onSuccess: closeCreateAuthorModal };
    if (props.author) router.patch(`/authors/${props.author.id}`, data, options);
    else router.post('/authors', data, options);
  }

  function closeCreateAuthorModal() {
    emit('close');
    newAuthor.value = emptyAuthor();
    isSavingAuthor.value = false;
    fetchError.value = '';
  }
</script>

<template>
  <Modal :show="showAddAuthorModal" @close="closeCreateAuthorModal" closeable>
    <div class="flex max-h-[90vh] flex-col">
      <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h1 class="text-lg font-medium text-gray-900">{{ author ? 'Edit Author' : 'Add New Author' }}</h1>
        <Button type="ghost" icon="x" icon-class="h-5 w-5" @click="closeCreateAuthorModal" />
      </div>

      <div v-if="!isLoading && !isSavingAuthor" class="p-6">
        <InputText
          focus
          @keypress.enter="findAuthorInformation"
          v-model="newAuthor.name"
          placeholder="e.g., Dante Alighieri"
          label="Author Name"
        />
        <div class="mt-6 flex gap-3 rounded-lg bg-[#F0FDF4] dark:bg-green-500/10 p-4">
          <Icon icon="sparkles" class="mt-0.5 h-5 w-5 shrink-0 text-green-600" />
          <div>
            <p class="mb-1 text-sm font-medium text-textPrimary">We'll use AI to fetch author information</p>
            <p class="text-sm text-textSecondary">You'll be able to review and edit everything before saving.</p>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="flex flex-col items-center justify-center py-12">
        <Icon icon="loading" class="mb-4 h-8 w-8 animate-spin text-primary" />
        <p class="text-sm text-gray-600">Fetching author information...</p>
      </div>

      <div v-if="!isLoading && isSavingAuthor" class="min-h-0 space-y-6 overflow-y-auto p-6">
        <div v-if="fetchError" class="flex gap-3 rounded-lg bg-red-50 p-4 dark:bg-red-500/10">
          <Icon icon="info" class="h-5 w-5 shrink-0 text-error" />
          <span class="text-sm text-error">{{ fetchError }}</span>
        </div>
        <div v-else-if="!author" class="flex gap-3 rounded-lg bg-blue-50 p-4 dark:bg-blue-500/10">
          <Icon icon="info" class="h-5 w-5 shrink-0 text-blue-600" />
          <span class="text-sm text-blue-700 dark:text-blue-300">Review the information below. You can edit it before saving.</span>
        </div>

        <section class="grid grid-cols-2 gap-4">
          <p class="col-span-2 text-xs font-semibold uppercase tracking-wider text-secondary">Basic information</p>
          <InputText v-model="newAuthor.name" placeholder="Author name" label="Name" />
          <InputText v-model="newAuthor.nationality" placeholder="e.g., Italian" label="Nationality" />
          <InputText v-model="newAuthor.born_year" placeholder="e.g., 1265" label="Birth year" />
          <InputText v-model="newAuthor.death_year" placeholder="Leave empty if alive" label="Death year" />
          <InputText
            class="col-span-2"
            v-model="newAuthor.period"
            placeholder="e.g., Renaissance"
            label="Literary period"
          />
        </section>

        <section class="space-y-4">
          <p class="text-xs font-semibold uppercase tracking-wider text-secondary">Literary profile</p>
          <TagInput v-model="newAuthor.genres" label="Genres" placeholder="Type a genre and press Enter" />
          <TagInput v-model="newAuthor.themes" label="Themes" placeholder="Type a theme and press Enter" />
          <TagInput v-model="newAuthor.famous_works" label="Famous works" placeholder="Type a title and press Enter" />
        </section>

        <section>
          <p class="label">Key facts</p>
          <div class="space-y-2">
            <div v-for="(_, i) in newAuthor.key_facts" :key="i" class="flex items-start gap-2">
              <textarea
                v-model="newAuthor.key_facts[i]"
                rows="2"
                class="w-full resize-none rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
              />
              <Button type="ghost" icon="x" @click="newAuthor.key_facts.splice(i, 1)" />
            </div>
          </div>
          <Button type="ghost" icon="plus" text="Add fact" class="mt-2" @click="newAuthor.key_facts.push('')" />
        </section>
      </div>

      <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
        <Button text="Cancel" type="ghost" @click="closeCreateAuthorModal" />
        <Button
          v-if="!isSavingAuthor"
          text="Continue"
          type="primary"
          :disabled="!newAuthor.name.trim() || isLoading"
          @click="findAuthorInformation"
        />
        <Button
          v-else
          :text="author ? 'Save changes' : 'Save author'"
          type="primary"
          :disabled="!newAuthor.name.trim()"
          @click="createAuthor"
        />
      </div>
    </div>
  </Modal>
</template>
