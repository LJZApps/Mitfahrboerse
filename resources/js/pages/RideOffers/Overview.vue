<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
  rideOffers: {
    type: Array,
    default: () => [],
  },
});

const map = ref(null);
const markerLayer = ref(null);
const markers = ref([]);
const selectedOffer = ref(null);
const selectRideOfferHandler = ref(null);
const mapError = ref(false);
const mapLoading = ref(true);

const selectOffer = (offer) => {
  selectedOffer.value = offer;
};

onMounted(() => {
  // Initialize the map
  try {
    initializeMap();

    // Define the event handler function
    selectRideOfferHandler.value = (event) => {
      const offerId = event.detail;
      const offer = props.rideOffers.find(o => o.id === offerId);
      if (offer) {
        selectOffer(offer);
      }
    };

    // Add the event listener
    document.addEventListener('select-ride-offer', selectRideOfferHandler.value);
  } catch (error) {
    console.error('Error initializing map:', error);
  }
});

onBeforeUnmount(() => {
  // Remove the event listener
  if (selectRideOfferHandler.value) {
    document.removeEventListener('select-ride-offer', selectRideOfferHandler.value);
  }

  // Clean up map, marker layer, and markers
  if (markerLayer.value) {
    markerLayer.value.clearLayers();
    markerLayer.value = null;
  }

  if (map.value) {
    map.value.remove();
    map.value = null;
  }

  markers.value = [];
});

// Function to initialize the map
const initializeMap = () => {
  // Ensure we're in a browser environment
  if (typeof window === 'undefined') {
    console.error('Error: Not in browser environment');
    mapError.value = true;
    mapLoading.value = false;
    return;
  }

  // Try to initialize the map with a retry mechanism
  const attemptInitialize = (retryCount = 0, maxRetries = 3) => {
    // Load OpenStreetMap
    const L = window.L;
    if (!L) {
      if (retryCount < maxRetries) {
        // Wait a bit longer before retrying
        console.log(`Leaflet not loaded yet, retrying (${retryCount + 1}/${maxRetries})...`);
        setTimeout(() => attemptInitialize(retryCount + 1, maxRetries), 500);
        return;
      }
      console.error('Error: Leaflet library not loaded after retries');
      mapError.value = true;
      mapLoading.value = false;
      return;
    }

    // Wait for the DOM to be ready
    setTimeout(() => {
      try {
        // Check if map container exists
        const mapContainer = document.getElementById('map');

        if (!mapContainer) {
          // We shouldn't need to create the container dynamically as it's in the template
          console.error('Error: Map container not found');
          mapError.value = true;
          mapLoading.value = false;
          return;
        }

        // Create map if it doesn't exist
        if (!map.value) {
          // Find center of all offers or use default
          let centerLat = 51.1657; // Default to Germany center
          let centerLng = 10.4515;

          const offersWithCoords = props.rideOffers.filter(
            offer => offer.latitude && offer.longitude
          );

          if (offersWithCoords.length > 0) {
            // Calculate average coordinates
            let totalLat = 0;
            let totalLng = 0;

            offersWithCoords.forEach(offer => {
              totalLat += parseFloat(offer.latitude);
              totalLng += parseFloat(offer.longitude);
            });

            centerLat = totalLat / offersWithCoords.length;
            centerLng = totalLng / offersWithCoords.length;
          }

          // Create the map
          map.value = L.map('map').setView([centerLat, centerLng], 10);

          // Add OpenStreetMap tiles
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          }).addTo(map.value);

          // Create a layer group for markers
          markerLayer.value = L.layerGroup().addTo(map.value);

          // Add markers for each ride offer with coordinates
          if (offersWithCoords.length > 0) {
            offersWithCoords.forEach(offer => {
              try {
                const marker = L.marker([offer.latitude, offer.longitude])
                  .bindPopup(`
                    <div class="popup-content">
                      <h3 class="font-semibold">${offer.first_name || ''} ${offer.last_name}</h3>
                      <p>${offer.street ? offer.street + '<br>' : ''}${offer.zip_code} ${offer.city}</p>
                      <button class="popup-button" onclick="document.dispatchEvent(new CustomEvent('select-ride-offer', {detail: ${offer.id}}))">
                        Details anzeigen
                      </button>
                    </div>
                  `);

                // Add the marker to the layer group
                markerLayer.value.addLayer(marker);

                // Store the marker with a reference to the offer ID
                markers.value.push({
                  offerId: offer.id,
                  leafletMarker: marker
                });
              } catch (error) {
                // Silently fail for individual markers
                console.error('Error creating marker:', error);
              }
            });
          } else if (map.value) {
            // Add a message to the map if no offers with coordinates
            L.popup()
              .setLatLng([centerLat, centerLng])
              .setContent('Keine Mitfahrgelegenheiten mit Koordinaten gefunden.')
              .openOn(map.value);
          }
        }

        mapLoading.value = false;
      } catch (error) {
        console.error('Error initializing map:', error);
        if (retryCount < maxRetries) {
          // Wait a bit longer before retrying
          setTimeout(() => attemptInitialize(retryCount + 1, maxRetries), 500);
        } else {
          mapError.value = true;
          mapLoading.value = false;
        }
      }
    }, 100);
  };

  // Start the initialization process
  attemptInitialize();
};
</script>

<template>
  <Head title="Mitfahrgelegenheiten Übersicht" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Mitfahrgelegenheiten Übersicht
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6">
              <h3 class="text-lg font-semibold mb-4 md:mb-0 text-gray-800 dark:text-gray-200 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                Alle Mitfahrgelegenheiten auf der Karte
              </h3>
              <div class="flex flex-col sm:flex-row gap-4">
                <Link
                  :href="route('ride-offers.search')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                  Suchen
                </Link>
                <Link
                  :href="route('ride-offers.create')"
                  class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  Anbieten
                </Link>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- Map -->
              <div class="md:col-span-2">
                <div class="relative">
                  <!-- Loading overlay -->
                  <div v-if="mapLoading" class="absolute inset-0 bg-gray-100 dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
                    <div class="text-center">
                      <svg class="animate-spin h-10 w-10 text-blue-500 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <p class="text-gray-700 dark:text-gray-300">Karte wird geladen...</p>
                    </div>
                  </div>

                  <!-- Error message -->
                  <div v-if="mapError" class="absolute inset-0 bg-red-50 dark:bg-red-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center z-10 rounded-lg">
                    <div class="text-center p-6">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      <h3 class="text-lg font-semibold mb-2 text-red-600 dark:text-red-400">Fehler beim Laden der Karte</h3>
                      <p class="text-gray-700 dark:text-gray-300">Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.</p>
                    </div>
                  </div>

                  <!-- Map container -->
                  <div id="map" class="h-[600px] w-full rounded-lg shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden"></div>

                  <!-- Map overlay with count -->
                  <div class="absolute top-4 right-4 bg-white dark:bg-gray-800 px-3 py-2 rounded-md shadow-md border border-gray-200 dark:border-gray-700 text-sm font-medium">
                    {{ rideOffers.length }} Mitfahrgelegenheiten
                  </div>
                </div>
              </div>

              <!-- Ride Offers Details -->
              <div>
                <div v-if="selectedOffer" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                  <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                      {{ selectedOffer.first_name || '' }} {{ selectedOffer.last_name }}
                    </h3>
                    <button
                      @click="selectedOffer = null"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                      <span class="sr-only">Schließen</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>

                  <div class="space-y-3">
                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Adresse</h4>
                      <p v-if="selectedOffer.street" class="text-sm">{{ selectedOffer.street }}</p>
                      <p class="text-sm">{{ selectedOffer.zip_code }} {{ selectedOffer.city }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Kontakt</h4>
                      <p v-if="selectedOffer.email" class="text-sm">
                        <span class="font-medium">E-Mail:</span> {{ selectedOffer.email }}
                      </p>
                      <p v-if="selectedOffer.phone" class="text-sm">
                        <span class="font-medium">Telefon:</span> {{ selectedOffer.phone }}
                      </p>
                      <p v-if="selectedOffer.class" class="text-sm">
                        <span class="font-medium">Klasse:</span> {{ selectedOffer.class }}
                      </p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Gültigkeit</h4>
                      <p v-if="selectedOffer.valid_from" class="text-sm">
                        <span class="font-medium">Gültig ab:</span> {{ new Date(selectedOffer.valid_from).toLocaleDateString() }}
                      </p>
                      <p v-if="selectedOffer.valid_until" class="text-sm">
                        <span class="font-medium">Gültig bis:</span> {{ new Date(selectedOffer.valid_until).toLocaleDateString() }}
                      </p>
                    </div>

                    <div v-if="selectedOffer.cost_info" class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Kostenbeteiligung</h4>
                      <p class="text-sm">{{ selectedOffer.cost_info }}</p>
                    </div>

                    <div v-if="selectedOffer.additional_info" class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Weitere Informationen</h4>
                      <p class="text-sm">{{ selectedOffer.additional_info }}</p>
                    </div>
                  </div>
                </div>

                <div v-else class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                  <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-gray-200">Mitfahrgelegenheiten</h3>
                  <p class="mb-4">{{ rideOffers.length }} Mitfahrgelegenheiten gefunden.</p>
                  <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <p class="text-sm text-blue-800 dark:text-blue-200 flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                      <span>Klicken Sie auf einen Pin auf der Karte, um Details zur Mitfahrgelegenheit anzuzeigen.</span>
                    </p>
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

.popup-button {
  margin-top: 8px;
  padding: 4px 8px;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.popup-button:hover {
  background-color: #2563eb;
}
</style>
