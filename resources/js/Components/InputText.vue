<script setup lang="ts">
  import { nextTick, onMounted, ref, watch } from 'vue';

  interface InputTextProps {
    placeholder?: string;
    modelValue?: string | number | null;
    disabled?: boolean;
    maxlength?: number;
    label?: string;
    focus?: boolean;
  }

  const props = defineProps<InputTextProps>();
  const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

  const inputRef = ref<HTMLInputElement | null>(null);

  onMounted(() => {
    if (props.focus && inputRef.value) {
      nextTick(() => {
        if (!props.disabled && inputRef.value) {
          inputRef.value.focus();
        }
      });
    }
  });

  function updateValue(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', target.value);
  }
</script>

<template>
  <div>
    <label class="label" for="input-text">
      {{ props.label }}
    </label>
    <input
      ref="inputRef"
      type="text"
      :placeholder="props.placeholder"
      :disabled="props.disabled"
      :maxlength="props.maxlength"
      :value="props.modelValue"
      @input="updateValue"
      class="w-full rounded-md border border-gray-200 px-4 py-2 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-200"
      aria-label="Input Text"
    />
  </div>
</template>
