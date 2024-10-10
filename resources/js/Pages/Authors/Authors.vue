<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import { Author } from '@/types/index';

  interface AuthorsPageProps {
    authors: Author[];
  }

  defineProps<AuthorsPageProps>();
</script>

<template>
  <AuthLayout>
    <h1>Authors</h1>
    <div class="flex">
      <div v-for="author in authors" :key="author.id" class="m-6 flex flex-col">
        <div class="flex w-[120px] items-center justify-center rounded bg-[#B7B7A4] p-4">
          <p class="text-6xl tracking-tighter">
            {{ author?.name.split(' ')[0][0] }}{{ author?.name.split(' ')[author?.name.split(' ').length - 1][0] }}
          </p>
        </div>
        <p class="text-center">
          {{ author?.name }}
        </p>
        <div>{{ author.texts.length }} texts</div>
      </div>
    </div>
  </AuthLayout>
</template>
