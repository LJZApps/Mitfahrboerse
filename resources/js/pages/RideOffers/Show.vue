<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { onMounted, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
  rideOffer: {
    type: Object,
    required: true,
  },
});

const map = ref(null);

onMounted(() => {
  // Initialize the map if we have coordinates
  if (props.rideOffer.latitude && props.rideOffer.longitude) {
    // Load OpenStreetMap
    const L = window.L;

    if (!L) {
      console.error('Leaflet library not loaded');
      return;
    }

    // Create the map
    map.value = L.map('map').setView([props.rideOffer.latitude, props.rideOffer.longitude], 15);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map.value);

    // Add marker for the ride offer
    L.marker([props.rideOffer.latitude, props.rideOffer.longitude])
      .addTo(map.value)
      .bindPopup(`
        <div class="popup-content">
          <h3 class="font-semibold">${props.rideOffer.first_name || ''} ${props.rideOffer.last_name}</h3>
          <p>${props.rideOffer.street ? props.rideOffer.street + '<br>' : ''}${props.rideOffer.zip_code} ${props.rideOffer.city}</p>
        </div>
      `);
  }
});

onBeforeUnmount(() => {
  // Clean up map when component is unmounted
  if (map.value) {
    map.value.remove();
    map.value = null;
  }
});
</script>

<template>
  <Head :title="`Mitfahrgelegenheit: ${rideOffer.first_name || ''} ${rideOffer.last_name}`" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Mitfahrgelegenheit Details
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
              <h3 class="text-lg font-semibold mb-4 md:mb-0 text-gray-800 dark:text-gray-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a1 1 0 00.9-.55l4-8a1 1 0 00-.9-1.45H5a1 1 0 00-1 1v1zm1 7a1 1 0 011-1h7a1 1 0 110 2H5a1 1 0 01-1-1z" />
                </svg>
                {{ rideOffer.first_name || '' }} {{ rideOffer.last_name }}
              </h3>

              <div class="flex flex-col sm:flex-row gap-4">
                <Link
                  :href="route('ride-offers.index')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                  </svg>
                  Zurück zur Übersicht
                </Link>

                <Link
                  :href="route('ride-offers.search')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                  Neue Suche
                </Link>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg shadow-md">
                  <h4 class="font-semibold mb-4 text-gray-800 dark:text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                    Kontaktinformationen
                  </h4>

                  <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <p class="flex items-start">
                      <span class="font-medium w-24">Name:</span>
                      <span>{{ rideOffer.first_name || '' }} {{ rideOffer.last_name }}</span>
                    </p>
                    <p v-if="rideOffer.class" class="flex items-start">
                      <span class="font-medium w-24">Klasse:</span>
                      <span>{{ rideOffer.class }}</span>
                    </p>
                    <p class="flex items-start">
                      <span class="font-medium w-24">E-Mail:</span>
                      <span>{{ rideOffer.email }}</span>
                    </p>
                    <p v-if="rideOffer.phone" class="flex items-start">
                      <span class="font-medium w-24">Telefon:</span>
                      <span>{{ rideOffer.phone }}</span>
                    </p>
                  </div>
                </div>

                <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mt-6 shadow-md">
                  <h4 class="font-semibold mb-4 text-gray-800 dark:text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    Adresse
                  </h4>

                  <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <p v-if="rideOffer.street" class="flex items-start">
                      <span class="font-medium w-24">Straße:</span>
                      <span>{{ rideOffer.street }}</span>
                    </p>
                    <p class="flex items-start">
                      <span class="font-medium w-24">PLZ/Ort:</span>
                      <span>{{ rideOffer.zip_code }} {{ rideOffer.city }}</span>
                    </p>
                  </div>
                </div>

                <div class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mt-6 shadow-md">
                  <h4 class="font-semibold mb-4 text-gray-800 dark:text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Gültigkeit
                  </h4>

                  <div class="space-y-3 text-gray-700 dark:text-gray-300">
                    <p class="flex items-start">
                      <span class="font-medium w-24">Gültig ab:</span>
                      <span>{{ rideOffer.valid_from ? new Date(rideOffer.valid_from).toLocaleDateString() : 'Ab sofort' }}</span>
                    </p>
                    <p class="flex items-start">
                      <span class="font-medium w-24">Gültig bis:</span>
                      <span>{{ rideOffer.valid_until ? new Date(rideOffer.valid_until).toLocaleDateString() : 'Unbegrenzt' }}</span>
                    </p>
                  </div>
                </div>

                <div v-if="rideOffer.cost_info" class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mt-6 shadow-md">
                  <h4 class="font-semibold mb-4 text-gray-800 dark:text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                    </svg>
                    Kostenbeteiligung
                  </h4>
                  <p class="text-gray-700 dark:text-gray-300">{{ rideOffer.cost_info }}</p>
                </div>

                <div v-if="rideOffer.additional_info" class="bg-gray-100 dark:bg-gray-900 p-6 rounded-lg mt-6 shadow-md">
                  <h4 class="font-semibold mb-4 text-gray-800 dark:text-gray-200 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Weitere Informationen
                  </h4>
                  <p class="text-gray-700 dark:text-gray-300">{{ rideOffer.additional_info }}</p>
                </div>
              </div>

              <div>
                <div v-if="rideOffer.latitude && rideOffer.longitude" class="h-96 rounded-lg overflow-hidden shadow-md border border-gray-200 dark:border-gray-700">
                  <div id="map" class="h-full w-full"></div>
                </div>
                <div v-else class="h-96 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center shadow-md border border-gray-200 dark:border-gray-700">
                  <div class="text-center p-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Keine Karteninformationen verfügbar</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
.popup-content {
  padding: 8px;
  min-width: 150px;
}

.popup-content h3 {
  font-weight: 600;
  margin-bottom: 4px;
}

.popup-content p {
  margin: 0;
}
</style>
