<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Modal from '@/Components/Modal.vue';
  import { Author } from '@/types/index';
  import { ref } from 'vue';
  import { Link } from '@inertiajs/vue3';

  interface AuthorsPageProps {
    authors: Author[];
  }

  defineProps<AuthorsPageProps>();

  const newAuthor = ref<Author>({
    name: '',
  });
  const showAddAuthorModal = ref(false);
</script>

<template>
  <AuthLayout>
    <div class="flex w-full">
      <h1 class="text-2xl font-semibold">Authors</h1>
      <button class="ml-3 rounded border border-blue-100 px-4 py-1 font-semibold" @click="showAddAuthorModal = true">
        Add Author
      </button>
    </div>
    <div class="flex flex-wrap">
      <div v-for="author in authors" :key="author.id" class="m-6 flex flex-col ">
        <Link :href="'/authors/' + author.id">
          <div class="flex w-[120px] items-center justify-center rounded bg-[#B7B7A4] p-4">
            <p class="text-6xl tracking-tighter">
              {{ author?.name.split(' ')[0][0] }}{{ author?.name.split(' ')[author?.name.split(' ').length - 1][0] }}
            </p>
          </div>
          <p class="text-center">
            {{ author?.name }}
          </p>
          <div>{{ author.texts.length }} texts</div>
        </Link>
      </div>
    </div>
    <Modal :show="showAddAuthorModal" @close="showAddAuthorModal = false" closeable>
      <div class="p-4">
        <h1 class="text-center text-xl font-semibold">Add Author</h1>
        <form class="flex flex-col" @submit.prevent="submit">
          <label for="title">Name:</label>
          <input v-model="newAuthor.name" type="text" id="title" />
          <Link href="/authors" as="button" method="post" :data="newAuthor">Create Author</Link>
        </form>
      </div>
    </Modal>
  </AuthLayout>
</template>
