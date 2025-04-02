<script setup lang="ts">
import { useAttrs } from 'vue';

defineProps({
  value: {
    type: String,
  },
  htmlFor: {
    type: String,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
});

const attrs = useAttrs();
</script>

<template>
  <label
    :for="htmlFor"
    :class="[
      'block font-medium text-sm mb-1',
      error ? 'text-red-500' : 'text-gray-700 dark:text-gray-300'
    ]"
    v-bind="attrs"
  >
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
    <span v-if="required" class="text-red-500 ml-1">*</span>
  </label>
</template>
