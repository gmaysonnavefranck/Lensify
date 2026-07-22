<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import Select from '@/Components/Select.vue';
  import { Author, Text } from '@/types';
  import { ref } from 'vue';
  import { router } from '@inertiajs/vue3';

  interface NewTextModalProps {
    showAddTextModal: boolean;
    authors: Author[];
  }

  const emit = defineEmits<{ (e: 'close'): void }>();

  defineProps<NewTextModalProps>();

  const isSavingText = ref(false);
  const isLoading = ref(false);
  const newText = ref<Text>({
    title: '',
    content: '',
  });

  async function findTextInformation() {
    if (!newText.value.content) return;

    isLoading.value = true;

    const response = await axios.post(
      '/texts/fetch-text',
      {
        content: newText.value.content,
        title: newText.value.title,
        author: newText.value.author?.name,
      },
      {
        headers: {
          'Content-Type': 'application/json',
          charset: 'utf-8',
        },
      },
    );

    const data = await response.data;

    if (!newText.value.title) {
      newText.value.title = data.title;
    }

    if (!newText.value.author) {
      newText.value.author = data.author;
    }

    newText.value.language = data.language;
    newText.value.year = data.year;
    newText.value.type = data.type;

    isSavingText.value = true;
    isLoading.value = false;
  }

  async function createText() {
    await router.post('/texts', {
      ...newText.value,
      author_id: newText.value.author?.id,
    });
    isSavingText.value = false;
    emit('close');
    newText.value = {
      content: '',
      title: '',
    };
  }

  function closeCreateTextModal() {
    emit('close');
    newText.value = {
      title: '',
      content: '',
    };
    isSavingText.value = false;
  }
</script>

<template>
  <Modal :show="showAddTextModal" @close="closeCreateTextModal" closeable>
    <div>
      <div class="flex items-center justify-between px-6 py-4">
        <h1 class="text-center text-lg font-medium text-gray-900">Add New Text</h1>
        <Icon icon="x" class="m-2 h-5 w-5 cursor-pointer text-textSecondary" @click="closeCreateTextModal" />
      </div>
      <hr />
      <div class="p-6" v-if="!isLoading && !isSavingText">
        <div class="mb-6 flex items-start justify-start gap-3 rounded-lg bg-blue-50 p-4">
          <Icon icon="info" class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" />
          <span class="text-sm">
            Just paste or type your text below. Don't worry if you don't have all the details - our AI will help
            identify the title, author, and other information.
          </span>
        </div>
        <div class="mb-6">
          <label class="label"> Your Text </label>
          <textarea
            v-model="newText.content"
            placeholder="Paste or type your text here..."
            rows="10"
            class="w-full rounded-md border border-gray-200 px-4 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-200"
          />
        </div>
        <div class="mb-6 flex items-start justify-start gap-3 rounded-lg bg-[#F0FDF4] p-4">
          <Icon icon="sparkles" class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" />
          <div>
            <p className="mt-1 mb-2 text-sm font-medium leading-none text-textPrimary">We'll help identify:</p>
            <ul className="text-sm space-y-1 text-textSecondary">
              <li class="font-light">• Title (if not provided)</li>
              <li class="font-light">• Author and their details</li>
              <li class="font-light">• Text type (poem, essay, etc.)</li>
              <li class="font-light">• Language and year of publication</li>
            </ul>
          </div>
        </div>
        <hr class="mb-4" />
        <p class="mb-4 text-sm font-light text-textSecondary">Optional: Fill these if you know them</p>
        <InputText class="mb-4" v-model="newText.title" placeholder="Title (if known)" />
        <Select
          v-model="newText.author"
          placeholder="Author (if known, you may also add it later)"
          open-direction="top"
          display-by="name"
          :options="authors"
        />
      </div>
      <div class="flex flex-col items-center justify-center py-12" v-if="isLoading">
        <Icon icon="loading" class="mb-4 h-8 w-8 animate-spin text-primary" />
        <p class="mb-2 text-lg font-medium text-textPrimary">Analyzing your text</p>
        <p class="text-sm text-textSecondary">We're processing the content and identifying key information...</p>
      </div>
      <div v-if="!isLoading && isSavingText" class="p-6">
        <div class="mb-6 flex items-start justify-start gap-3 rounded-lg bg-blue-50 p-4">
          <Icon icon="check" class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600" />
          <span class="text-sm">
            We've analyzed your text. Please review and edit the information below if needed.
          </span>
        </div>
        <InputText class="mb-4" v-model="newText.title" placeholder="Title" label="Title" />
        <InputText class="mb-4" v-model="newText.author" placeholder="Author" label="Author" />
        <div class="flex gap-4">
          <InputText class="mb-4 w-1/2" v-model="newText.type" placeholder="Type" label="Type" />
          <InputText
            class="mb-4 w-1/2"
            v-model="newText.year"
            placeholder="Year of publication"
            label="Year of publication"
          />
        </div>
        <InputText class="mb-4" v-model="newText.language" placeholder="Language" label="Language" />
      </div>
      <hr />
      <div class="flex justify-end gap-3 px-6 py-4">
        <Button text="Cancel" type="ghost" @click="closeCreateTextModal" />
        <Button
          text="Continue"
          type="primary"
          v-if="!isSavingText && !isLoading"
          :disabled="!newText.content"
          @click="findTextInformation"
        />
        <Button text="Save" type="primary" v-if="isSavingText" :disabled="!newText.content" @click="createText" />
      </div>
    </div>
  </Modal>
</template>
