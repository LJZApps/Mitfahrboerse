<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import type { BreadcrumbItem, NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Menu, Search, Car, Map } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

const isCurrentRoute = computed(() => (url: string) => page.url === url);

const activeItemStyles = computed(
    () => (url: string) => (isCurrentRoute.value(url) ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : ''),
);

const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: route('home'),
        icon: LayoutGrid,
    },
    {
        title: 'Kartenübersicht',
        href: route('ride-offers.overview'),
        icon: Map,
    },
];
</script>

<template>
    <div>
        <!-- Modern header with subtle gradient and glass effect -->
        <div class="relative border-b border-sidebar-border/80 bg-gradient-to-r from-background via-background to-background">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-primary/5 blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 h-40 w-40 rounded-full bg-secondary/5 blur-3xl"></div>
            </div>

            <div class="relative mx-auto flex h-16 items-center px-4 md:max-w-7xl z-10">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9 hover-scale">
                                <Menu class="h-5 w-5 text-primary" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6 gradient-card">
                            <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogo />
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav class="-mx-3 space-y-2">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200 hover:bg-primary/10 hover:text-primary"
                                        :class="[
                                            activeItemStyles(item.href),
                                            isCurrentRoute(item.href) ? 'bg-primary/10 text-primary shadow-sm' : ''
                                        ]"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <!-- Mobile menu footer area -->
                                <div class="flex flex-col space-y-4">
                                    <!-- Add any mobile-specific footer content here if needed -->
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link href="/" class="flex items-center gap-x-2 hover-scale">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-3">
                            <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index" class="relative flex h-full items-center">
                                <Link :href="item.href">
                                    <NavigationMenuLink
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            'h-10 cursor-pointer rounded-lg px-4 py-3 text-sm font-medium transition-all duration-200 hover:bg-primary/10 hover:text-primary',
                                            isCurrentRoute(item.href) ? 'text-primary font-medium' : ''
                                        ]"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                        {{ item.title }}
                                    </NavigationMenuLink>
                                </Link>
                                <div
                                    v-if="isCurrentRoute(item.href)"
                                    class="absolute bottom-0 left-0 h-1 w-full translate-y-px rounded-t-full bg-gradient-to-r from-primary via-primary to-primary-accent"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-3">
                    <div class="relative flex items-center space-x-2">
                        <Link :href="route('ride-offers.search')">
                            <Button variant="ghost" size="icon" class="group h-10 w-10 cursor-pointer rounded-full hover:bg-primary/10 transition-all duration-300">
                                <Search class="size-5 text-primary opacity-80 group-hover:opacity-100" />
                            </Button>
                        </Link>

                        <!-- Create ride offer button -->
                        <Link :href="route('ride-offers.create')" class="hidden md:block">
                            <Button class="gradient-primary button-glow h-10 px-4 rounded-full shadow-md hover:shadow-lg transition-all duration-300">
                                <Car class="mr-2 h-4 w-4" />
                                <span>Anbieten</span>
                            </Button>
                        </Link>

                        <!-- Desktop menu additional actions can be added here if needed -->
                        <div class="hidden space-x-1 lg:flex">
                            <!-- Future actions can be added here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
