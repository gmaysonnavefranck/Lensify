<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Modal from '@/Components/Modal.vue';
  import { Author, Text } from '@/types/index';
  import { Link } from '@inertiajs/vue3';
  import { ref } from 'vue';

  interface TextsPageProps {
    texts: Text[];
    authors: Author[];
  }

  const showAddTextModal = ref(false);
  const newText = ref<Text>({
    title: '',
    content: '',
    author_id: null,
  });

  function openAddTextModal() {}

  function createText() {
    Inertia.post('/texts', newText.value);
  }

  defineProps<TextsPageProps>();
</script>

<template>
  <AuthLayout>
    <div class="flex flex-wrap gap-3">
      <div class="flex w-full">
        <h1 class="text-2xl font-semibold">Texts</h1>
        <button class="ml-3 rounded border border-blue-100 px-4 py-1 font-semibold" @click="showAddTextModal = true">
          Add Text
        </button>
      </div>
      <div v-for="text in texts" :key="text.id" class="w-[300px] rounded border-2 border-red-300 p-3">
        <Link :href="'/texts/' + text.id">
          <h1 class="mb-2 text-center font-semibold">{{ text.title }}</h1>
          <p>{{ text.author?.name ?? 'unknown' }}</p>
        </Link>
      </div>
    </div>
    <Modal :show="showAddTextModal" @close="showAddTextModal = false" closeable>
      <div class="p-4">
        <h1 class="text-center text-xl font-semibold">Add Text</h1>
        <form class="flex flex-col" @submit.prevent="submit">
          <label for="title">Title:</label>
          <input v-model="newText.title" type="text" id="title" />
          <label for="content">Content:</label>
          <textarea v-model="newText.content" id="content"></textarea>
          <label for="author_id">Author:</label>
          <select v-model="newText.author_id">
            <option v-for="author in authors" :key="author.id" :value="author.id">{{ author.name }}</option>
          </select>
          <Link href="/texts" as="button" method="post" :data="newText">Create Text</Link>
        </form>
      </div>
    </Modal>
  </AuthLayout>
</template>
