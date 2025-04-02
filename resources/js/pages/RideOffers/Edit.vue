<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { Search as SearchIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import TextArea from '@/components/TextArea.vue';

const props = defineProps({
  rideOffer: {
    type: Object,
    required: true,
  },
});

// Format dates for form inputs (YYYY-MM-DD)
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toISOString().split('T')[0];
};

const form = useForm({
  zip_code: props.rideOffer.zip_code,
  city: props.rideOffer.city,
  street: props.rideOffer.street || '',
  last_name: props.rideOffer.last_name,
  first_name: props.rideOffer.first_name || '',
  email: props.rideOffer.email,
  class: props.rideOffer.class || '',
  phone: props.rideOffer.phone || '',
  valid_from: formatDate(props.rideOffer.valid_from),
  valid_until: formatDate(props.rideOffer.valid_until),
  cost_info: props.rideOffer.cost_info || '',
  additional_info: props.rideOffer.additional_info || '',
});

// Address search functionality
const addressQuery = ref('');
const addressSuggestions = ref([]);
const isSearching = ref(false);
const showSuggestions = ref(false);

// Initialize address query with existing address
onMounted(() => {
  // Combine address components into a single string for the search field
  const addressParts = [];
  if (form.street) addressParts.push(form.street);
  if (form.zip_code) addressParts.push(form.zip_code);
  if (form.city) addressParts.push(form.city);

  addressQuery.value = addressParts.join(', ');
});

// Debounce function to limit API calls
const debounce = (fn, delay) => {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => fn(...args), delay);
  };
};

// Search for address suggestions using Nominatim API
const searchAddress = debounce(async (query) => {
  if (!query || query.length < 3) {
    addressSuggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  isSearching.value = true;
  showSuggestions.value = true;

  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&addressdetails=1&limit=5&countrycodes=de`, {
      headers: {
        'Accept': 'application/json',
        'User-Agent': 'Mitfahrboerse/1.0'
      }
    });

    if (response.ok) {
      const data = await response.json();
      addressSuggestions.value = data.map(item => ({
        display_name: item.display_name,
        street: item.address.road ? `${item.address.road}${item.address.house_number ? ' ' + item.address.house_number : ''}` : '',
        city: item.address.city || item.address.town || item.address.village || item.address.municipality || '',
        zip_code: item.address.postcode || '',
        latitude: item.lat,
        longitude: item.lon
      }));
    } else {
      addressSuggestions.value = [];
    }
  } catch (error) {
    console.error('Error searching for address:', error);
    addressSuggestions.value = [];
  } finally {
    isSearching.value = false;
  }
}, 300);

// Watch for changes in the address query
watch(addressQuery, (newQuery) => {
  searchAddress(newQuery);
});

// Select an address suggestion
const selectAddress = (suggestion) => {
  form.zip_code = suggestion.zip_code;
  form.city = suggestion.city;
  form.street = suggestion.street;
  addressQuery.value = suggestion.display_name;
  showSuggestions.value = false;
};

const editCode = computed(() => props.rideOffer.edit_code);

const submit = () => {
  form.put(route('ride-offers.update', editCode.value));
};

const confirmDelete = () => {
  if (confirm('Sind Sie sicher, dass Sie diese Mitfahrgelegenheit löschen möchten?')) {
    form.delete(route('ride-offers.destroy', editCode.value));
  }
};
</script>

<template>
  <Head title="Mitfahrgelegenheit bearbeiten" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Mitfahrgelegenheit bearbeiten
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <form @submit.prevent="submit">
              <!-- General error messages -->
              <div v-if="form.errors.rate_limit" class="mb-4 p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-md text-red-600 dark:text-red-300">
                <div class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500 dark:text-red-400 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <span>{{ form.errors.rate_limit }}</span>
                </div>
              </div>

              <!-- Address Search -->
              <div class="col-span-full mb-4">
                <InputLabel for="address_search" value="Adresse suchen" />
                <div class="relative">
                  <div class="flex">
                    <div class="relative flex-grow">
                      <TextInput
                        id="address_search"
                        type="text"
                        class="mt-1 block w-full pr-10"
                        v-model="addressQuery"
                        placeholder="Geben Sie eine Adresse ein (z.B. Straße, Stadt, PLZ)"
                        autocomplete="off"
                        @focus="showSuggestions = addressSuggestions.length > 0"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <SearchIcon class="h-5 w-5 text-gray-400" />
                      </div>
                    </div>
                  </div>

                  <!-- Address Suggestions Dropdown -->
                  <div v-if="showSuggestions && addressSuggestions.length > 0"
                       class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700 max-h-60 overflow-auto">
                    <div v-if="isSearching" class="p-2 text-center text-gray-500 dark:text-gray-400">
                      <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <p class="mt-1">Suche...</p>
                    </div>
                    <ul v-else>
                      <li v-for="(suggestion, index) in addressSuggestions" :key="index"
                          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                          @click="selectAddress(suggestion)">
                        <div class="font-medium">{{ suggestion.display_name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ suggestion.street ? suggestion.street + ', ' : '' }}{{ suggestion.zip_code }} {{ suggestion.city }}
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  Suchen Sie nach einer Adresse, um die Felder automatisch auszufüllen
                </p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ZIP Code -->
                <div>
                  <InputLabel for="zip_code" value="PLZ *" />
                  <TextInput
                    id="zip_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.zip_code"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.zip_code" />
                </div>

                <!-- City -->
                <div>
                  <InputLabel for="city" value="Ort *" />
                  <TextInput
                    id="city"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.city"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <!-- Street -->
                <div>
                  <InputLabel for="street" value="Straße, Hausnummer" />
                  <TextInput
                    id="street"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.street"
                  />
                  <InputError class="mt-2" :message="form.errors.street" />
                </div>

                <!-- Last Name -->
                <div>
                  <InputLabel for="last_name" value="Name *" />
                  <TextInput
                    id="last_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.last_name"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.last_name" />
                </div>

                <!-- First Name -->
                <div>
                  <InputLabel for="first_name" value="Vorname" />
                  <TextInput
                    id="first_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.first_name"
                  />
                  <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <!-- Email -->
                <div>
                  <InputLabel for="email" value="E-Mail *" />
                  <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <!-- Class -->
                <div>
                  <InputLabel for="class" value="Klasse (z.B. IT23x)" />
                  <TextInput
                    id="class"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.class"
                  />
                  <InputError class="mt-2" :message="form.errors.class" />
                </div>

                <!-- Phone -->
                <div>
                  <InputLabel for="phone" value="Handynummer" />
                  <TextInput
                    id="phone"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                  />
                  <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <!-- Valid From -->
                <div>
                  <InputLabel for="valid_from" value="Gültig ab" />
                  <TextInput
                    id="valid_from"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.valid_from"
                  />
                  <InputError class="mt-2" :message="form.errors.valid_from" />
                </div>

                <!-- Valid Until -->
                <div>
                  <InputLabel for="valid_until" value="Gültig bis" />
                  <TextInput
                    id="valid_until"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.valid_until"
                  />
                  <InputError class="mt-2" :message="form.errors.valid_until" />
                </div>
              </div>

              <!-- Cost Info -->
              <div class="mt-6">
                <InputLabel for="cost_info" value="Kostenbeteiligung" />
                <TextArea
                  id="cost_info"
                  class="mt-1 block w-full"
                  v-model="form.cost_info"
                  rows="3"
                />
                <InputError class="mt-2" :message="form.errors.cost_info" />
              </div>

              <!-- Additional Info -->
              <div class="mt-6">
                <InputLabel for="additional_info" value="Sonstige Informationen" />
                <TextArea
                  id="additional_info"
                  class="mt-1 block w-full"
                  v-model="form.additional_info"
                  rows="3"
                />
                <InputError class="mt-2" :message="form.errors.additional_info" />
              </div>

              <div class="flex items-center justify-between mt-6">
                <button
                  type="button"
                  @click="confirmDelete"
                  class="inline-flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md shadow-sm transition-colors"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  Löschen
                </button>

                <div class="flex items-center">
                  <Link
                    :href="route('ride-offers.index')"
                    class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md shadow-sm transition-colors"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Abbrechen
                  </Link>
                  <PrimaryButton
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Änderungen speichern
                  </PrimaryButton>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
