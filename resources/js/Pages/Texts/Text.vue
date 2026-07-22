<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { Text } from '@/types/index';
  import { computed, onMounted, ref } from 'vue';
  import { Link, router } from '@inertiajs/vue3';

  import axios from 'axios';

  interface textProps {
    text: Text;
    response?: string;
  }

  const selectedText = ref<string>('');
  const isWritingNote = ref<boolean>(false);
  const isGeneratingExplanation = ref<boolean>(false);
  const isAiGenerated = ref<boolean>(false);
  const newFootnote = ref<string>('');
  const selectedTextPosition = ref<{ top: number; left: number } | null>(null);
  const isTranslationMenuOpen = ref<boolean>(false);
  const selectedTranslation = ref<{ language: string; id: number; content: string }>({
    language: '',
    id: 0,
    content: '',
  });

  const props = defineProps<textProps>();

  defineOptions({ layout: AuthLayout });

  onMounted(() => {
    selectedTranslation.value = {
      id: props.text.id,
      language: props.text.language || 'Unknown language',
      content: props.text.content,
    };
    document.addEventListener('mouseup', function (event) {
      if (event.target?.id !== 'content-container') return;
      const selection = window.getSelection();
      const text = selection?.toString().trim();
      if (text && (!isWritingNote.value || !isGeneratingExplanation.value)) {
        selectedText.value = text;
        const range = selection?.getRangeAt(0);
        const rect = range?.getBoundingClientRect();
        if (rect) {
          selectedTextPosition.value = {
            top: rect.bottom + window.scrollY,
            left: rect.right,
          };
        }
      }
    });
  });

  const allLanguages = computed(() => {
    const translations: { language: string; id: number; content: string }[] =
      props.text.translations?.map((translation) => ({
        language: translation.language as string,
        id: translation.id,
        content: translation.content,
      })) || [];
    translations.unshift({
      id: props.text.id,
      language: props.text.language || 'Unknown language',
      content: props.text.content,
    });
    return translations;
  });

  async function generateExplanation() {
    isGeneratingExplanation.value = true;
    const response = await axios.post('/chat/analyze-text', {
      text: selectedText.value,
    });

    // Parse the JSON response
    const data = await response.data.footNote;
    newFootnote.value = data;
    isAiGenerated.value = true;
    isGeneratingExplanation.value = false;
    isWritingNote.value = true;
  }

  async function saveFootnote() {
    let start_position = 0;
    let end_position = 0;

    const start = props.text.content.indexOf(selectedText.value);

    if (start !== -1) {
      start_position = start;
      end_position = start + selectedText.value.length;
    } else {
      return console.warn('Selected text not found in the full text.');
    }

    await axios.post(`/texts/${props.text.id}/footnote`, {
      note: newFootnote.value,
      start_position,
      end_position,
      is_ai_generated: isAiGenerated.value,
      text_content: selectedText.value,
    });

    router.reload();
    closeCreateFootnoteMenu();
  }

  async function associateAuthor() {
    await axios.post(`/texts/${props.text.id}/assign-author`, {
      author_id: 1,
    });
    router.reload();
  }

  function goBack() {
    if (document.referrer.includes('texts')) {
      history.back();
    } else {
      router.visit('/texts');
    }
  }

  function closeCreateFootnoteMenu() {
    isWritingNote.value = false;
    isGeneratingExplanation.value = false;
    isAiGenerated.value = false;
    selectedText.value = '';
    newFootnote.value = '';
  }
</script>

<template>
  <Head title="Texts" />
  <div class="flex h-16 items-center justify-between border-b border-gray-200 px-6">
    <div class="flex cursor-pointer select-none items-center gap-2">
      <Icon icon="chevronLeft" class="h-5 w-5 text-textSecondary" />
      <span @click="goBack">Back to Texts</span>
    </div>
    <div class="flex cursor-pointer items-center gap-4">
      <div class="relative" @click="isTranslationMenuOpen = !isTranslationMenuOpen">
        <Button type="ghost" class="flex select-none items-center gap-2">
          <Icon icon="globe" class="h-5 w-5 text-textSecondary" />
          <span class="text-textSecondary">{{ selectedTranslation.language }}</span>
          <Icon
            icon="chevronDown"
            class="h-5 w-5 transform text-textSecondary transition-transform"
            :class="!isTranslationMenuOpen ? 'rotate-180' : ''"
          />
        </Button>
        <div
          v-show="isTranslationMenuOpen"
          class="absolute right-0 top-full z-50 mt-1 w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg"
        >
          <div
            class="text-sem flex items-center justify-between px-4 py-2 text-left hover:bg-gray-100"
            v-for="language in allLanguages"
            :key="language.id"
            @click="selectedTranslation = language"
          >
            {{ language.language }}
            <div class="h-2 w-2 rounded-full bg-primary" v-if="selectedTranslation.id === language.id"></div>
          </div>
          <hr class="my-1" />
          <div
            class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm hover:bg-gray-100"
            style="color: rgb(79, 109, 158)"
          >
            <Icon icon="plus" class="h-4 w-4 text-textSecondary" />
            Request translation
          </div>
        </div>
      </div>
      <Button type="ghost" icon="pen" iconClass="h-[20px] w-[20px]" />
    </div>
  </div>
  <div class="flex h-[calc(100vh-136px)] max-h-[calc(100vh-136px)] w-full">
    <div class="flex w-1/2 flex-col border-r border-gray-200 p-12">
      <p class="mb-2 text-2xl font-medium text-textPrimary">
        {{ text.title }}
      </p>
      <p class="mb-8 text-lg capitalize text-textSecondary">by {{ text.author?.name || 'Unknown author' }}</p>
      <div class="max-w-none select-text overflow-y-auto text-lg leading-relaxed text-textPrimary">
        <p id="content-container" class="whitespace-pre-wrap">
          {{ text.content }}
        </p>
      </div>
    </div>
    <div class="flex w-1/2 flex-col bg-gray-50 p-12">
      <div class="mb-8 flex items-center gap-3">
        <Icon icon="message" class="h-5 w-5 text-primary" />
        <span class="text-xl font-medium text-textPrimary">Footnotes & Analysis</span>
        <span
          class="rounded-full bg-slate-200 px-2 py-0.5 text-sm text-primary"
          v-if="text.text_notes && text.text_notes.length > 0"
        >
          {{ text.text_notes?.length || 0 }}
        </span>
      </div>
      <div v-if="text.text_notes && text.text_notes.length > 0" class="space-y-4">
        <div
          class="rounded-lg border border-gray-200 bg-white p-4"
          v-for="(footNote, index) in text.text_notes"
          :key="footNote.id"
        >
          <div class="flex gap-4">
            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-slate-200">
              <span class="text-sm font-medium text-primary">{{ index + 1 }}</span>
            </div>
            <div>
              <p class="mb-2 text-sm italic text-textSecondary">"{{ footNote.text_content }}"</p>
              <p class="text-sm">{{ footNote.note }}</p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="select-none rounded-lg border-2 border-dashed border-gray-200 p-8 text-center">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-slate-200">
          <Icon icon="message" class="h-8 w-8 text-primary" />
        </div>
        <p class="mb-2 text-lg font-medium">No footnotes yet</p>
        <p class="mb-8 text-sm text-textSecondary">Select any text to add explanations or generate AI analysis</p>
        <div class="mx-auto max-w-sm space-y-6 text-left">
          <div class="flex gap-3">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200">
              <Icon icon="mousePointer" class="h-4 w-4 text-primary" />
            </div>
            <div>
              <p class="mb-1 font-medium text-textPrimary">Select text</p>
              <p class="text-sm text-textSecondary">Click and drag over any portion of text you want to annotate</p>
            </div>
          </div>
          <div class="flex gap-3">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200">
              <Icon icon="squarePen" class="h-4 w-4 text-primary" />
            </div>
            <div>
              <p class="mb-1 font-medium text-textPrimary">Write a note</p>
              <p class="text-sm text-textSecondary">Add your own analysis, explanation, or commentary</p>
            </div>
          </div>
          <div class="flex gap-3">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-slate-200">
              <Icon icon="sparkles" class="h-4 w-4 text-primary" />
            </div>
            <div>
              <p class="mb-1 font-medium text-textPrimary">Or get AI help</p>
              <p class="text-sm text-textSecondary">Let AI generate an analysis that you can review and edit</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div
    v-if="selectedTextPosition && selectedText"
    class="w-max-[400px] fixed z-50 mt-2 -translate-x-1/2 transform rounded-lg border border-gray-200 bg-slate-50 bg-white p-2 shadow-lg"
    :style="{
      top: `${selectedTextPosition.top}px`,
      left: `${selectedTextPosition.left}px`,
    }"
  >
    <div v-if="!isGeneratingExplanation && !isWritingNote" class="flex gap-2">
      <Button type="ghost" icon="squarePen" @click="isWritingNote = true"> Write Note </Button>
      <Button icon="sparkles" @click="generateExplanation">Generate Analysis</Button>
    </div>
    <div v-if="isGeneratingExplanation" class="flex items-center gap-2">
      <Icon icon="loading" class="h-5 w-5 animate-spin text-primary" />
      <p class="text-sm text-textSecondary">Generating analysis...</p>
    </div>
    <div v-if="isWritingNote" class="w-max-[400px] p-2">
      <div class="mb-4 flex items-center justify-between">
        <p class="font-medium text-textPrimary">Add Footnote</p>
        <Button class="!p-1" type="ghost" icon="x" @click="closeCreateFootnoteMenu"></Button>
      </div>
      <p class="line-clamp-3 w-[400px] text-sm italic text-textSecondary">"{{ selectedText }}"</p>
      <hr class="my-4" />

      <textarea
        v-model="newFootnote"
        placeholder="Write your analysis, explanation, or commentary here..."
        rows="4"
        class="w-full resize-none rounded-md border border-gray-200 px-4 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-200"
      />
      <div class="mt-2 flex justify-end gap-2">
        <Button type="ghost" @click="closeCreateFootnoteMenu">Cancel</Button>
        <Button @click="saveFootnote">Save Footnote</Button>
      </div>
    </div>
  </div>
</template>
