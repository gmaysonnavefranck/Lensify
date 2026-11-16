<script setup lang="ts">
  import Button from '@/Components/Button.vue';
  import Icon from '@/Components/Icon.vue';
  import InputText from '@/Components/InputText.vue';
  import Modal from '@/Components/Modal.vue';
  import { Text } from '@/types';
  import { router } from '@inertiajs/vue3';
  import { ref } from 'vue';

  const props = defineProps<{ text: Text }>();
  const emit = defineEmits<{ (e: 'close'): void }>();

  const form = ref({
    title: props.text.title,
    author_name: props.text.author?.name ?? '',
    year: props.text.year ?? '',
    type: props.text.type ?? '',
    language: props.text.language ?? '',
  });

  function save() {
    router.patch(`/texts/${props.text.id}`, form.value, { preserveScroll: true, onSuccess: () => emit('close') });
  }
</script>

<template>
  <Modal show @close="emit('close')" max-width="lg">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
      <h1 class="text-lg font-medium text-gray-900">Edit Text</h1>
      <Button type="ghost" icon="x" icon-class="h-5 w-5" @click="emit('close')" />
    </div>
    <div class="space-y-4 p-6">
      <InputText v-model="form.title" label="Title" placeholder="Title" />
      <InputText v-model="form.author_name" label="Author" placeholder="Leave empty if unknown" />
      <div class="grid grid-cols-2 gap-4">
        <InputText v-model="form.type" label="Type" placeholder="e.g., poem" />
        <InputText v-model="form.year" label="Year of publication" placeholder="e.g., 1891" />
      </div>
      <InputText v-model="form.language" label="Language" placeholder="e.g., English" />
      <p class="flex gap-2 text-xs text-secondary">
        <Icon icon="info" class="h-4 w-4 shrink-0" />
        The text itself can't be edited, since footnotes are attached to its exact wording.
      </p>
    </div>
    <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
      <Button text="Cancel" type="ghost" @click="emit('close')" />
      <Button text="Save changes" :disabled="!form.title.trim()" @click="save" />
    </div>
  </Modal>
</template>
