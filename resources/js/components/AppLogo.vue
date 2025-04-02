<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex items-center">
        <!-- Modern logo container with gradient background and shadow -->
        <div class="flex aspect-square size-10 items-center justify-center rounded-xl overflow-hidden gradient-primary modern-shadow relative">
            <!-- Animated background effect -->
            <div class="absolute inset-0 bg-white/10 dark:bg-black/10"></div>
            <!-- Subtle pulse animation -->
            <div class="absolute inset-0 bg-white/5 dark:bg-white/5 animate-pulse"></div>
            <!-- Logo icon -->
            <AppLogoIcon class="size-8 fill-current text-white dark:text-white relative z-10" />
        </div>
        <!-- Modern typography for logo text -->
        <div class="ml-3 grid flex-1 text-left">
            <span class="truncate font-bold text-base tracking-tight leading-none bg-gradient-to-r from-primary to-primary-accent bg-clip-text text-transparent dark:from-primary dark:to-primary-accent">Mitfahrbörse</span>
            <span class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-0.5">BSZ Vogtland</span>
        </div>
    </div>
</template>
