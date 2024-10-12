<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import { Text } from '@/types/index';
  import { onMounted, ref } from 'vue';

  import axios from 'axios';

  interface textProps {
    text: Text;
    response?: string;
  }

  const generatedText = ref<string>('');
  const selectedText = ref<string>('');

  defineProps<textProps>();

  onMounted(() => {
    document.addEventListener('mouseup', function () {
      selectedText.value = window.getSelection().toString();
    });
  });

  async function generateExplanation() {
    const response = await axios.post('/chat/analyze-text', {
      text: selectedText.value,
    });

    // Parse the JSON response
    const data = await response.data.footNote;
    generatedText.value = data;
  }
</script>

<template>
  <AuthLayout>
    <div class="flex h-full">
      <div class="h-full w-1/2">
        <p class="mb-4 text-center text-xl font-semibold">{{ text.title }}</p>
        <span class="whitespace-pre">
          {{ text.content }}
        </span>
      </div>
      <hr class="mx-6 h-full w-1 bg-[#f1f1f1]" />
      <div class="w-1/2">
        <div>
          <p>Author</p>
          {{ text?.author?.name || '-' }}
        </div>
        <hr class="my-6 h-px w-full bg-slate-400" />
        <div>
          <p>selected text:</p>
          <p class="whitespace-pre">
            {{ selectedText }}
          </p>
          <button class="my-2 border px-3 py-1" @click="generateExplanation">Generate explanation</button>
          <p class="text-sm">
            {{ generatedText }}
          </p>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>
