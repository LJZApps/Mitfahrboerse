<script setup lang="ts">
import { useAttrs } from 'vue';

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  rows: {
    type: [Number, String],
    default: 3,
  },
  error: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['update:modelValue']);
const attrs = useAttrs();
</script>

<template>
  <textarea
    :class="[
      'w-full px-3 py-2 bg-white dark:bg-gray-800 border rounded-md shadow-sm transition-colors',
      'focus:outline-none focus:ring-2 focus:ring-offset-0',
      error
        ? 'border-red-500 focus:border-red-500 focus:ring-red-500/50'
        : 'border-input hover:border-primary/50 focus:border-primary focus:ring-primary/50',
    ]"
    :value="modelValue"
    @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    :rows="rows"
    v-bind="attrs"
  ></textarea>
</template>
