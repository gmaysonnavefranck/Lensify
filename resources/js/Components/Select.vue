<script setup lang="ts">
  import { watch, ref, onMounted } from 'vue';
  import Multiselect from 'vue-multiselect';

  interface SelectProps {
    modelValue: string;
    options: string[];
    multiple?: boolean;
    label: string;
    placeholder: string;
    isLoading: boolean;
    taggable?: boolean;
  }

  const value = ref('');
  const props = withDefaults(defineProps<SelectProps>(), {
    multiple: false,
    placeholder: 'Select an option',
    isLoading: false,
    taggable: false,
  });
  const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>();

  onMounted(() => {
    value.value = props.modelValue;
  });

  watch(
    () => value,
    (newVal) => {
      emit('update:modelValue', newVal.value);
    },
  );
</script>

<template>
  <div>
    <label class="label" for="input-text">
      {{ label }}
    </label>
    <Multiselect
      v-model="value"
      :multiple="multiple"
      :options="options"
      :loading="isLoading"
      :placeholder="placeholder"
      :taggable="taggable"
      @update:modelValue="(value) => emit('update:modelValue', value)"
    ></Multiselect>
  </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>

<style>
  .multiselect__tags {
    @apply rounded-md border border-gray-200 px-4 py-2 pr-8;
  }
  .multiselect__tags:has(.multiselect__input:focus) {
    @apply rounded-md border-transparent ring-2 ring-blue-200;
  }
  .multiselect__placeholder {
    @apply m-0 p-0 font-inter text-base text-gray-500;
  }
  .multiselect__input {
    @apply m-0 border-0 p-0 py-0.5 text-textPrimary focus:ring-0;
  }
  .multiselect__content-wrapper {
    @apply mt-1 rounded-md border border-gray-200 shadow-md;
  }

  .multiselect__option--highlight {
    @apply bg-primary text-white;
  }

  .multiselect__tags-wrap {
    @apply flex p-0;
  }
  .multiselect__tag {
    @apply m-0 mr-1;
  }
</style>
