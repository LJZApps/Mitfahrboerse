<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
  rideOffer: {
    type: Object,
    required: true,
  },
  editCode: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <Head title="Bestätigung" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Bestätigung
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="text-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>

              <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-200">
                Ihre Mitfahrgelegenheit wurde erfolgreich gespeichert!
              </h3>

              <p class="mb-6 text-gray-600 dark:text-gray-400">
                Vielen Dank für Ihren Beitrag zur Umwelt und Verkehrsentlastung.
              </p>

              <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mb-6 max-w-md mx-auto shadow-md">
                <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Ihr persönlicher Bearbeitungscode:</h4>
                <div class="bg-white dark:bg-gray-800 p-3 rounded border border-gray-300 dark:border-gray-700 text-xl font-mono text-gray-800 dark:text-gray-200">
                  {{ editCode }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                  Bitte bewahren Sie diesen Code auf. Sie benötigen ihn, um Ihre Mitfahrgelegenheit später zu bearbeiten oder zu löschen.
                </p>
              </div>

              <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mb-6 max-w-md mx-auto shadow-md">
                <h4 class="font-semibold mb-2 text-gray-800 dark:text-gray-200">Ihre Angaben:</h4>
                <div class="text-left text-gray-700 dark:text-gray-300 space-y-2">
                  <p><span class="font-medium">Name:</span> {{ rideOffer.first_name || '' }} {{ rideOffer.last_name }}</p>
                  <p><span class="font-medium">Adresse:</span> {{ rideOffer.street || '' }}, {{ rideOffer.zip_code }} {{ rideOffer.city }}</p>
                  <p><span class="font-medium">E-Mail:</span> {{ rideOffer.email }}</p>
                  <p v-if="rideOffer.phone"><span class="font-medium">Telefon:</span> {{ rideOffer.phone }}</p>
                  <p v-if="rideOffer.class"><span class="font-medium">Klasse:</span> {{ rideOffer.class }}</p>
                  <p>
                    <span class="font-medium">Gültig:</span>
                    {{ rideOffer.valid_from ? new Date(rideOffer.valid_from).toLocaleDateString() : 'Ab sofort' }}
                    bis
                    {{ rideOffer.valid_until ? new Date(rideOffer.valid_until).toLocaleDateString() : 'Unbegrenzt' }}
                  </p>
                </div>
              </div>

              <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link
                  :href="route('ride-offers.edit', { editCode })"
                  class="inline-flex items-center justify-center px-4 py-2 bg-primary hover:bg-primary-accent text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                  </svg>
                  Bearbeiten
                </Link>

                <Link
                  :href="route('ride-offers.overview')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                  </svg>
                  Alle Mitfahrgelegenheiten anzeigen
                </Link>

                <Link
                  :href="route('ride-offers.search')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                  Mitfahrgelegenheit suchen
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
