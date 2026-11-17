<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import TagInput from '@/Components/TagInput.vue';
  import { GLOSSARY_TYPES } from '@/glossary';
  import { GlossaryEntry, GlossaryType } from '@/types';
  import { router, useForm } from '@inertiajs/vue3';

  // With `entry` the modal edits it; otherwise it creates one, optionally as an alias of an existing entry
  const props = defineProps<{ bookId: number; entry?: GlossaryEntry; name?: string; entries?: GlossaryEntry[] }>();
  const emit = defineEmits<{ (e: 'close'): void }>();

  const form = useForm({
    alias_of: '' as number | '',
    type: (props.entry?.type ?? 'character') as GlossaryType,
    name: props.entry?.name ?? props.name ?? '',
    aliases: [...(props.entry?.aliases ?? [])],
    description: props.entry?.description ?? '',
    relation: props.entry?.relation ?? '',
    notes: props.entry?.notes ?? '',
  });

  const options = { preserveScroll: true, preserveState: true, onSuccess: () => emit('close') };

  function save() {
    if (form.processing || !form.name.trim()) return;
    if (props.entry) form.patch(`/glossary/${props.entry.id}`, options);
    else form.post(`/books/${props.bookId}/glossary`, options);
  }

  function remove() {
    if (props.entry && confirm(`Remove “${props.entry.name}” from the glossary?`)) {
      router.delete(`/glossary/${props.entry.id}`, options);
    }
  }
</script>

<template>
  <Modal show closeable max-width="lg" @close="emit('close')">
    <div class="flex max-h-[90vh] flex-col">
      <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h1 class="text-lg font-medium text-gray-900">{{ entry ? 'Edit glossary entry' : 'Add to glossary' }}</h1>
        <Button type="ghost" icon="x" icon-class="h-5 w-5" @click="emit('close')" />
      </div>

      <div class="space-y-4 overflow-y-auto p-6">
        <div v-if="!entry && entries?.length">
          <label class="label">Add as</label>
          <select
            v-model="form.alias_of"
            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
          >
            <option value="">A new entry</option>
            <option v-for="existing in entries" :key="existing.id" :value="existing.id">
              Another name for {{ existing.name }}
            </option>
          </select>
        </div>

        <InputText v-model="form.name" label="Name" placeholder="e.g., Sancho Panza" focus />
        <p v-if="form.errors.name" class="-mt-2 text-sm text-error">{{ form.errors.name }}</p>

        <template v-if="!form.alias_of">
          <div>
            <label class="label">Type</label>
            <div class="flex gap-2">
              <button
                v-for="(meta, type) in GLOSSARY_TYPES"
                :key="type"
                type="button"
                class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm transition-colors"
                :class="
                  form.type === type
                    ? 'border-primary bg-primary/10 text-primary'
                    : 'border-gray-200 text-textSecondary hover:border-primary/40'
                "
                @click="form.type = type"
              >
                <Icon :icon="meta.icon" class="h-3.5 w-3.5" />
                {{ meta.label }}
              </button>
            </div>
          </div>

          <div>
            <label class="label">Description</label>
            <textarea
              v-model="form.description"
              rows="2"
              placeholder="Short, shown when you hover the name in the text"
              class="w-full resize-y rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>

          <TagInput v-model="form.aliases" label="Other names" placeholder="Type a name and press Enter" />
          <InputText v-model="form.relation" label="Relation" placeholder="e.g., Don Quixote's squire" />

          <div>
            <label class="label">Notes</label>
            <textarea
              v-model="form.notes"
              rows="4"
              placeholder="Anything else worth remembering"
              class="w-full resize-y rounded-md border border-gray-200 px-3 py-2 text-sm focus:border-transparent focus:outline-none focus:ring-2 focus:ring-primary/30"
            />
          </div>
        </template>
      </div>

      <div class="flex items-center gap-3 border-t border-gray-200 px-6 py-4">
        <Button v-if="entry" type="ghost" icon="trash" text="Remove" class="hover:!text-error" @click="remove" />
        <div class="ml-auto flex gap-3">
          <Button text="Cancel" type="ghost" @click="emit('close')" />
          <Button
            :text="form.alias_of ? 'Add name' : entry ? 'Save changes' : 'Add entry'"
            :disabled="form.processing || !form.name.trim()"
            @click="save"
          />
        </div>
      </div>
    </div>
  </Modal>
</template>
