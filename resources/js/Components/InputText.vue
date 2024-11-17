<script setup lang="ts">
  interface InputTextProps {
    placeholder?: string;
    modelValue?: string | number | null;
    disabled?: boolean;
    maxlength?: number;
    label?: string;
  }

  const props = defineProps<InputTextProps>();
  const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

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
