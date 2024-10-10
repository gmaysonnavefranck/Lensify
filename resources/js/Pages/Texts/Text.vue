<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import { Text } from '@/types/index';

  interface textProps {
    text: Text;
  }

  defineProps<textProps>();
</script>

<template>
  <AuthLayout>
    <div class="flex h-full">
      <div class="h-full w-1/2">
        <p class="mb-4 text-center text-xl font-semibold">{{ text.title }}</p>
        <span>
          {{ text.content }}
        </span>
      </div>
      <hr class="mx-6 h-full w-1 bg-[#f1f1f1]" />
      <div class="w-1/2">
        <div>
          <p>Author</p>
          {{ text?.author?.name || '-' }}
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
