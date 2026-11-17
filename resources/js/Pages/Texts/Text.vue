<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import { ChapterNav, FootNote, GlossaryEntry, Highlight, HighlightColor, Text } from '@/types/index';
  import { GLOSSARY_TYPES } from '@/glossary';
  import GlossaryEntryModal from '../Books/GlossaryEntryModal.vue';
  import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
  import { Link, router, usePage } from '@inertiajs/vue3';
  import EditTextModal from './EditTextModal.vue';

  import axios from 'axios';

  interface textProps {
    text: Text;
    book?: ChapterNav | null;
    glossary?: GlossaryEntry[];
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
    if (selection && !selection.isCollapsed) clampSelectionToPage(selection);
    const text = selection?.toString().trim();
    const range = selection?.rangeCount ? selection.getRangeAt(0) : null;

    vocabState.value = 'idle';
    savedWord.value = null;
    savedQuote.value = null;
    highlightTarget.value = null;

    // A plain click on a highlight opens its menu (glossary names inside one keep their own click)
    // ponytail: a highlight made only of a glossary name can't be clicked; highlight over it to replace it
    const clicked = (event.target as Element).closest?.<HTMLElement>('[data-highlight-id]');
    if (!text && clicked) {
      highlightTarget.value = highlights.value.find((h) => h.id === Number(clicked.dataset.highlightId)) ?? null;
      const rect = clicked.getBoundingClientRect();
      selectedText.value = '';
      selectedTextPosition.value = { top: rect.top, bottom: rect.bottom, centerX: event.clientX };
      return;
    }

    if (!text || !range || !contentContainer.value?.contains(range.commonAncestorContainer)) {
      selectedText.value = '';
      selectedTextPosition.value = null;
      return;
    }

    const rect = range.getBoundingClientRect();
    selectedRange.value = contentRange(range);
    selectedText.value = text;
    selectedTextPosition.value = { top: rect.top, bottom: rect.bottom, centerX: rect.left + rect.width / 2 };
  }

  // Where a DOM selection sits in the content, as character offsets without surrounding whitespace.
  // Taken from the selection itself, so a repeated phrase anchors to the copy that was selected.
  const selectedRange = ref<{ start: number; end: number } | null>(null);
  function contentRange(range: Range) {
    const root = contentContainer.value!;
    const offsetAt = (node: Node, offset: number) => {
      if (!root.contains(node)) return 0;
      const before = document.createRange();
      before.setStart(root, 0);
      before.setEnd(node, offset);
      return before.toString().length;
    };
    const content = props.text.content;
    let start = offsetAt(range.startContainer, range.startOffset);
    let end = offsetAt(range.endContainer, range.endOffset);
    while (start < end && /\s/.test(content[start])) start++;
    while (end > start && /\s/.test(content[end - 1])) end--;
    return { start, end };
  }

  // Highlights: a marker pen, no note needed
  const HIGHLIGHT_COLORS: Record<HighlightColor, string> = {
    yellow: 'bg-yellow-200/80',
    green: 'bg-green-200/80',
    blue: 'bg-sky-200/80',
    pink: 'bg-pink-200/80',
  };
  const highlightTarget = ref<Highlight | null>(null);

  // Changes show instantly and save in the background; on failure the list goes back to what the server has
  const highlights = ref<Highlight[]>([]);
  watch(
    () => props.text.highlights,
    (list) => (highlights.value = [...(list ?? [])]),
    { immediate: true },
  );
  let snapshot: Highlight[] = [];
  function saving(request: Promise<unknown>) {
    request.catch(() => {
      highlights.value = snapshot;
      alert("Couldn't save the highlight. Please try again.");
    });
  }

  let tempId = 0;
  function addHighlight(color: HighlightColor) {
    const range = selectedRange.value;
    if (!range || range.end <= range.start) return;
    snapshot = highlights.value;
    const temp: Highlight = { id: --tempId, start_position: range.start, end_position: range.end, color };
    // Going over existing highlights replaces them (the server does the same)
    highlights.value = [...snapshot.filter((h) => h.end_position <= range.start || h.start_position >= range.end), temp];
    saving(
      axios
        .post(`/texts/${props.text.id}/highlights`, { start_position: range.start, end_position: range.end, color })
        .then(({ data }) => (highlights.value = highlights.value.map((h) => (h.id === temp.id ? data.highlight : h)))),
    );
    window.getSelection()?.removeAllRanges();
    closePopup();
  }

  function recolorHighlight(color: HighlightColor) {
    const target = highlightTarget.value;
    closePopup();
    if (!target || target.id < 0) return;
    snapshot = highlights.value;
    highlights.value = snapshot.map((h) => (h.id === target.id ? { ...h, color } : h));
    saving(axios.patch(`/highlights/${target.id}`, { color }));
  }

  function removeHighlight() {
    const target = highlightTarget.value;
    closePopup();
    if (!target || target.id < 0) return;
    snapshot = highlights.value;
    highlights.value = snapshot.filter((h) => h.id !== target.id);
    saving(axios.delete(`/highlights/${target.id}`));
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
    highlightTarget.value = null;
    selectedText.value = '';
    selectedTextPosition.value = null;
  }

  const activeNoteId = ref<number | null>(null);

  // Notes numbered in reading order, matching the markers in the text
  const sortedNotes = computed(() =>
    [...(props.text.text_notes || [])].sort((a, b) => a.start_position - b.start_position),
  );

  // One regex for every glossary name and alias, longest first so "Sancho Panza" wins over "Sancho"
  const glossaryMatcher = computed(() => {
    const byTerm = new Map<string, GlossaryEntry>();
    for (const entry of props.glossary ?? []) {
      for (const term of [entry.name, ...(entry.aliases ?? [])]) {
        if (term.trim().length > 1 && !byTerm.has(term.trim())) byTerm.set(term.trim(), entry);
      }
    }
    if (!byTerm.size) return null;
    const pattern = [...byTerm.keys()]
      .sort((a, b) => b.length - a.length)
      .map((term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
      .join('|');
    return { regex: new RegExp(`(?<![\\p{L}\\p{N}])(?:${pattern})(?![\\p{L}\\p{N}])`, 'gu'), byTerm };
  });

  type Segment = {
    text: string;
    noteId?: number;
    number?: number;
    entry?: GlossaryEntry;
    highlightId?: number;
    color?: HighlightColor;
  };

  // Plain text, with glossary names cut out as their own segments
  // ponytail: names inside a footnoted passage aren't highlighted; nest them if that's missed
  function plainSegments(text: string): Segment[] {
    const matcher = glossaryMatcher.value;
    if (!matcher || !text) return [{ text }];
    const parts: Segment[] = [];
    let cursor = 0;
    for (const match of text.matchAll(matcher.regex)) {
      parts.push({ text: text.slice(cursor, match.index) });
      parts.push({ text: match[0], entry: matcher.byTerm.get(match[0]) });
      cursor = match.index! + match[0].length;
    }
    parts.push({ text: text.slice(cursor) });
    return parts;
  }

  // Split the content into plain, glossary, highlighted and annotated chunks
  // ponytail: a note or highlight overlapping an earlier one is skipped in the text (notes stay listed in the panel); nest marks if overlaps become common
  const contentSegments = computed(() => {
    const content = props.text.content;
    const marks = [
      ...sortedNotes.value.map((note, index) => ({ start: note.start_position, end: note.end_position, note, number: index + 1 })),
      ...highlights.value.map((highlight) => ({ start: highlight.start_position, end: highlight.end_position, highlight })),
    ].sort((a, b) => a.start - b.start);
    const segments: Segment[] = [];
    let cursor = 0;
    for (const mark of marks) {
      if (mark.start < cursor || mark.end > content.length) continue;
      segments.push(...plainSegments(content.slice(cursor, mark.start)));
      const text = content.slice(mark.start, mark.end);
      if ('note' in mark) segments.push({ text, noteId: mark.note.id, number: mark.number });
      else {
        const { id, color } = mark.highlight;
        segments.push(...plainSegments(text).map((part) => ({ ...part, highlightId: id, color })));
      }
      cursor = mark.end;
    }
    segments.push(...plainSegments(content.slice(cursor)));
    return segments;
  });

  // Glossary: hover a name for its description, click it for everything
  const hoveredEntry = ref<{ entry: GlossaryEntry; top: number; left: number } | null>(null);
  const glossaryModal = ref<{ entry?: GlossaryEntry; name?: string } | null>(null);

  function showEntryCard(entry: GlossaryEntry, event: MouseEvent) {
    const rect = (event.target as HTMLElement).getBoundingClientRect();
    hoveredEntry.value = { entry, top: rect.bottom + 6, left: Math.min(rect.left, window.innerWidth - 300) };
  }

  function addToGlossary() {
    glossaryModal.value = { name: selectedText.value };
    closePopup();
  }

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

  // --- Paged reading: the text flows into CSS columns, one column per page, and turning a page slides them ---
  const PAGE_RATIO = Math.SQRT2; // A4 proportions
  const PAGE_GAP = 48;
  const FONT_MIN = 14;
  const FONT_MAX = 26;

  const stage = ref<HTMLElement | null>(null);
  const pageWindow = ref<HTMLElement | null>(null);
  const flow = ref<HTMLElement | null>(null);
  const pageSize = ref({ width: 0, height: 0 });
  const pageMargin = computed(() => ({ x: pageSize.value.width * 0.1, y: pageSize.value.height * 0.075 }));
  const textArea = computed(() => ({
    width: pageSize.value.width - 2 * pageMargin.value.x,
    height: pageSize.value.height - 2 * pageMargin.value.y,
  }));
  const page = ref(0);
  const pages = ref(1);
  // Turning the page with the keyboard doesn't fire mouseleave on a hovered glossary name
  watch(page, () => (hoveredEntry.value = null));

  const readStoredFont = () => {
    try {
      return Number(localStorage.getItem('lensify.fontSize')) || 18;
    } catch {
      return 18;
    }
  };
  const fontSize = ref(readStoredFont());
  watch(fontSize, (size) => {
    try {
      localStorage.setItem('lensify.fontSize', String(size));
    } catch {
      // Private mode: the size just isn't remembered
    }
  });

  // Page index for a horizontal screen position inside the flowing columns
  function pageAt(left: number) {
    const offset = left - flow.value!.getBoundingClientRect().left;
    return Math.min(Math.max(Math.floor(offset / (textArea.value.width + PAGE_GAP)), 0), pages.value - 1);
  }

  // Character offset of the first word on the current page: the smallest offset whose page is this one.
  // Measured from the layout, so it's right even mid page-turn animation.
  function visibleOffset(): number {
    if (page.value === 0) return 0;
    let low = 0;
    let high = props.text.content.length;
    while (low < high) {
      const mid = Math.floor((low + high) / 2);
      if (pageOfOffset(mid) >= page.value) high = mid;
      else low = mid + 1;
    }
    return low;
  }

  // The page showing a given character offset (whitespace has no reliable box, so measure the next visible char)
  function pageOfOffset(offset: number): number {
    const content = props.text.content;
    while (offset < content.length && /\s/.test(content[offset])) offset++;
    if (offset >= content.length) return pages.value - 1;
    if (!contentContainer.value || offset <= 0) return 0;
    const walker = document.createTreeWalker(contentContainer.value, NodeFilter.SHOW_TEXT);
    // globalThis.Text is the DOM text node; plain `Text` here is the app's text type
    type TextNode = globalThis.Text;
    for (let node = walker.nextNode() as TextNode | null; node; node = walker.nextNode() as TextNode | null) {
      if (offset < node.length) {
        const range = document.createRange();
        range.setStart(node, offset);
        range.setEnd(node, offset + 1);
        return pageAt(range.getBoundingClientRect().left);
      }
      offset -= node.length;
    }
    return pages.value - 1;
  }

  // Fit the largest A4 page in the reading area, then count the columns the text fills.
  // `keep`: stay on the same words, open at the start/end, or resume where the reader last stopped.
  async function paginate(keep: 'position' | 'start' | 'end' | 'saved') {
    if (!stage.value) return;
    const offset = keep === 'position' ? visibleOffset() : (props.text.read_offset ?? 0);

    const height = Math.min(stage.value.clientHeight, stage.value.clientWidth * PAGE_RATIO);
    pageSize.value = { width: height / PAGE_RATIO, height };
    await nextTick();
    if (!flow.value) return;

    const stride = textArea.value.width + PAGE_GAP;
    pages.value = Math.max(1, Math.round((flow.value.scrollWidth + PAGE_GAP) / stride));
    page.value = keep === 'start' ? 0 : keep === 'end' ? pages.value - 1 : pageOfOffset(offset);
  }

  const inertiaPage = usePage();
  const openingMode = () => {
    const at = new URLSearchParams(inertiaPage.url.split('?')[1]).get('at');
    return at === 'end' ? 'end' : at === 'start' ? 'start' : 'saved';
  };

  // Save the reading point shortly after the reader turns a page (never on automatic page changes)
  let saveTimer: ReturnType<typeof setTimeout> | undefined;
  let lastSaved = props.text.read_offset ?? 0;
  function scheduleSave() {
    clearTimeout(saveTimer);
    const textId = props.text.id;
    saveTimer = setTimeout(() => {
      const offset = visibleOffset();
      if (textId !== props.text.id || offset === lastSaved) return;
      lastSaved = offset;
      axios.patch(`/texts/${textId}/progress`, { offset }).catch(() => {});
    }, 800);
  }
  watch(
    () => props.text.id,
    () => (lastSaved = props.text.read_offset ?? 0),
  );
  onUnmounted(() => clearTimeout(saveTimer));

  let stageObserver: ResizeObserver | null = null;
  onMounted(() => {
    // Resizes only re-paginate after the first layout, so they can't race the restore of the saved page
    let restored = false;
    document.fonts.ready.then(() => paginate(openingMode())).then(() => (restored = true));
    stageObserver = new ResizeObserver(() => restored && paginate('position'));
    if (stage.value) stageObserver.observe(stage.value);
    document.addEventListener('keydown', onKeyDown);
  });
  onUnmounted(() => {
    stageObserver?.disconnect();
    document.removeEventListener('keydown', onKeyDown);
  });

  // A new chapter opens at its start (or its end when stepping back); notes or text size keep the reading point
  watch([() => props.text.id, contentSegments, fontSize], ([id], [oldId]) =>
    paginate(id !== oldId ? openingMode() : 'position'),
  );

  function nextPage() {
    if (page.value < pages.value - 1) {
      page.value++;
      scheduleSave();
    } else if (props.book?.next) router.visit(`/texts/${props.book.next.id}?at=start`);
  }

  function prevPage() {
    if (page.value > 0) {
      page.value--;
      scheduleSave();
    } else if (props.book?.prev) router.visit(`/texts/${props.book.prev.id}?at=end`);
  }

  function onKeyDown(event: KeyboardEvent) {
    const target = event.target as HTMLElement;
    if (event.altKey || event.ctrlKey || event.metaKey || target.closest('input, textarea, [contenteditable]')) return;
    if (isWritingNote.value || editingNoteId.value !== null || isEditingText.value || glossaryModal.value) return;
    if (event.key === 'ArrowRight' || event.key === 'PageDown') nextPage();
    else if (event.key === 'ArrowLeft' || event.key === 'PageUp') prevPage();
    else return;
    event.preventDefault();
  }

  function caretAt(x: number, y: number): Range | null {
    if (document.caretRangeFromPoint) return document.caretRangeFromPoint(x, y);
    const position = (document as any).caretPositionFromPoint?.(x, y); // Firefox
    if (!position) return null;
    const range = document.createRange();
    range.setStart(position.offsetNode, position.offset);
    return range;
  }

  // Dragging past the page edge selects into the hidden pages; trim the selection to the visible page
  // ponytail: a passage that runs onto the next page can't be selected whole; lower the text size for it
  function clampSelectionToPage(selection: Selection) {
    const box = pageWindow.value?.getBoundingClientRect();
    if (!box || !selection.rangeCount) return;
    const range = selection.getRangeAt(0);
    if (!pageWindow.value!.contains(range.commonAncestorContainer)) return;
    const first = caretAt(box.left + 1, box.top + 1);
    const last = caretAt(box.right - 1, box.bottom - 1);
    if (first && range.compareBoundaryPoints(Range.START_TO_START, first) < 0) {
      range.setStart(first.startContainer, first.startOffset);
    }
    if (last && range.compareBoundaryPoints(Range.END_TO_END, last) > 0) {
      range.setEnd(last.startContainer, last.startOffset);
    }
    selection.removeAllRanges();
    selection.addRange(range);
  }

  // Turn to the page where a footnote's marker sits
  function showNoteInText(id: number) {
    const mark = flow.value?.querySelector<HTMLElement>(`[data-note-id="${id}"]`);
    if (!mark) return;
    page.value = pageAt(mark.getBoundingClientRect().left);
    scheduleSave();
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
    if (!selectedRange.value) return;

    await axios.post(`/texts/${props.text.id}/footnote`, {
      note: newFootnote.value,
      start_position: selectedRange.value.start,
      end_position: selectedRange.value.end,
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
    <Link v-if="book" :href="`/books/${book.id}`" class="flex min-w-0 select-none items-center gap-2">
      <Icon icon="chevronLeft" class="h-5 w-5 shrink-0 text-textSecondary" />
      <span class="truncate">{{ book.title }}</span>
    </Link>
    <div v-else class="flex cursor-pointer select-none items-center gap-2">
      <Icon icon="chevronLeft" class="h-5 w-5 text-textSecondary" />
      <span @click="goBack">Back to Texts</span>
    </div>
    <div v-if="book" class="flex shrink-0 select-none items-center gap-1 text-sm text-textSecondary">
      <Link
        v-if="book.prev"
        :href="`/texts/${book.prev.id}`"
        :title="book.prev.title"
        class="rounded p-1.5 hover:bg-gray-100 hover:text-primary"
      >
        <Icon icon="chevronLeft" class="h-4 w-4" />
      </Link>
      <span class="px-1 tabular-nums">Chapter {{ book.position }} of {{ book.total }}</span>
      <Link
        v-if="book.next"
        :href="`/texts/${book.next.id}`"
        :title="book.next.title"
        class="rounded p-1.5 hover:bg-gray-100 hover:text-primary"
      >
        <Icon icon="chevronRight" class="h-4 w-4" />
      </Link>
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
    <div class="flex w-1/2 flex-col items-center gap-3 border-r border-gray-200 bg-gray-100 px-6 pb-3 pt-6">
      <div ref="stage" class="flex min-h-0 w-full flex-1 items-center justify-center">
        <div
          class="rounded-sm bg-white shadow-md"
          :style="{
            width: `${pageSize.width}px`,
            height: `${pageSize.height}px`,
            padding: `${pageMargin.y}px ${pageMargin.x}px`,
          }"
        >
          <div ref="pageWindow" class="h-full w-full overflow-hidden" @scroll="pageWindow!.scrollLeft = 0">
            <div
              ref="flow"
              class="h-full select-text font-newsreader leading-relaxed text-textPrimary transition-transform duration-200 ease-out"
              :style="{
                fontSize: `${fontSize}px`,
                columnWidth: `${textArea.width}px`,
                columnGap: `${PAGE_GAP}px`,
                columnFill: 'auto',
                transform: `translateX(-${page * (textArea.width + PAGE_GAP)}px)`,
              }"
            >
              <p class="mb-1 text-[1.4em] font-medium leading-snug">{{ text.title }}</p>
              <p class="mb-[1.5em] text-[0.9em] capitalize text-textSecondary">
                {{ text.author?.name || 'Unknown author' }}
              </p>
              <p ref="contentContainer" class="whitespace-pre-wrap">
                <template v-for="(segment, i) in contentSegments" :key="i"
                  ><mark
                    v-if="segment.noteId"
                    :data-note-id="segment.noteId"
                    :data-n="segment.number"
              class="cursor-pointer rounded-sm bg-transparent text-inherit underline decoration-primary/40 decoration-2 underline-offset-4 transition-colors after:ml-0.5 after:align-super after:text-xs after:font-semibold after:text-primary after:no-underline after:content-[attr(data-n)]"
              :class="activeNoteId === segment.noteId ? 'bg-primary/15 decoration-primary' : 'hover:bg-primary/10'"
              @mouseenter="activeNoteId = segment.noteId"
              @mouseleave="activeNoteId = null"
              @click="focusNote(segment.noteId)"
              >{{ segment.text }}</mark
            ><span
                    v-else-if="segment.entry"
                    class="cursor-pointer underline decoration-amber-500/60 decoration-dotted decoration-2 underline-offset-4 hover:bg-amber-100/60"
                    :class="segment.color && HIGHLIGHT_COLORS[segment.color]"
                    @mouseenter="showEntryCard(segment.entry, $event)"
                    @mouseleave="hoveredEntry = null"
                    @click="
                      glossaryModal = { entry: segment.entry };
                      hoveredEntry = null;
                    "
                    >{{ segment.text }}</span
                  ><span
                    v-else-if="segment.color"
                    :data-highlight-id="segment.highlightId"
                    class="cursor-pointer"
                    :class="HIGHLIGHT_COLORS[segment.color]"
                    >{{ segment.text }}</span
                  ><template v-else>{{ segment.text }}</template></template
          >
              </p>
            </div>
          </div>
        </div>
      </div>
      <div
        class="grid w-full select-none grid-cols-[1fr_auto_1fr] items-center text-sm text-textSecondary"
        :style="{ maxWidth: `${Math.max(pageSize.width, 320)}px` }"
      >
        <span />
        <div class="flex items-center gap-1">
          <button
            class="rounded p-1.5 hover:bg-gray-200 hover:text-primary disabled:opacity-30 disabled:hover:bg-transparent"
            :disabled="page === 0 && !book?.prev"
            :title="page === 0 && book?.prev ? `Previous chapter: ${book.prev.title}` : 'Previous page (←)'"
            @click="prevPage"
          >
            <Icon icon="chevronLeft" class="h-4 w-4" />
          </button>
          <span class="min-w-24 text-center tabular-nums">Page {{ page + 1 }} of {{ pages }}</span>
          <button
            class="rounded p-1.5 hover:bg-gray-200 hover:text-primary disabled:opacity-30 disabled:hover:bg-transparent"
            :disabled="page === pages - 1 && !book?.next"
            :title="page === pages - 1 && book?.next ? `Next chapter: ${book.next.title}` : 'Next page (→)'"
            @click="nextPage"
          >
            <Icon icon="chevronRight" class="h-4 w-4" />
          </button>
        </div>
        <div class="flex items-center justify-end gap-0.5">
          <button
            class="rounded px-2 py-1 text-xs hover:bg-gray-200 hover:text-primary disabled:opacity-30"
            title="Smaller text"
            :disabled="fontSize <= FONT_MIN"
            @click="fontSize--"
          >
            A−
          </button>
          <button
            class="rounded px-2 py-1 text-base hover:bg-gray-200 hover:text-primary disabled:opacity-30"
            title="Larger text"
            :disabled="fontSize >= FONT_MAX"
            @click="fontSize++"
          >
            A+
          </button>
        </div>
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
          @click="showNoteInText(footNote.id)"
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
    v-if="selectedTextPosition && (selectedText || highlightTarget)"
    ref="selectionPopup"
    class="fixed z-50 max-w-[calc(100vw-16px)] rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
    :class="isWritingNote ? 'max-h-[90vh] -translate-x-1/2 -translate-y-1/2 transform overflow-y-auto' : ''"
    :style="isWritingNote ? { top: '50%', left: '50%' } : popupPosition"
  >
    <div v-if="highlightTarget" class="flex items-center gap-1.5 px-1">
      <button
        v-for="(classes, color) in HIGHLIGHT_COLORS"
        :key="color"
        class="h-6 w-6 rounded-full border border-black/10 transition-transform hover:scale-110"
        :class="[classes, highlightTarget.color === color && 'ring-2 ring-primary/60 ring-offset-1']"
        :title="`Make it ${color}`"
        @click="recolorHighlight(color)"
      />
      <Button type="ghost" icon="trash" class="!p-1.5 hover:!text-error" title="Remove highlight" @click="removeHighlight" />
    </div>
    <div
      v-else-if="!isGeneratingExplanation && !isWritingNote && vocabState === 'idle' && !savedQuote"
      class="flex flex-wrap items-center gap-1"
    >
      <div class="flex items-center gap-1 border-r border-gray-200 pl-1 pr-2" title="Highlight">
        <button
          v-for="(classes, color) in HIGHLIGHT_COLORS"
          :key="color"
          class="h-5 w-5 rounded-full border border-black/10 transition-transform hover:scale-110"
          :class="classes"
          :title="`Highlight in ${color}`"
          @click="addHighlight(color)"
        />
      </div>
      <Button type="ghost" icon="squarePen" title="Write your own footnote" @click="isWritingNote = true">Note</Button>
      <Button type="ghost" icon="quotes" title="Save as a quote" @click="saveQuote">Quote</Button>
      <Button v-if="canAddWord" type="ghost" icon="bookA" title="Add to vocabulary" @click="addToVocabulary">
        Word
      </Button>
      <Button v-if="book && selectedText.length <= 80" type="ghost" icon="author" title="Add to the book's glossary" @click="addToGlossary">
        Glossary
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
  <div
    v-if="hoveredEntry"
    class="pointer-events-none fixed z-40 w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
    :style="{ top: `${hoveredEntry.top}px`, left: `${hoveredEntry.left}px` }"
  >
    <p class="flex items-center gap-1.5 font-medium text-textPrimary">
      <Icon :icon="GLOSSARY_TYPES[hoveredEntry.entry.type].icon" class="h-4 w-4 text-amber-600" />
      {{ hoveredEntry.entry.name }}
    </p>
    <p v-if="hoveredEntry.entry.description" class="mt-1 text-sm text-textSecondary">
      {{ hoveredEntry.entry.description }}
    </p>
    <p v-else class="mt-1 text-sm italic text-textSecondary">No description yet. Click to add one.</p>
  </div>
  <GlossaryEntryModal
    v-if="glossaryModal && book"
    :book-id="book.id"
    :entry="glossaryModal.entry"
    :name="glossaryModal.name"
    :entries="glossary"
    @close="glossaryModal = null"
  />
</template>
