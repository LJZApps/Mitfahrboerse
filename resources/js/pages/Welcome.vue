<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Car, Search, Map, Users, ArrowRight, Edit } from 'lucide-vue-next';

// For edit code form
const editCode = ref('');
const submitEditCode = () => {
    if (editCode.value) {
        router.visit(route('ride-offers.edit', { editCode: editCode.value }));
    }
};

// Animation for the hero section
const isLoaded = ref(false);
onMounted(() => {
    setTimeout(() => {
        isLoaded.value = true;
    }, 100);
});
</script>

<template>
    <Head title="Mitfahrbörse - BSZ Vogtland">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    </Head>
    <div class="min-h-screen bg-gradient-to-b from-green-50 to-primary/5 text-gray-800 dark:bg-gradient-to-b dark:from-gray-900 dark:to-gray-800 dark:text-white">
        <!-- Hero Section -->
        <div class="relative overflow-hidden bg-cover bg-center py-20"
             style="background-image: url('https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80'); height: 500px;">
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>
            <div class="relative container mx-auto px-6 flex flex-col items-center justify-center h-full text-center">
                <div :class="['transition-all duration-1000 transform', isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0']">
                    <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">Mitfahrbörse BSZ Vogtland</h1>
                    <p class="text-xl md:text-2xl text-white max-w-3xl mx-auto mb-10">
                        Gemeinsam fahren, Umwelt schonen, Kosten sparen. Finden Sie Mitfahrgelegenheiten zum BSZ Vogtland in Rodewisch oder bieten Sie selbst eine an.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            :href="route('ride-offers.create')"
                            class="px-8 py-4 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 transition-colors flex items-center justify-center"
                        >
                            <Car class="mr-2 h-5 w-5" />
                            Fahrt anbieten
                        </Link>
                        <Link
                            :href="route('ride-offers.search')"
                            class="px-8 py-4 bg-primary text-white font-bold rounded-lg shadow-lg hover:bg-primary-accent transition-colors flex items-center justify-center"
                        >
                            <Search class="mr-2 h-5 w-5" />
                            Fahrt finden
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-6 py-16">
            <main class="flex flex-col">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-4">Einfach und schnell zur Mitfahrgelegenheit</h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                        Unsere Plattform macht es einfach, Fahrgemeinschaften zum BSZ Vogtland zu finden oder anzubieten.
                    </p>
                </div>

                <!-- Feature Cards -->
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Offer a Ride Card -->
                    <div class="flex flex-col rounded-lg bg-white p-6 shadow-lg transition-all hover:shadow-xl dark:bg-gray-800">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-300">
                            <Car class="h-8 w-8" />
                        </div>
                        <h2 class="mb-2 text-xl font-semibold">Mitfahrgelegenheit anbieten</h2>
                        <p class="mb-4 flex-grow text-gray-600 dark:text-gray-300">
                            Bieten Sie eine Mitfahrgelegenheit an und helfen Sie anderen, zum BSZ Vogtland zu kommen.
                        </p>
                        <Link
                            :href="route('ride-offers.create')"
                            class="mt-auto inline-flex items-center justify-center rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-700 dark:hover:bg-green-600"
                        >
                            Jetzt anbieten
                        </Link>
                    </div>

                    <!-- Search for Rides Card -->
                    <div class="flex flex-col rounded-lg bg-white p-6 shadow-lg transition-all hover:shadow-xl dark:bg-gray-800">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary">
                            <Search class="h-8 w-8" />
                        </div>
                        <h2 class="mb-2 text-xl font-semibold">Mitfahrgelegenheit suchen</h2>
                        <p class="mb-4 flex-grow text-gray-600 dark:text-gray-300">
                            Finden Sie eine passende Mitfahrgelegenheit zum BSZ Vogtland in Rodewisch.
                        </p>
                        <Link
                            :href="route('ride-offers.search')"
                            class="mt-auto inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-white hover:bg-primary-accent focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:bg-primary dark:hover:bg-primary-accent"
                        >
                            Jetzt suchen
                        </Link>
                    </div>

                    <!-- View All Rides Card -->
                    <div class="flex flex-col rounded-lg bg-white p-6 shadow-lg transition-all hover:shadow-xl dark:bg-gray-800">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-300">
                            <Map class="h-8 w-8" />
                        </div>
                        <h2 class="mb-2 text-xl font-semibold">Kartenübersicht</h2>
                        <p class="mb-4 flex-grow text-gray-600 dark:text-gray-300">
                            Sehen Sie alle verfügbaren Mitfahrgelegenheiten auf einer interaktiven Karte.
                        </p>
                        <Link
                            :href="route('ride-offers.overview')"
                            class="mt-auto inline-flex items-center justify-center rounded-md bg-purple-600 px-4 py-2 text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-purple-700 dark:hover:bg-purple-600"
                        >
                            Karte anzeigen
                        </Link>
                    </div>

                    <!-- Edit Ride Card -->
                    <div class="flex flex-col rounded-lg bg-white p-6 shadow-lg transition-all hover:shadow-xl dark:bg-gray-800">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900 dark:text-amber-300">
                            <Edit class="h-8 w-8" />
                        </div>
                        <h2 class="mb-2 text-xl font-semibold">Mitfahrgelegenheit bearbeiten</h2>
                        <p class="mb-4 flex-grow text-gray-600 dark:text-gray-300">
                            Haben Sie bereits eine Mitfahrgelegenheit erstellt? Geben Sie Ihren Bearbeitungscode ein.
                        </p>
                        <form @submit.prevent="submitEditCode" class="mt-auto">
                            <input
                                type="text"
                                v-model="editCode"
                                placeholder="Bearbeitungscode eingeben"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-amber-500 dark:focus:border-amber-400 focus:ring focus:ring-amber-200 dark:focus:ring-amber-800 focus:ring-opacity-50 mb-2"
                            />
                            <button
                                type="submit"
                                class="w-full inline-flex items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-700 dark:hover:bg-amber-600"
                            >
                                Bearbeiten
                            </button>
                        </form>
                    </div>
                </div>

                <!-- How it works section -->
                <div class="mt-20 mb-20 bg-gray-50 dark:bg-gray-900 rounded-xl p-10">
                    <h2 class="text-3xl font-bold text-center text-gray-800 dark:text-white mb-12">So funktioniert's</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 relative">
                            <div class="absolute -top-5 -left-5 w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center text-xl font-bold">1</div>
                            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Registrieren Sie Ihre Fahrt</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Geben Sie Ihre Route, Kontaktdaten und Verfügbarkeit ein. Sie erhalten einen persönlichen Code zur Verwaltung Ihres Angebots.
                            </p>
                            <Link
                                :href="route('ride-offers.create')"
                                class="text-green-600 dark:text-green-400 font-medium hover:underline inline-flex items-center"
                            >
                                <Car class="mr-2 h-4 w-4" />
                                Fahrt anbieten
                            </Link>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 relative">
                            <div class="absolute -top-5 -left-5 w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-xl font-bold">2</div>
                            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Finden Sie passende Fahrten</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Suchen Sie nach Mitfahrgelegenheiten in Ihrer Nähe. Filtern Sie nach Postleitzahl oder nutzen Sie die Umkreissuche.
                            </p>
                            <Link
                                :href="route('ride-offers.search')"
                                class="text-primary dark:text-primary-accent font-medium hover:underline inline-flex items-center"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                Fahrt suchen
                            </Link>
                        </div>

                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-8 relative">
                            <div class="absolute -top-5 -left-5 w-12 h-12 rounded-full bg-purple-500 text-white flex items-center justify-center text-xl font-bold">3</div>
                            <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Kontakt aufnehmen</h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-4">
                                Haben Sie eine passende Mitfahrgelegenheit gefunden? Nehmen Sie Kontakt mit dem Anbieter auf und vereinbaren Sie die Details.
                            </p>
                            <Link
                                :href="route('ride-offers.overview')"
                                class="text-purple-600 dark:text-purple-400 font-medium hover:underline inline-flex items-center"
                            >
                                <Users class="mr-2 h-4 w-4" />
                                Alle Angebote ansehen
                            </Link>
                        </div>
                    </div>

                    <div class="mt-12 flex justify-center">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 max-w-2xl">
                            <h3 class="text-xl font-semibold mb-4 text-center text-gray-800 dark:text-white">Ihre Vorteile auf einen Blick</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <ArrowRight class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span class="text-gray-600 dark:text-gray-300">Kostenlose Nutzung ohne Registrierung</span>
                                </li>
                                <li class="flex items-start">
                                    <ArrowRight class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span class="text-gray-600 dark:text-gray-300">Einfache Verwaltung Ihrer Angebote mit einem persönlichen Code</span>
                                </li>
                                <li class="flex items-start">
                                    <ArrowRight class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span class="text-gray-600 dark:text-gray-300">Umkreissuche für passende Mitfahrgelegenheiten</span>
                                </li>
                                <li class="flex items-start">
                                    <ArrowRight class="h-5 w-5 text-green-500 mr-2 mt-0.5" />
                                    <span class="text-gray-600 dark:text-gray-300">Interaktive Kartenansicht aller Angebote</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Why Carpooling Section -->
                <div class="mt-16 text-center">
                    <h2 class="mb-4 text-2xl font-semibold">Warum Fahrgemeinschaften?</h2>
                    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                        <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600 mx-auto dark:bg-green-900 dark:text-green-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-medium">Umweltfreundlich</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Reduzieren Sie CO2-Emissionen durch gemeinsame Fahrten.
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary mx-auto dark:bg-primary/20 dark:text-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-medium">Kostengünstig</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Teilen Sie die Fahrtkosten und sparen Sie Geld.
                            </p>
                        </div>

                        <div class="rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 text-purple-600 mx-auto dark:bg-purple-900 dark:text-purple-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h3 class="mb-2 text-lg font-medium">Gemeinschaft</h3>
                            <p class="text-gray-600 dark:text-gray-300">
                                Lernen Sie Ihre Mitschüler und Kollegen besser kennen.
                            </p>
                        </div>
                    </div>
                </div>

                <footer class="mt-16 border-t border-gray-200 pt-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    <p>© {{ new Date().getFullYear() }} Mitfahrbörse BSZ Vogtland. Alle Rechte vorbehalten.</p>
                </footer>
            </main>
        </div>
    </div>
</template>
