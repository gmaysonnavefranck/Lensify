<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Modal from '@/Components/Modal.vue';
  import { Author } from '@/types/index';
  import { ref } from 'vue';
  import { Link, router } from '@inertiajs/vue3';

  interface AuthorsPageProps {
    authors: Author[];
  }

  defineProps<AuthorsPageProps>();

  const newAuthor = ref<Author>({
    name: '',
  });
  const showAddAuthorModal = ref(false);

  function getAuthorInitials(name: string) {
    return name.split(' ')[0][0] + (name.split(' ').length > 1 ? name.split(' ')[name.split(' ').length - 1][0] : '');
  }

  async function deleteAuthor(id: number) {
    router.delete(`/authors/${id}`, {
      onFinish: () => {
        router.reload();
      },
    });
  }
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
      <div v-for="author in authors" :key="author.id" class="m-6 flex flex-col shadow">
        <Link :href="'/authors/' + author.id">
          <div class="flex w-60 h-40 items-center justify-center rounded rounded-b-none bg-[#e5e5e5] p-4">
            <p class="text-6xl tracking-tighter uppercase">
              {{ getAuthorInitials(author.name) }}
            </p>
          </div>
          <p class="text-center text-2xl capitalize">
            {{ author?.name }}
          </p>
          <div>{{ author?.texts?.length }} texts</div>

        </Link>
        <button class="mt-3 rounded border border-blue-100 px-4 py-1 font-semibold" @click="deleteAuthor(author.id)">
          Delete Author
        </button>
      </div>
    </div>
    <Modal :show="showAddAuthorModal" @close="showAddAuthorModal = false" closeable>
      <div class="p-4">
        <h1 class="text-center text-xl font-semibold">Add Author</h1>
        <form class="flex flex-col">
          <label for="title">Name:</label>
          <input v-model="newAuthor.name" type="text" id="title" />
          <Link href="/authors" as="button" method="post" :data="newAuthor">Create Author</Link>
        </form>
      </div>
    </Modal>
  </AuthLayout>
</template>
