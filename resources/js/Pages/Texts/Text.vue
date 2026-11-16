<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { FootNote, Text } from '@/types/index';
  import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
  import { Link, router } from '@inertiajs/vue3';
  import EditTextModal from './EditTextModal.vue';

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
  const aiError = ref<string>('');
  const selectedTextPosition = ref<{ top: number; bottom: number; centerX: number } | null>(null);
  const popupPosition = ref({ top: '-9999px', left: '-9999px' });
  const contentContainer = ref<HTMLElement | null>(null);
  const selectionPopup = ref<HTMLElement | null>(null);

  const props = defineProps<textProps>();

  defineOptions({ layout: AuthLayout });

  onMounted(() => document.addEventListener('mouseup', onMouseUp));

  onUnmounted(() => document.removeEventListener('mouseup', onMouseUp));

  function onMouseUp(event: MouseEvent) {
    // Keep the popup while interacting with it or while a note is in progress
    if (selectionPopup.value?.contains(event.target as Node)) return;
    if (isWritingNote.value || isGeneratingExplanation.value || vocabState.value === 'saving') return;

    const selection = window.getSelection();
    const text = selection?.toString().trim();
    const range = selection?.rangeCount ? selection.getRangeAt(0) : null;

    vocabState.value = 'idle';
    savedWord.value = null;
    savedQuote.value = null;

    if (!text || !range || !contentContainer.value?.contains(range.commonAncestorContainer)) {
      selectedText.value = '';
      selectedTextPosition.value = null;
      return;
    }

    const rect = range.getBoundingClientRect();
    selectedText.value = text;
    selectedTextPosition.value = { top: rect.top, bottom: rect.bottom, centerX: rect.left + rect.width / 2 };
  }

  // Keep the popup fully inside the viewport: clamp sideways, flip above the selection if there's no room below
  function placePopup() {
    const el = selectionPopup.value;
    const anchor = selectedTextPosition.value;
    if (!el || !anchor || isWritingNote.value) return;

    const margin = 8;
    const { offsetWidth: width, offsetHeight: height } = el;
    const left = Math.min(Math.max(anchor.centerX - width / 2, margin), window.innerWidth - width - margin);
    let top = anchor.bottom + margin;
    if (top + height > window.innerHeight - margin) top = Math.max(anchor.top - height - margin, margin);

    popupPosition.value = { top: `${top}px`, left: `${left}px` };
  }

  let popupObserver: ResizeObserver | null = null;
  watch(selectionPopup, (el) => {
    popupObserver?.disconnect();
    if (el) {
      popupObserver = new ResizeObserver(placePopup);
      popupObserver.observe(el);
    }
  });
  watch(selectedTextPosition, () => nextTick(placePopup));
  onUnmounted(() => popupObserver?.disconnect());

  const vocabState = ref<'idle' | 'saving' | 'saved'>('idle');
  const savedWord = ref<{ word: string; definition: string | null; alreadySaved: boolean } | null>(null);
  const canAddWord = computed(() => selectedText.value.split(/\s+/).length <= 4);

  // The sentence (or line) around the selection, so the AI defines the word as used here
  function sentenceAround(fragment: string) {
    const content = props.text.content;
    const at = content.indexOf(fragment);
    if (at === -1) return fragment;
    const boundaries = /[.!?\n]/g;
    let start = 0;
    let end = content.length;
    for (const match of content.matchAll(boundaries)) {
      if (match.index! < at) start = match.index! + 1;
      else if (match.index! >= at + fragment.length) {
        end = match.index! + 1;
        break;
      }
    }
    return content.slice(start, end).trim().slice(0, 1000);
  }

  async function addToVocabulary() {
    vocabState.value = 'saving';
    try {
      const { data } = await axios.post('/vocabulary', {
        word: selectedText.value,
        context: sentenceAround(selectedText.value),
        text_id: props.text.id,
      });
      savedWord.value = { ...data.word, alreadySaved: data.alreadySaved };
    } catch {
      savedWord.value = { word: selectedText.value, definition: null, alreadySaved: false };
    }
    vocabState.value = 'saved';
  }

  const savedQuote = ref<'saved' | 'already' | null>(null);

  async function saveQuote() {
    try {
      const { data } = await axios.post('/quotes', { content: selectedText.value, text_id: props.text.id });
      savedQuote.value = data.alreadySaved ? 'already' : 'saved';
    } catch {
      alert("Couldn't save the quote. Please try again.");
    }
  }

  function closePopup() {
    vocabState.value = 'idle';
    savedWord.value = null;
    savedQuote.value = null;
    selectedText.value = '';
    selectedTextPosition.value = null;
  }

  const activeNoteId = ref<number | null>(null);

  // Notes numbered in reading order, matching the markers in the text
  const sortedNotes = computed(() =>
    [...(props.text.text_notes || [])].sort((a, b) => a.start_position - b.start_position),
  );

  // Split the content into plain and annotated chunks
  // ponytail: overlapping notes are skipped in the text (still listed in the panel); nest marks if overlaps become common
  const contentSegments = computed(() => {
    const content = props.text.content;
    const segments: { text: string; noteId?: number; number?: number }[] = [];
    let cursor = 0;
    sortedNotes.value.forEach((note, index) => {
      if (note.start_position < cursor || note.end_position > content.length) return;
      segments.push({ text: content.slice(cursor, note.start_position) });
      segments.push({
        text: content.slice(note.start_position, note.end_position),
        noteId: note.id,
        number: index + 1,
      });
      cursor = note.end_position;
    });
    segments.push({ text: content.slice(cursor) });
    return segments;
  });

  const editingNoteId = ref<number | null>(null);
  const editDraft = ref('');

  function startEditing(note: FootNote) {
    editingNoteId.value = note.id;
    editDraft.value = note.note;
  }

  function saveEdit() {
    if (!editDraft.value.trim()) return;
    router.patch(
      `/notes/${editingNoteId.value}`,
      { note: editDraft.value },
      { preserveScroll: true, preserveState: true, onSuccess: () => (editingNoteId.value = null) },
    );
  }

  function deleteNote(note: FootNote) {
    if (!confirm('Delete this footnote?')) return;
    router.delete(`/notes/${note.id}`, { preserveScroll: true, preserveState: true });
  }

  function focusNote(id: number) {
    activeNoteId.value = id;
    document.getElementById(`note-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  async function generateExplanation() {
    isGeneratingExplanation.value = true;
    aiError.value = '';
    try {
      const response = await axios.post('/chat/analyze-text', { text: selectedText.value });
      newFootnote.value = response.data.footNote;
      isAiGenerated.value = true;
    } catch {
      aiError.value = "Couldn't generate an analysis right now. Write your own note or try again later.";
    }
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

  const isEditingText = ref(false);

  function deleteText() {
    const notes = props.text.text_notes?.length ?? 0;
    const warning = notes ? ` Its ${notes} footnote${notes === 1 ? '' : 's'} will be deleted too.` : '';
    if (confirm(`Delete “${props.text.title}”?${warning}`)) router.delete(`/texts/${props.text.id}`);
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
    aiError.value = '';
    selectedText.value = '';
    newFootnote.value = '';
  }
</script>

<template>
  <Head :title="text.title" />
  <div class="flex h-16 items-center justify-between border-b border-gray-200 px-6">
    <div class="flex cursor-pointer select-none items-center gap-2">
      <Icon icon="chevronLeft" class="h-5 w-5 text-textSecondary" />
      <span @click="goBack">Back to Texts</span>
    </div>
    <div class="flex items-center gap-4">
      <span v-if="text.language" class="flex items-center gap-2 text-sm text-textSecondary">
        <Icon icon="globe" class="h-4 w-4" />
        {{ text.language }}
      </span>
      <Button type="outline" icon="squarePen" text="Edit" @click="isEditingText = true" />
      <Button type="danger" icon="trash" text="Delete" @click="deleteText" />
    </div>
  </div>
  <div class="flex h-[calc(100vh-136px)] max-h-[calc(100vh-136px)] w-full">
    <div class="flex w-1/2 flex-col border-r border-gray-200 p-12">
      <p class="mb-2 text-2xl font-medium text-textPrimary">
        {{ text.title }}
      </p>
      <p class="mb-8 text-lg capitalize text-textSecondary">by {{ text.author?.name || 'Unknown author' }}</p>
      <div class="max-w-none select-text overflow-y-auto text-lg leading-relaxed text-textPrimary">
        <p ref="contentContainer" class="whitespace-pre-wrap">
          <template v-for="(segment, i) in contentSegments" :key="i"
            ><mark
              v-if="segment.noteId"
              :data-n="segment.number"
              class="cursor-pointer rounded-sm bg-transparent text-inherit underline decoration-primary/40 decoration-2 underline-offset-4 transition-colors after:ml-0.5 after:align-super after:text-xs after:font-semibold after:text-primary after:no-underline after:content-[attr(data-n)]"
              :class="activeNoteId === segment.noteId ? 'bg-primary/15 decoration-primary' : 'hover:bg-primary/10'"
              @mouseenter="activeNoteId = segment.noteId"
              @mouseleave="activeNoteId = null"
              @click="focusNote(segment.noteId)"
              >{{ segment.text }}</mark
            ><template v-else>{{ segment.text }}</template></template
          >
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
      <div v-if="sortedNotes.length > 0" class="space-y-4 overflow-y-auto">
        <div
          v-for="(footNote, index) in sortedNotes"
          :id="`note-${footNote.id}`"
          :key="footNote.id"
          class="group rounded-lg border bg-white p-4 transition-all"
          :class="activeNoteId === footNote.id ? 'border-primary shadow-md' : 'border-gray-200'"
          @mouseenter="activeNoteId = footNote.id"
          @mouseleave="activeNoteId = null"
        >
          <div class="flex gap-4">
            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-slate-200">
              <span class="text-sm font-medium text-primary">{{ index + 1 }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="mb-2 flex items-start justify-between gap-2">
                <p class="text-sm italic text-textSecondary">"{{ footNote.text_content }}"</p>
                <div
                  v-if="editingNoteId !== footNote.id"
                  class="flex shrink-0 gap-0.5 opacity-0 transition-opacity focus-within:opacity-100 group-hover:opacity-100"
                >
                  <Button type="ghost" icon="squarePen" class="!p-1.5" title="Edit" @click="startEditing(footNote)" />
                  <Button
                    type="ghost"
                    icon="trash"
                    class="!p-1.5 hover:!text-error"
                    title="Delete"
                    @click="deleteNote(footNote)"
                  />
                </div>
              </div>
              <div v-if="editingNoteId === footNote.id">
                <textarea
                  v-model="editDraft"
                  rows="5"
                  class="w-full resize-y rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
                  @keydown.esc="editingNoteId = null"
                />
                <div class="mt-2 flex justify-end gap-2">
                  <Button type="ghost" text="Cancel" @click="editingNoteId = null" />
                  <Button text="Save" :disabled="!editDraft.trim()" @click="saveEdit" />
                </div>
              </div>
              <p v-else class="whitespace-pre-line text-sm">{{ footNote.note }}</p>
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
    ref="selectionPopup"
    class="fixed z-50 max-w-[calc(100vw-16px)] rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
    :class="isWritingNote ? 'max-h-[90vh] -translate-x-1/2 -translate-y-1/2 transform overflow-y-auto' : ''"
    :style="isWritingNote ? { top: '50%', left: '50%' } : popupPosition"
  >
    <div
      v-if="!isGeneratingExplanation && !isWritingNote && vocabState === 'idle' && !savedQuote"
      class="flex flex-wrap gap-1"
    >
      <Button type="ghost" icon="squarePen" title="Write your own footnote" @click="isWritingNote = true">Note</Button>
      <Button type="ghost" icon="quotes" title="Save as a quote" @click="saveQuote">Quote</Button>
      <Button v-if="canAddWord" type="ghost" icon="bookA" title="Add to vocabulary" @click="addToVocabulary">
        Word
      </Button>
      <Button icon="sparkles" title="Generate an AI analysis" @click="generateExplanation">Analyze</Button>
    </div>
    <div v-if="savedQuote" class="flex items-center gap-4 px-2 py-1 text-sm">
      <span class="flex items-center gap-1 text-success">
        <Icon icon="check" class="h-4 w-4" />
        {{ savedQuote === 'already' ? 'Already in your quotes' : 'Quote saved' }}
      </span>
      <Link href="/quotes" class="font-medium text-primary hover:underline">Open quotes →</Link>
    </div>
    <div v-if="vocabState === 'saving'" class="flex items-center gap-2 px-2 py-1">
      <Icon icon="loading" class="h-5 w-5 animate-spin text-primary" />
      <p class="text-sm text-textSecondary">Looking up the definition...</p>
    </div>
    <div v-if="vocabState === 'saved' && savedWord" class="w-[min(360px,90vw)] p-2">
      <div class="flex items-start justify-between gap-2">
        <p class="font-newsreader text-lg font-semibold text-textPrimary">{{ savedWord.word }}</p>
        <Button class="!p-1" type="ghost" icon="x" @click="closePopup" />
      </div>
      <p v-if="savedWord.definition" class="mt-1 text-sm text-textPrimary">{{ savedWord.definition }}</p>
      <p v-else class="mt-1 text-sm text-error">Saved, but we couldn't get a definition. You can add one later.</p>
      <div class="mt-3 flex items-center justify-between text-xs">
        <span class="flex items-center gap-1 text-success">
          <Icon icon="check" class="h-3.5 w-3.5" />
          {{ savedWord.alreadySaved ? 'Already in your vocabulary' : 'Added to vocabulary' }}
        </span>
        <Link href="/vocabulary" class="font-medium text-primary hover:underline">Open vocabulary →</Link>
      </div>
    </div>
    <div v-if="isGeneratingExplanation" class="flex items-center gap-2">
      <Icon icon="loading" class="h-5 w-5 animate-spin text-primary" />
      <p class="text-sm text-textSecondary">Generating analysis...</p>
    </div>
    <div v-if="isWritingNote" class="w-[min(560px,90vw)] p-2">
      <div class="mb-4 flex items-center justify-between">
        <p class="font-medium text-textPrimary">Add Footnote</p>
        <Button class="!p-1" type="ghost" icon="x" @click="closeCreateFootnoteMenu"></Button>
      </div>
      <p class="line-clamp-3 text-sm italic text-textSecondary">"{{ selectedText }}"</p>
      <hr class="my-4" />
      <p v-if="aiError" class="mb-3 text-sm text-error">{{ aiError }}</p>

      <textarea
        v-model="newFootnote"
        placeholder="Write your analysis, explanation, or commentary here..."
        rows="8"
        class="w-full resize-y rounded-md border border-gray-200 px-4 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-200"
      />
      <div class="mt-2 flex justify-end gap-2">
        <Button type="ghost" @click="closeCreateFootnoteMenu">Cancel</Button>
        <Button @click="saveFootnote">Save Footnote</Button>
      </div>
    </div>
  </div>
  <EditTextModal v-if="isEditingText" :text="text" @close="isEditingText = false" />
</template>
