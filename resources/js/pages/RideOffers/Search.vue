<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { Search as SearchIcon, AlertTriangle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  rideOffers: {
    type: Array,
    default: () => [],
  },
  searchParams: {
    type: Object,
    default: () => ({}),
  },
  searchCoordinates: {
    type: Object,
    default: null,
  },
});

// Local state for AJAX search results
const rideOffers = ref(props.rideOffers || []);
const searchCoordinates = ref(props.searchCoordinates);
const searchParams = ref(props.searchParams || {});

const form = useForm({
  search_query: props.searchParams.zip_code ? `${props.searchParams.zip_code} ${props.searchParams.city}` : '',
  zip_code: props.searchParams.zip_code || '',
  city: props.searchParams.city || '',
  radius: props.searchParams.radius || 5,
});

// For the location search
const isGettingLocation = ref(false);
const locationError = ref(null);

// Function to get current location
const getCurrentLocation = () => {
  if (!navigator.geolocation) {
    locationError.value = 'Geolocation wird von Ihrem Browser nicht unterstützt.';
    return;
  }

  isGettingLocation.value = true;
  locationError.value = null;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      try {
        // Reverse geocoding to get address from coordinates
        const response = await axios.get('https://nominatim.openstreetmap.org/reverse', {
          params: {
            lat: position.coords.latitude,
            lon: position.coords.longitude,
            format: 'json',
            addressdetails: 1
          },
          headers: {
            'User-Agent': 'Projektwoche/1.0'
          }
        });

        if (response.data && response.data.address) {
          const address = response.data.address;
          form.zip_code = address.postcode || '';
          form.city = address.city || address.town || address.village || '';
          form.search_query = `${form.zip_code} ${form.city}`;

          // Set search coordinates directly
          searchCoordinates.value = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude
          };

          // Automatically submit the search
          submit();
        } else {
          locationError.value = 'Adresse konnte nicht ermittelt werden.';
        }
      } catch (error) {
        console.error('Error getting address from coordinates:', error);
        locationError.value = 'Fehler beim Ermitteln der Adresse.';
      } finally {
        isGettingLocation.value = false;
      }
    },
    (error) => {
      console.error('Geolocation error:', error);
      isGettingLocation.value = false;

      switch(error.code) {
        case error.PERMISSION_DENIED:
          locationError.value = 'Standortabfrage wurde verweigert.';
          break;
        case error.POSITION_UNAVAILABLE:
          locationError.value = 'Standortinformationen sind nicht verfügbar.';
          break;
        case error.TIMEOUT:
          locationError.value = 'Zeitüberschreitung bei der Standortabfrage.';
          break;
        default:
          locationError.value = 'Unbekannter Fehler bei der Standortabfrage.';
      }
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
};

// Function to parse search query into zip code and city
const parseSearchQuery = () => {
  if (!form.search_query) {
    form.zip_code = '';
    form.city = '';
    return;
  }

  // Try to extract zip code (assuming German format of 5 digits)
  const zipMatch = form.search_query.match(/\b\d{5}\b/);
  if (zipMatch) {
    form.zip_code = zipMatch[0];
    // Remove zip code from query to get city
    const cityPart = form.search_query.replace(zipMatch[0], '').trim();
    form.city = cityPart || form.city;
  } else {
    // If no zip code found, assume the whole query is the city
    form.zip_code = '';
    form.city = form.search_query.trim();
  }
};

// Reference to the radius circle on the map
const radiusCircle = ref(null);

const map = ref(null);
const markerLayer = ref(null);
const markers = ref([]);
const selectedOffer = ref(null);
const mapError = ref(false);
const mapLoading = ref(true);
const selectRideOfferHandler = ref(null);
const isSearching = ref(false);
const searchError = ref(null);
const hasSearched = ref(false);

const submit = async () => {
  // Parse search query into zip code and city
  parseSearchQuery();

  // Ensure radius is a number and at least 1
  form.radius = Math.max(1, parseInt(form.radius) || 5);

  // Close any open popups before performing a new search
  if (map.value) {
    map.value.closePopup();

    // Ensure all markers are properly attached to the map
    if (markerLayer.value) {
      // Refresh the marker layer to ensure proper zoom behavior
      const currentMarkers = [...markers.value];
      markerLayer.value.clearLayers();
      markers.value = [];

      // Re-add existing markers to ensure they're properly attached
      currentMarkers.forEach(marker => {
        if (marker.leafletMarker) {
          markerLayer.value.addLayer(marker.leafletMarker);
          markers.value.push(marker);
        }
      });
    }
  }

  // Reset selected offer
  selectedOffer.value = null;

  // Reset search error
  searchError.value = null;

  // Set searching state
  isSearching.value = true;

  hasSearched.value = true;

  try {
    // Make AJAX request to search API
    const response = await axios.post(route('api.ride-offers.search'), {
      zip_code: form.zip_code,
      city: form.city,
      radius: form.radius
    });

    // Update local state with search results
    rideOffers.value = response.data.rideOffers;
    searchParams.value = response.data.searchParams;
    searchCoordinates.value = response.data.searchCoordinates;

    // Update the radius circle on the map if we have coordinates
    updateRadiusCircle();
  } catch (error) {
    console.error('Search error:', error);
    searchError.value = 'Ein Fehler ist bei der Suche aufgetreten. Bitte versuchen Sie es erneut.';
  } finally {
    isSearching.value = false;
  }
};

// Function to update or create the radius circle on the map
const updateRadiusCircle = () => {
  if (!map.value || !searchCoordinates.value) {
    return;
  }

  const L = window.L;
  if (!L) {
    return;
  }

  // Remove existing circle if it exists
  if (radiusCircle.value) {
    radiusCircle.value.remove();
  }

  // Create a new circle with the current radius
  const radius = parseInt(form.radius) || 5;

  try {
    radiusCircle.value = L.circle(
      [searchCoordinates.value.latitude, searchCoordinates.value.longitude],
      {
        radius: radius * 1000, // Convert km to meters
        fillColor: 'hsl(var(--primary))',
        fillOpacity: 0.1,
        color: 'hsl(var(--primary))',
        weight: 2
      }
    ).addTo(map.value);
  } catch (error) {
    console.error('Error creating radius circle:', error);
  }
};

const selectOffer = (offer) => {
  selectedOffer.value = offer;

  // If we have a map and the offer has coordinates, center the map on the offer
  if (map.value && offer.latitude && offer.longitude) {
    try {
      map.value.setView([offer.latitude, offer.longitude], 14);
    } catch (error) {
      console.error('Error centering map:', error);
    }

    // Find the marker for this offer and open its popup
    const marker = markers.value.find(m => m.offerId === offer.id);

    if (marker && marker.leafletMarker) {
      try {
        marker.leafletMarker.openPopup();
      } catch (error) {
        console.error('Error opening popup:', error);
      }
    }
  }
};

// Watch for changes in rideOffers and update the map markers
watch(rideOffers, (newOffers) => {
  if (map.value) {
    // Clear existing markers from the layer
    if (markerLayer.value) {
      markerLayer.value.clearLayers();
    } else {
      // Create a new marker layer if it doesn't exist
      const L = window.L;
      if (L) {
        try {
          markerLayer.value = L.layerGroup().addTo(map.value);
        } catch (error) {
          console.error('Error creating marker layer:', error);
        }
      }
    }

    // Reset markers array
    markers.value = [];

    // Add new markers
    const L = window.L;
    if (L && markerLayer.value) {
      addMarkersToMap(newOffers);
    }
  }
}, { deep: true });

onMounted(() => {
  try {
    initializeMap();
  } catch (error) {
    console.error('Error initializing map:', error);
    mapError.value = true;
    mapLoading.value = false;
  }

  // Define the event handler function
  selectRideOfferHandler.value = (event) => {
    const offerId = event.detail;
    const offer = rideOffers.value.find(o => o.id === offerId);
    if (offer) {
      selectOffer(offer);
    }
  };

  // Add the event listener
  document.addEventListener('select-ride-offer', selectRideOfferHandler.value);
});

onBeforeUnmount(() => {
  // Remove the event listener when the component is unmounted
  if (selectRideOfferHandler.value) {
    document.removeEventListener('select-ride-offer', selectRideOfferHandler.value);
  }

  // Clean up map, marker layer, and markers
  if (markerLayer.value) {
    markerLayer.value.clearLayers();
  }

  if (map.value) {
    try {
      map.value.remove();
      map.value = null;
    } catch (error) {
      console.error('Error removing map:', error);
    }
  }

  markers.value = [];
});

// Function to initialize the map
const initializeMap = () => {
  // Ensure we're in a browser environment
  if (typeof window === 'undefined') {
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
        setTimeout(() => attemptInitialize(retryCount + 1, maxRetries), 500);
        return;
      }
      mapError.value = true;
      mapLoading.value = false;
      return;
    }

    // Wait for the DOM to be ready
    setTimeout(() => {
      // Re-check if map container exists after timeout
      const mapContainer = document.getElementById('map');
      if (!mapContainer) {
        if (retryCount < maxRetries) {
          setTimeout(() => attemptInitialize(retryCount + 1, maxRetries), 500);
          return;
        } else {
          mapError.value = true;
          mapLoading.value = false;
          return;
        }
      }

      try {
        // Find center of map
        let centerLat = 50.5411; // Default to Rodewisch coordinates
        let centerLng = 12.4534; // Coordinates for Rodewisch
        let zoomLevel = 10;

        // If search coordinates are available, use them
        if (searchCoordinates.value) {
          centerLat = parseFloat(searchCoordinates.value.latitude);
          centerLng = parseFloat(searchCoordinates.value.longitude);
          zoomLevel = 12; // Zoom in more when we have search coordinates
        } else {
          const offersWithCoords = rideOffers.value.filter(
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
        }

        // Create the map
        try {
          map.value = L.map('map').setView([centerLat, centerLng], zoomLevel);

          // Add OpenStreetMap tiles
          try {
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map.value);
          } catch (tileError) {
            console.error('Error adding tile layer:', tileError);
          }

          // Create a layer group for markers
          try {
            markerLayer.value = L.layerGroup().addTo(map.value);
          } catch (layerError) {
            console.error('Error creating marker layer:', layerError);
          }

          // Add markers for ride offers
          addMarkersToMap(rideOffers.value);

          // Add radius circle if we have search coordinates
          updateRadiusCircle();

          mapLoading.value = false;
        } catch (mapError) {
          console.error('Error creating map:', mapError);
          mapError.value = true;
          mapLoading.value = false;
        }
      } catch (error) {
        console.error('Error initializing map:', error);

        if (retryCount < maxRetries) {
          setTimeout(() => attemptInitialize(retryCount + 1, maxRetries), 500);
        } else {
          mapError.value = true;
          mapLoading.value = false;
        }
      }
    }, 500);
  };

  // Start the initialization process
  attemptInitialize();
};

// Function to add markers to the map
const addMarkersToMap = (offers) => {
  if (!map.value || !markerLayer.value) {
    return;
  }

  const L = window.L;
  if (!L) {
    return;
  }

  const offersWithCoords = offers.filter(
    offer => offer.latitude && offer.longitude
  );

  if (offersWithCoords.length > 0) {
    offersWithCoords.forEach((offer) => {
      try {
        // Create the marker
        const marker = L.marker([offer.latitude, offer.longitude])
          .bindPopup(`
            <div class="popup-content">
              <h3 class="font-semibold">${offer.first_name || ''} ${offer.last_name}</h3>
              <p>${offer.street ? offer.street + '<br>' : ''}${offer.zip_code} ${offer.city}</p>
              ${offer.distance !== undefined ?
                `<p class="text-sm text-green-600"><svg xmlns="http://www.w3.org/2000/svg" class="inline h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>${Math.round(offer.distance * 10) / 10} km entfernt</p>`
                : ''
              }
              <button class="popup-button mt-2" onclick="document.dispatchEvent(new CustomEvent('select-ride-offer', {detail: ${offer.id}}))">
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
      } catch {
        // Silently fail for individual markers
      }
    });
  } else if (map.value) {
    // Add a message to the map if no offers with coordinates
    const centerPoint = map.value.getCenter();
    try {
      L.popup()
        .setLatLng([centerPoint.lat, centerPoint.lng])
        .setContent('Keine Mitfahrgelegenheiten mit Koordinaten gefunden.')
        .openOn(map.value);
    } catch {
      // Silently fail
    }
  }
};

</script>

<template>
  <Head title="Mitfahrgelegenheit suchen" />

  <AppLayout :breadcrumbs="[
    { name: 'Home', href: route('home') },
    { name: 'Mitfahrgelegenheit suchen', href: route('ride-offers.search') }
  ]">

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <!-- Search Form -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 mb-6">
              <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white flex items-center">
                <SearchIcon class="h-5 w-5 mr-2 text-primary" />
                Mitfahrgelegenheit suchen
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Finden Sie Mitfahrgelegenheiten in Ihrer Nähe. Geben Sie einen Ort oder eine Postleitzahl ein und wählen Sie den gewünschten Umkreis.
              </p>

              <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Combined Search Field -->
                    <div class="md:col-span-1">
                        <InputLabel for="search_query" value="PLZ und Ort" required />
                        <div class="mt-1 flex">
                            <TextInput
                                id="search_query"
                                type="text"
                                class="block w-full rounded-r-none h-full"
                                v-model="form.search_query"
                                required
                                autofocus
                                placeholder="z.B. 08228 Rodewisch"
                            />
                            <div class="relative group">
                                <button
                                    type="button"
                                    class="px-3 bg-primary text-white rounded-r-md hover:bg-primary-accent focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 transition-colors h-full"
                                    @click="getCurrentLocation"
                                    :disabled="isGettingLocation"
                                    title="Aktuellen Standort verwenden"
                                >
                                    <template v-if="isGettingLocation">
                                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                    </template>
                                    <template v-else>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </template>
                                </button>
                                <!-- Tooltip -->
                                <div
                                    class="absolute right-0 top-full mt-2 w-48 bg-gray-800 text-white text-xs rounded py-1 px-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10"
                                >
                                    Aktuellen Standort verwenden
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Geben Sie eine Postleitzahl und einen Ort ein
                        </p>
                        <div v-if="locationError" class="mt-1 text-xs text-red-500">
                            {{ locationError }}
                        </div>
                        <InputError class="mt-2" :message="form.errors.search_query || form.errors.zip_code || form.errors.city" />
                    </div>


                    <!-- Radius Slider -->
                  <div class="md:col-span-1">
                    <InputLabel for="radius" value="Umkreis" />
                    <div class="mt-3 px-2 relative">
                      <div class="flex items-center mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-1" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-primary dark:text-primary-accent">{{ form.radius || 5 }} km</span>
                      </div>

                      <!-- Custom styled slider -->
                      <div class="relative">
                        <div class="absolute h-1 bg-gradient-to-r from-primary/50 to-primary rounded-full top-1/2 transform -translate-y-1/2 left-0 right-0"></div>
                        <input
                          id="radius"
                          type="range"
                          class="w-full h-2 bg-transparent appearance-none cursor-pointer z-10 relative"
                          v-model="form.radius"
                          min="1"
                          max="100"
                          step="1"
                          style="--thumb-color: hsl(var(--primary)); --thumb-size: 16px;"
                        />
                      </div>

                      <div class="flex justify-between mt-1">
                        <span class="text-xs text-gray-500">1 km</span>
                        <span class="text-xs text-gray-500">100 km</span>
                      </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-start">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 flex-shrink-0 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                      <span>Suche nach Mitfahrgelegenheiten im angegebenen Umkreis</span>
                    </p>
                    <InputError class="mt-2" :message="form.errors.radius" />
                  </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between mt-6">
                  <div v-if="searchError" class="text-red-500 mb-4 sm:mb-0">
                    {{ searchError }}
                  </div>
                    <div class="w-full flex items-center justify-between">
                        <!-- Textbereich links -->
                        <div class="text-sm text-gray-500 hidden sm:block">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block text-primary mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                      clip-rule="evenodd" />
                            </svg>
                            Drücken Sie Enter oder klicken Sie auf Suchen
                        </div>

                        <!-- Buttonbereich rechts -->
                        <PrimaryButton
                            type="submit"
                            class="bg-primary hover:bg-primary-accent transition-all transform hover:scale-105"
                            :disabled="isSearching"
                        >
                            <template v-if="isSearching">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Suche läuft...
                            </template>
                            <template v-else>
                                <SearchIcon class="h-5 w-5 mr-2" />
                                Suchen
                            </template>
                        </PrimaryButton>
                    </div>

                </div>
              </form>
            </div>

            <!-- Map and Results Section - Always rendered -->
            <div class="mt-8">
              <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                <div class="flex items-center mb-4 md:mb-0">
                  <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-foreground">Karte der Mitfahrgelegenheiten</h3>
                    <p class="text-sm text-muted-foreground hidden md:block">
                      Entdecken Sie alle verfügbaren Mitfahrgelegenheiten auf der Karte
                    </p>
                  </div>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a :href="route('ride-offers.create')" class="inline-flex items-center justify-center px-5 py-2.5 gradient-primary button-glow text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Anbieten
                    </a>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Map - Takes more space on larger screens -->
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
                          <AlertTriangle class="h-8 w-8 text-destructive" />
                        </div>
                        <h3 class="text-lg font-semibold mb-2 text-destructive">Fehler beim Laden der Karte</h3>
                        <p class="text-muted-foreground">Bitte versuchen Sie es später erneut oder kontaktieren Sie den Support.</p>
                      </div>
                    </div>

                    <!-- Map container -->
                    <div id="map" class="h-[600px] w-full rounded-lg overflow-hidden"></div>

                    <!-- Map overlay with count -->
                    <div v-if="rideOffers.length > 0" class="absolute top-4 right-4 glass-effect px-4 py-2 rounded-full shadow-md text-sm font-medium">
                      <span class="flex items-center">
                        <span class="w-2 h-2 rounded-full bg-primary mr-2 animate-pulse"></span>
                        {{ rideOffers.length }} Mitfahrgelegenheiten
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Ride Offers List / Details - Only shown when there are ride offers -->
                <div v-if="rideOffers.length > 0">
                  <div v-if="selectedOffer" class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start mb-4">
                      <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                          {{ selectedOffer.first_name || '' }} {{ selectedOffer.last_name }}
                        </h3>
                        <div v-if="selectedOffer.distance !== undefined" class="mt-1 text-sm">
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            {{ Math.round(selectedOffer.distance * 10) / 10 }} km entfernt
                          </span>
                        </div>
                      </div>
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
                    <div class="bg-primary/5 dark:bg-primary/20 p-4 rounded-lg border border-primary/10 dark:border-primary/30">
                      <p class="text-sm text-primary dark:text-primary-foreground flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <span>Klicken Sie auf einen Pin auf der Karte, um Details zur Mitfahrgelegenheit anzuzeigen.</span>
                      </p>
                    </div>
                  </div>
                </div>

                <!-- No search results message - Only shown when search was performed but no results found -->
                <div v-else-if="Object.keys(searchParams).length > 0 && hasSearched" class="gradient-card modern-shadow p-8 rounded-xl text-center">
                  <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                    <AlertTriangle class="h-8 w-8 text-warning" />
                  </div>
                  <h3 class="text-xl font-semibold mb-3 text-foreground">Keine Mitfahrgelegenheiten gefunden</h3>
                  <p class="text-muted-foreground mb-6 max-w-md mx-auto">Versuchen Sie es mit anderen Suchkriterien oder bieten Sie selbst eine Mitfahrgelegenheit an.</p>
                  <a :href="route('ride-offers.create')" class="inline-flex items-center justify-center px-5 py-2.5 gradient-primary button-glow text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Mitfahrgelegenheit anbieten
                  </a>
                </div>

                  <div v-else-if="!hasSearched" class="gradient-card modern-shadow p-8 rounded-xl text-center">
                      <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                          <SearchIcon class="h-8 w-8 text-warning" />
                      </div>
                      <h3 class="text-xl font-semibold mb-3 text-foreground">Starte die Suche</h3>
                      <p class="text-muted-foreground mb-6 max-w-md mx-auto">Suche etwas, um Ergebnisse zu sehen.</p>
                  </div>
              </div>
            </div>


            <!-- Initial state message - Only shown when no search has been performed yet -->
            <div v-if="Object.keys(searchParams).length === 0" class="mt-8 gradient-card modern-shadow p-8 rounded-xl">
              <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 animated-bg">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <h3 class="text-xl font-semibold mb-3 text-foreground">Suchen Sie nach Mitfahrgelegenheiten</h3>
                <p class="text-muted-foreground mb-6 max-w-md mx-auto">Geben Sie oben Ihre PLZ und Ihren Ort ein, um Mitfahrgelegenheiten in Ihrer Nähe zu finden.</p>
              </div>
              <div class="bg-primary/5 p-5 rounded-xl border border-primary/10 mt-4 flex items-start">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-4">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div>
                  <h4 class="font-medium text-foreground mb-1">Tipp</h4>
                  <p class="text-sm text-muted-foreground">Die Karte wird automatisch aktualisiert, sobald Sie eine Suche durchführen. Sie können auch Ihren aktuellen Standort verwenden, um Mitfahrgelegenheiten in Ihrer Nähe zu finden.</p>
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
.marker-pin {
  width: 30px;
  height: 30px;
  border-radius: 50% 50% 50% 0;
  background: currentColor;
  position: absolute;
  transform: rotate(-45deg);
  left: 50%;
  top: 50%;
  margin: -15px 0 0 -15px;
}

.marker-pin::after {
  content: '';
  width: 24px;
  height: 24px;
  margin: 3px 0 0 3px;
  background: #fff;
  position: absolute;
  border-radius: 50%;
}

.popup-content {
  padding: 8px;
  min-width: 150px;
}

.popup-button {
  margin-top: 8px;
  padding: 4px 8px;
  background-color: hsl(var(--primary));
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
}

.popup-button:hover {
  background-color: hsl(var(--primary-accent));
}

/* Custom slider styling */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
  cursor: pointer;
  height: 20px;
}

/* Slider Track */
input[type="range"]::-webkit-slider-runnable-track {
  background: transparent;
  height: 4px;
  border-radius: 2px;
}

input[type="range"]::-moz-range-track {
  background: transparent;
  height: 4px;
  border-radius: 2px;
}

/* Slider Thumb */
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  margin-top: -6px;
  background-color: hsl(var(--primary));
  border: 2px solid white;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

input[type="range"]::-moz-range-thumb {
  border: none;
  background-color: hsl(var(--primary));
  border: 2px solid white;
  height: 16px;
  width: 16px;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

/* Hover and Focus states */
input[type="range"]:hover::-webkit-slider-thumb {
  background-color: hsl(var(--primary-accent));
  transform: scale(1.1);
}

input[type="range"]:hover::-moz-range-thumb {
  background-color: hsl(var(--primary-accent));
  transform: scale(1.1);
}

input[type="range"]:focus {
  outline: none;
}

input[type="range"]:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 3px hsl(var(--primary) / 0.3);
}

input[type="range"]:focus::-moz-range-thumb {
  box-shadow: 0 0 0 3px hsl(var(--primary) / 0.3);
}
</style>
