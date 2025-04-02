<script setup lang="ts">
defineProps({
  type: {
    type: String,
    default: 'submit',
  },
  variant: {
    type: String,
    default: 'primary', // primary, secondary, success, danger
    validator: (value) => ['primary', 'secondary', 'success', 'danger'].includes(value),
  },
  size: {
    type: String,
    default: 'md', // sm, md, lg
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});
</script>

<template>
  <button
    :type="type"
    :class="[
      'inline-flex items-center justify-center border border-transparent rounded-md font-semibold text-center transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2',
      // Size variants
      {
        'px-2.5 py-1.5 text-xs': size === 'sm',
        'px-4 py-2 text-sm': size === 'md',
        'px-6 py-3 text-base': size === 'lg',
      },
      // Color variants
      {
        'bg-primary text-primary-foreground hover:bg-primary/90 focus:ring-primary': variant === 'primary',
        'bg-secondary text-secondary-foreground hover:bg-secondary/80 focus:ring-secondary': variant === 'secondary',
        'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500': variant === 'success',
        'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500': variant === 'danger',
      }
    ]"
  >
    <slot />
  </button>
</template>
