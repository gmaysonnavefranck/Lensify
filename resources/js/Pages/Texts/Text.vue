<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import { Text } from '@/types/index';
  import { onMounted, ref } from 'vue';
  import { Link, router } from '@inertiajs/vue3';

  import axios from 'axios';

  interface textProps {
    text: Text;
    response?: string;
  }

  const generatedText = ref<string>('');
  const selectedText = ref<string>('');

  const props = defineProps<textProps>();

  onMounted(() => {
    document.addEventListener('mouseup', function () {
      if(!window)
      if (window.getSelection().toString().length > 0) {
        selectedText.value = window.getSelection().toString();
      }
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

  async function associateAuthor() {
    await axios.post(`/texts/${props.text.id}/assign-author`, {
      author_id: 1,
    });
    router.reload();
  }
</script>

<template>
  <AuthLayout>
    <div class="flex h-full">
      <div class="h-full w-1/2">
        <p class="mb-4 text-center text-xl font-semibold">{{ text.title }}</p>
        <div class="text-box">
          <span class="whitespace-pre">
            {{ text.content }}
          </span>
        </div>
      </div>
      <hr class="mx-6 h-full w-1 bg-[#f1f1f1]" />
      <div class="w-1/2">
        <div>
          <p>Author</p>
          <div v-if="text?.author">
            {{ text?.author || '-' }}
          </div>
          <div v-else>
            <button class="my-2 border px-3 py-1" @click="associateAuthor">Associate author</button>
          <Link :href="`/texts/${text.id}/assign-author`" as="button" method="post" :data="{ author_id: 1}">Create Text</Link>

          </div>

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
        <hr class="my-6 h-px w-full bg-slate-400" />
        <div>
          <p>Foot notes</p>
          <p class="text-sm">
            {{ text?.footNotes }}
          </p>
        </div>
      </div>
    </div>
  </AuthLayout>
</template>

 <style scoped>

.text-box {
  --s: 50px; /* the size on the corner */
  --t: 5px;  /* the thickness of the border */
  --g: 20px; /* the gap between the border and image */
  
  padding: calc(var(--g) + var(--t));
  outline: var(--t) solid #B38184; /* the color here */
  outline-offset: calc(-1*var(--t));
  mask:
    conic-gradient(at var(--s) var(--s),#0000 75%,#000 0)
    0 0/calc(100% - var(--s)) calc(100% - var(--s)),
    linear-gradient(#000 0 0) content-box;
  transition: .4s;
}
</style>
