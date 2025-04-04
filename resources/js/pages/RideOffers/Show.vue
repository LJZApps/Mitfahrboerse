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
    map.value = L.map('map', {scrollWheelZoom:false, attributionControl: false, zoomControl: false, dragging: false}).setView([props.rideOffer.latitude, props.rideOffer.longitude], 15);

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
      `,
          { closeButton: false, autoClose: false, closeOnEscapeKey: false, closeOnClick: false, keepInView: true }
      )
        .openPopup();
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

  <AppLayout :breadcrumbs="[
    { name: 'Home', href: route('home') },
    { name: 'Mitfahrgelegenheiten', href: route('ride-offers.index') },
    { name: `${rideOffer.first_name || ''} ${rideOffer.last_name}`, href: route('ride-offers.show', rideOffer.id) }
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
                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-5h2a1 1 0 00.9-.55l4-8a1 1 0 00-.9-1.45H5a1 1 0 00-1 1v1zm1 7a1 1 0 011-1h7a1 1 0 110 2H5a1 1 0 01-1-1z" />
                  </svg>
                </div>
                <div>
                  <h2 class="text-xl font-bold bg-gradient-to-r from-primary to-primary-accent bg-clip-text text-transparent">
                    {{ rideOffer.first_name || '' }} {{ rideOffer.last_name }}
                  </h2>
                  <p class="text-sm text-muted-foreground hidden md:block">
                    Mitfahrgelegenheit Details
                  </p>
                </div>
              </div>

              <div class="flex flex-col sm:flex-row gap-4">
                <Link
                  :href="route('ride-offers.overview')"
                  class="inline-flex items-center justify-center px-5 py-2.5 bg-muted hover:bg-muted/80 text-muted-foreground font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                  </svg>
                  Zurück zur Übersicht
                </Link>

                <Link
                  :href="route('ride-offers.search')"
                  class="inline-flex items-center justify-center px-5 py-2.5 gradient-primary button-glow text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-300"
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
                <div class="gradient-card modern-shadow p-6 rounded-xl">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Kontaktinformationen
                  </h4>

                  <div class="space-y-3">
                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Name:</span>
                        <span>{{ rideOffer.first_name || '' }} {{ rideOffer.last_name }}</span>
                      </p>
                    </div>

                    <div v-if="rideOffer.class" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Klasse:</span>
                        <span>{{ rideOffer.class }}</span>
                      </p>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">E-Mail:</span>
                        <span>{{ rideOffer.email }}</span>
                      </p>
                    </div>

                    <div v-if="rideOffer.phone" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Telefon:</span>
                        <span>{{ rideOffer.phone }}</span>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="gradient-card modern-shadow p-6 rounded-xl mt-6">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Adresse
                  </h4>

                  <div class="space-y-3">
                    <div v-if="rideOffer.street" class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Straße:</span>
                        <span>{{ rideOffer.street }}</span>
                      </p>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">PLZ/Ort:</span>
                        <span>{{ rideOffer.zip_code }} {{ rideOffer.city }}</span>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="gradient-card modern-shadow p-6 rounded-xl mt-6">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Gültigkeit
                  </h4>

                  <div class="space-y-3">
                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Gültig ab:</span>
                        <span>{{ rideOffer.valid_from ? new Date(rideOffer.valid_from).toLocaleDateString() : 'Ab sofort' }}</span>
                      </p>
                    </div>

                    <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                      <p class="flex items-start text-sm text-muted-foreground">
                        <span class="font-medium w-24">Gültig bis:</span>
                        <span>{{ rideOffer.valid_until ? new Date(rideOffer.valid_until).toLocaleDateString() : 'Unbegrenzt' }}</span>
                      </p>
                    </div>
                  </div>
                </div>

                <div v-if="rideOffer.cost_info" class="gradient-card modern-shadow p-6 rounded-xl mt-6">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Kostenbeteiligung
                  </h4>
                  <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <p class="text-sm text-muted-foreground">{{ rideOffer.cost_info }}</p>
                  </div>
                </div>

                <div v-if="rideOffer.additional_info" class="gradient-card modern-shadow p-6 rounded-xl mt-6">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Weitere Informationen
                  </h4>
                  <div class="bg-gray-50/50 dark:bg-gray-900/50 p-4 rounded-lg">
                    <p class="text-sm text-muted-foreground">{{ rideOffer.additional_info }}</p>
                  </div>
                </div>
              </div>

              <div>
                <div class="gradient-card modern-shadow p-6 rounded-xl">
                  <h4 class="font-semibold mb-4 text-foreground flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 mr-3">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    Standort
                  </h4>

                  <div v-if="rideOffer.latitude && rideOffer.longitude" class="h-80 rounded-lg overflow-hidden">
                    <div id="map" class="h-full w-full rounded-lg"></div>
                  </div>
                  <div v-else class="h-80 bg-gray-50/50 dark:bg-gray-900/50 rounded-lg flex items-center justify-center">
                    <div class="text-center p-6">
                      <div class="w-16 h-16 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <h4 class="text-lg font-medium mb-2 text-foreground">Keine Karteninformationen</h4>
                      <p class="text-muted-foreground">Für diese Mitfahrgelegenheit sind keine Koordinaten verfügbar.</p>
                    </div>
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
