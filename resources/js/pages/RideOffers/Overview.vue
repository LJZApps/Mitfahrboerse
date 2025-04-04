<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Map as MapIcon, List as ListIcon } from 'lucide-vue-next';

const props = defineProps({
  rideOffers: {
    type: Array,
    default: () => [],
  },
});

// View mode toggle (map or list)
const viewMode = ref('map'); // 'map' or 'list'

const map = ref(null);
const markerLayer = ref(null);
const markers = ref([]);
const selectedOffer = ref(null);
const selectRideOfferHandler = ref(null);
const mapError = ref(false);
const mapLoading = ref(true);

// Computed property to filter offers with coordinates for the map
const offersWithCoordinates = computed(() => {
  return props.rideOffers.filter(offer => offer.latitude && offer.longitude);
});

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
  // Only initialize map if in map view
  if (viewMode.value !== 'map') {
    return;
  }

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
          map.value = L.map('map').setView([centerLat, centerLng], 6);

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
                      <button class="popup-button w-full" onclick="document.dispatchEvent(new CustomEvent('select-ride-offer', {detail: ${offer.id}}))">
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

// Watch for changes in viewMode to initialize map when switching to map view
watch(viewMode, (newMode) => {
  if (newMode === 'map') {
    // If map already exists, remove it first to ensure proper reinitialization
    if (map.value) {
      // Clean up existing map
      if (markerLayer.value) {
        markerLayer.value.clearLayers();
        markerLayer.value = null;
      }
      map.value.remove();
      map.value = null;
      markers.value = [];
    }
    // Reset loading state
    mapLoading.value = true;
    // Initialize the map
    initializeMap();
  }
});
</script>

<template>
  <Head title="Mitfahrgelegenheiten Übersicht" />

  <AppLayout :breadcrumbs="[
    { name: 'Home', href: route('home') },
    { name: 'Mitfahrgelegenheiten Übersicht', href: route('ride-offers.index') }
  ]">

    <div class="py-12 relative">
      <!-- Decorative background elements -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-64 right-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="absolute top-1/3 -left-24 h-64 w-64 rounded-full bg-secondary/5 blur-3xl"></div>
      </div>

      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 relative z-10">
        <div class="gradient-card modern-shadow overflow-hidden rounded-xl">
          <div class="p-8 border-b border-gray-100/20 dark:border-gray-700/20">

            <div class="flex flex-col md:flex-row justify-between items-center mb-8">
              <div class="flex items-center mb-4 md:mb-0">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-xl font-bold bg-gradient-to-r from-primary to-primary-accent bg-clip-text text-transparent">Mitfahrgelegenheiten Übersicht</h2>
                  <p class="text-sm text-muted-foreground hidden md:block">
                    Entdecken Sie alle verfügbaren Mitfahrgelegenheiten
                  </p>
                </div>
              </div>
              <div class="flex flex-col sm:flex-row gap-4">
                <Link
                  :href="route('ride-offers.search')"
                  class="inline-flex items-center justify-center px-5 py-2.5 bg-primary hover:bg-primary-accent text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                  Suchen
                </Link>
                <Link
                  :href="route('ride-offers.create')"
                  class="inline-flex items-center justify-center px-5 py-2.5 gradient-primary button-glow text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  Anbieten
                </Link>
              </div>
            </div>

            <!-- View toggle buttons -->
            <div class="flex justify-center mb-6">
              <div class="inline-flex rounded-lg p-1 bg-gray-100 dark:bg-gray-800">
                <button
                  @click="viewMode = 'map'"
                  class="flex items-center px-4 py-2 rounded-md transition-all duration-200"
                  :class="viewMode === 'map' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >
                  <MapIcon class="h-5 w-5 mr-2" />
                  <span>Karte</span>
                </button>
                <button
                  @click="viewMode = 'list'"
                  class="flex items-center px-4 py-2 rounded-md transition-all duration-200"
                  :class="viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow-sm text-primary' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                >
                  <ListIcon class="h-5 w-5 mr-2" />
                  <span>Liste</span>
                </button>
              </div>
            </div>

            <!-- Map View -->
            <div v-if="viewMode === 'map'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
              <!-- Map -->
              <div class="md:col-span-2">
                <div class="relative gradient-card modern-shadow p-4 rounded-xl overflow-hidden">
                  <!-- Loading overlay -->
                  <div v-if="mapLoading" class="absolute inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-10 rounded-xl">
                    <div class="text-center">
                      <div class="relative w-16 h-16 mx-auto mb-4">
                        <div class="absolute inset-0 rounded-full bg-primary/10 animate-ping"></div>
                        <svg class="animate-spin relative z-10 h-16 w-16 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </div>
                      <p class="text-foreground font-medium">Karte wird geladen...</p>
                    </div>
                  </div>

                  <!-- Error message -->
                  <div v-if="mapError" class="absolute inset-0 bg-destructive/5 backdrop-blur-sm flex items-center justify-center z-10 rounded-xl">
                    <div class="text-center p-6 max-w-md">
                      <div class="w-16 h-16 rounded-full bg-destructive/10 flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                      </div>
                      <h3 class="text-lg font-semibold mb-2 text-destructive">Fehler beim Laden der Karte</h3>
                      <p class="text-muted-foreground">Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.</p>
                    </div>
                  </div>

                  <!-- Map container -->
                  <div id="map" class="h-[600px] w-full rounded-lg overflow-hidden"></div>

                  <!-- Map overlay with count -->
                  <div v-if="offersWithCoordinates.length > 0" class="absolute top-4 right-4 glass-effect px-4 py-2 rounded-full shadow-md text-sm font-medium">
                    <span class="flex items-center">
                      <span class="w-2 h-2 rounded-full bg-primary mr-2 animate-pulse"></span>
                      {{ offersWithCoordinates.length }} Mitfahrgelegenheiten
                    </span>
                  </div>
                </div>
              </div>

              <!-- Ride Offers Details -->
              <div>
                <div v-if="selectedOffer" class="gradient-card modern-shadow p-6 rounded-xl">
                  <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-foreground">
                      {{ selectedOffer.first_name || '' }} {{ selectedOffer.last_name }}
                    </h3>
                    <button
                      @click="selectedOffer = null"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                    >
                      <span class="sr-only">Schließen</span>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>

                  <div class="space-y-4">
                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        Adresse
                      </h4>
                      <p v-if="selectedOffer.street" class="text-sm text-muted-foreground">{{ selectedOffer.street }}</p>
                      <p class="text-sm text-muted-foreground">{{ selectedOffer.zip_code }} {{ selectedOffer.city }}</p>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        Kontakt
                      </h4>
                      <p v-if="selectedOffer.email" class="text-sm text-muted-foreground">
                        <span class="font-medium">E-Mail:</span> {{ selectedOffer.email }}
                      </p>
                      <p v-if="selectedOffer.phone" class="text-sm text-muted-foreground">
                        <span class="font-medium">Telefon:</span> {{ selectedOffer.phone }}
                      </p>
                      <p v-if="selectedOffer.class" class="text-sm text-muted-foreground">
                        <span class="font-medium">Klasse:</span> {{ selectedOffer.class }}
                      </p>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        Gültigkeit
                      </h4>
                      <p v-if="selectedOffer.valid_from" class="text-sm text-muted-foreground">
                        <span class="font-medium">Gültig ab:</span> {{ new Date(selectedOffer.valid_from).toLocaleDateString() }}
                      </p>
                      <p v-if="selectedOffer.valid_until" class="text-sm text-muted-foreground">
                        <span class="font-medium">Gültig bis:</span> {{ new Date(selectedOffer.valid_until).toLocaleDateString() }}
                      </p>
                    </div>

                    <div v-if="selectedOffer.cost_info" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                        </svg>
                        Kostenbeteiligung
                      </h4>
                      <p class="text-sm text-muted-foreground">{{ selectedOffer.cost_info }}</p>
                    </div>

                    <div v-if="selectedOffer.additional_info" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        Weitere Informationen
                      </h4>
                      <p class="text-sm text-muted-foreground">{{ selectedOffer.additional_info }}</p>
                    </div>
                  </div>
                </div>

                <div v-else class="gradient-card modern-shadow p-6 rounded-xl">
                  <h3 class="text-lg font-semibold mb-3 text-foreground">Mitfahrgelegenheiten</h3>
                  <p class="mb-4 text-muted-foreground">{{ offersWithCoordinates.length }} Mitfahrgelegenheiten gefunden.</p>
                  <div class="bg-primary/5 p-4 rounded-lg border border-primary/10">
                    <p class="text-sm text-muted-foreground flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 flex-shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                      <span>Klicken Sie auf einen Pin auf der Karte, um Details zur Mitfahrgelegenheit anzuzeigen.</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- List View -->
            <div v-if="viewMode === 'list'" class="space-y-6">
              <div class="gradient-card modern-shadow p-6 rounded-xl">
                <h3 class="text-lg font-semibold mb-4 text-foreground flex items-center">
                  <ListIcon class="h-5 w-5 mr-2 text-primary" />
                  Liste aller Mitfahrgelegenheiten
                </h3>

                <div v-if="rideOffers.length === 0" class="text-center py-8">
                  <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                  </div>
                  <h4 class="text-lg font-medium mb-2 text-foreground">Keine Mitfahrgelegenheiten gefunden</h4>
                  <p class="text-muted-foreground">Es sind derzeit keine Mitfahrgelegenheiten verfügbar.</p>
                </div>

                <div v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                  <Link
                       v-for="offer in rideOffers"
                       :key="offer.id"
                       :href="route('ride-offers.show', offer.id)"
                       class="block py-4 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 rounded-lg transition-all duration-200 px-4 -mx-4 cursor-pointer hover:shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                      <div class="mb-2 md:mb-0">
                        <h4 class="font-medium text-foreground">{{ offer.first_name || '' }} {{ offer.last_name }}</h4>
                        <p class="text-sm text-muted-foreground flex items-center mt-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-primary" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                          </svg>
                          {{ offer.zip_code }} {{ offer.city }}
                          <span v-if="offer.street" class="mx-1">•</span>
                          {{ offer.street }}
                        </p>
                      </div>
                      <div class="flex flex-wrap gap-2">
                        <span v-if="offer.valid_from || offer.valid_until" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-foreground">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                          </svg>
                          {{ offer.valid_from ? new Date(offer.valid_from).toLocaleDateString() : '' }}
                          {{ offer.valid_from && offer.valid_until ? '-' : '' }}
                          {{ offer.valid_until ? new Date(offer.valid_until).toLocaleDateString() : '' }}
                        </span>
                        <span v-if="offer.class" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                          {{ offer.class }}
                        </span>
                      </div>
                    </div>
                  </Link>
                </div>
              </div>

              <!-- Selected Offer Details in List View -->
              <div v-if="selectedOffer" class="gradient-card modern-shadow p-6 rounded-xl">
                <div class="flex justify-between items-start mb-4">
                  <h3 class="text-lg font-semibold text-foreground">
                    {{ selectedOffer.first_name || '' }} {{ selectedOffer.last_name }}
                  </h3>
                  <button
                    @click="selectedOffer = null"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                  >
                    <span class="sr-only">Schließen</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                      </svg>
                      Adresse
                    </h4>
                    <p v-if="selectedOffer.street" class="text-sm text-muted-foreground">{{ selectedOffer.street }}</p>
                    <p class="text-sm text-muted-foreground">{{ selectedOffer.zip_code }} {{ selectedOffer.city }}</p>
                  </div>

                  <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                      </svg>
                      Kontakt
                    </h4>
                    <p v-if="selectedOffer.email" class="text-sm text-muted-foreground">
                      <span class="font-medium">E-Mail:</span> {{ selectedOffer.email }}
                    </p>
                    <p v-if="selectedOffer.phone" class="text-sm text-muted-foreground">
                      <span class="font-medium">Telefon:</span> {{ selectedOffer.phone }}
                    </p>
                    <p v-if="selectedOffer.class" class="text-sm text-muted-foreground">
                      <span class="font-medium">Klasse:</span> {{ selectedOffer.class }}
                    </p>
                  </div>

                  <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                      </svg>
                      Gültigkeit
                    </h4>
                    <p v-if="selectedOffer.valid_from" class="text-sm text-muted-foreground">
                      <span class="font-medium">Gültig ab:</span> {{ new Date(selectedOffer.valid_from).toLocaleDateString() }}
                    </p>
                    <p v-if="selectedOffer.valid_until" class="text-sm text-muted-foreground">
                      <span class="font-medium">Gültig bis:</span> {{ new Date(selectedOffer.valid_until).toLocaleDateString() }}
                    </p>
                  </div>

                  <div v-if="selectedOffer.cost_info" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                      </svg>
                      Kostenbeteiligung
                    </h4>
                    <p class="text-sm text-muted-foreground">{{ selectedOffer.cost_info }}</p>
                  </div>
                </div>

                <div v-if="selectedOffer.additional_info" class="mt-4 bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                  <h4 class="font-medium text-sm text-foreground mb-2 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-primary" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Weitere Informationen
                  </h4>
                  <p class="text-sm text-muted-foreground">{{ selectedOffer.additional_info }}</p>
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
  @apply bg-primary;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.popup-button:hover {
  @apply bg-primary-accent;
}

/* Modern styling */
.gradient-card {
  background: linear-gradient(to bottom right, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.8));
}

.dark .gradient-card {
  background: linear-gradient(to bottom right, rgba(31, 41, 55, 0.9), rgba(17, 24, 39, 0.8));
}

.modern-shadow {
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
}

.gradient-primary {
  @apply bg-gradient-to-r from-primary via-primary/90 to-primary-accent;
}

.button-glow {
  position: relative;
  z-index: 1;
  overflow: hidden;
}

.button-glow:after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 200%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  z-index: -1;
  transition: 0.5s;
}

.button-glow:hover:after {
  left: 100%;
}

.glass-effect {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark .glass-effect {
  background: rgba(17, 24, 39, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
