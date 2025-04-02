<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search as SearchIcon } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import TextArea from '@/components/TextArea.vue';
import { debounce } from '@/utils/debounce';

const form = useForm({
  zip_code: '',
  city: '',
  street: '',
  last_name: '',
  first_name: '',
  email: '',
  class: '',
  phone: '',
  valid_from: '',
  valid_until: '',
  cost_info: '',
  additional_info: '',
});

// Address search functionality
const addressQuery = ref('');
const addressSuggestions = ref([]);
const isSearching = ref(false);
const showSuggestions = ref(false);

// Using the imported debounce utility function to limit API calls

// Search for address suggestions using our backend API (which proxies to Nominatim)
const searchAddress = debounce(async (query) => {
  if (!query || query.length < 3) {
    addressSuggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  isSearching.value = true;
  showSuggestions.value = true;

  try {
    const response = await fetch(`${route('api.geocode')}?query=${encodeURIComponent(query)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (response.ok) {
      const data = await response.json();
      addressSuggestions.value = data;
    } else {
      addressSuggestions.value = [];
      console.error('Error from geocoding API:', response.status);
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

/**
 * Validates the form before submission
 * @returns {boolean} True if the form is valid, false otherwise
 */
const validateForm = () => {
  let isValid = true;
  const errors = {};

  // Required fields validation
  if (!form.zip_code.trim()) {
    errors.zip_code = 'PLZ ist erforderlich';
    isValid = false;
  } else if (form.zip_code.length > 10) {
    errors.zip_code = 'PLZ darf maximal 10 Zeichen lang sein';
    isValid = false;
  }

  if (!form.city.trim()) {
    errors.city = 'Ort ist erforderlich';
    isValid = false;
  } else if (form.city.length > 255) {
    errors.city = 'Ort darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  if (!form.last_name.trim()) {
    errors.last_name = 'Name ist erforderlich';
    isValid = false;
  } else if (form.last_name.length > 255) {
    errors.last_name = 'Name darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  if (!form.email.trim()) {
    errors.email = 'E-Mail ist erforderlich';
    isValid = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
    isValid = false;
  } else if (form.email.length > 255) {
    errors.email = 'E-Mail darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  // Optional fields length validation
  if (form.street && form.street.length > 255) {
    errors.street = 'Straße darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  if (form.first_name && form.first_name.length > 255) {
    errors.first_name = 'Vorname darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  if (form.class && form.class.length > 255) {
    errors.class = 'Klasse darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  if (form.phone && form.phone.length > 255) {
    errors.phone = 'Handynummer darf maximal 255 Zeichen lang sein';
    isValid = false;
  }

  // Date validation
  if (form.valid_from && form.valid_until) {
    const fromDate = new Date(form.valid_from);
    const untilDate = new Date(form.valid_until);

    if (fromDate > untilDate) {
      errors.valid_until = 'Das Enddatum muss nach dem Startdatum liegen';
      isValid = false;
    }
  }

  // Set errors on the form
  form.clearErrors();
  if (!isValid) {
    Object.entries(errors).forEach(([key, value]) => {
      form.setError(key, value as string);
    });
  }

  return isValid;
};

const submit = () => {
  if (validateForm()) {
    form.post(route('ride-offers.store'));
  }
};
</script>

<template>
  <Head title="Mitfahrgelegenheit anbieten" />

  <AppLayout :breadcrumbs="[
    { name: 'Home', href: route('home') },
    { name: 'Mitfahrgelegenheit anbieten', href: route('ride-offers.create') }
  ]">

    <div class="py-12 relative">
      <!-- Decorative background elements -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-64 right-1/4 h-96 w-96 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="absolute top-1/3 -left-24 h-64 w-64 rounded-full bg-secondary/5 blur-3xl"></div>
      </div>

      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 relative z-10">
        <!-- Modern card with gradient and shadow -->
        <div class="gradient-card modern-shadow overflow-hidden rounded-xl">
          <div class="p-8 border-b border-gray-100/20 dark:border-gray-700/20">
            <form @submit.prevent="submit">
              <!-- Modern form header -->
              <div class="mb-8">
                <h2 class="text-2xl font-bold bg-gradient-to-r from-primary to-primary-accent bg-clip-text text-transparent">Mitfahrgelegenheit anbieten</h2>
                <p class="text-muted-foreground mt-2">Füllen Sie das Formular aus, um eine neue Mitfahrgelegenheit anzubieten.</p>
              </div>

              <!-- General error messages -->
              <div v-if="form.errors.rate_limit" class="mb-6 p-4 bg-destructive/10 dark:bg-destructive/20 border border-destructive/20 dark:border-destructive/30 rounded-lg text-destructive dark:text-destructive-foreground animated-bg">
                <div class="flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-destructive flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  <span class="font-medium">{{ form.errors.rate_limit }}</span>
                </div>
              </div>

              <!-- Address Search with modern styling -->
              <div class="col-span-full mb-8">
                <div class="flex items-center justify-between mb-2">
                  <InputLabel for="address_search" value="Adresse suchen" class="text-base font-medium text-foreground" />
                  <span class="text-xs px-2 py-1 rounded-full bg-primary/10 text-primary font-medium">Empfohlen</span>
                </div>

                <div class="relative">
                  <div class="flex">
                    <div class="relative flex-grow group">
                      <TextInput
                        id="address_search"
                        type="text"
                        class="mt-1 block w-full pr-10 rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                        v-model="addressQuery"
                        placeholder="Geben Sie eine Adresse ein (z.B. Straße, Stadt, PLZ)"
                        autocomplete="off"
                        @focus="showSuggestions = addressSuggestions.length > 0"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <SearchIcon class="h-5 w-5 text-gray-400 group-focus-within:text-primary transition-colors duration-200" />
                      </div>
                    </div>
                  </div>

                  <!-- Address Suggestions Dropdown with improved styling -->
                  <div v-if="showSuggestions && addressSuggestions.length > 0"
                       class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 max-h-60 overflow-auto modern-shadow">
                    <div v-if="isSearching" class="p-4 text-center text-gray-500 dark:text-gray-400">
                      <svg class="animate-spin h-6 w-6 mx-auto text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <p class="mt-2 font-medium">Suche läuft...</p>
                    </div>
                    <ul v-else class="py-1">
                      <li v-for="(suggestion, index) in addressSuggestions" :key="index"
                          class="px-4 py-3 hover:bg-primary/5 dark:hover:bg-primary/10 cursor-pointer transition-colors duration-150"
                          @click="selectAddress(suggestion)">
                        <div class="font-medium text-foreground">{{ suggestion.display_name }}</div>
                        <div class="text-sm text-muted-foreground mt-1 flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-primary" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                          </svg>
                          {{ suggestion.street ? suggestion.street + ', ' : '' }}{{ suggestion.zip_code }} {{ suggestion.city }}
                        </div>
                      </li>
                    </ul>
                  </div>
                </div>

                <div class="mt-2 flex items-start">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary/70 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                  <p class="text-sm text-muted-foreground">
                    Suchen Sie nach einer Adresse, um die Felder automatisch auszufüllen. Dies erleichtert die Eingabe und verbessert die Genauigkeit.
                  </p>
                </div>
              </div>

              <!-- Form fields with modern styling -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Address section with subtle grouping -->
                <div class="md:col-span-2 mb-2">
                  <h3 class="text-lg font-semibold text-foreground mb-1">Adressinformationen</h3>
                  <div class="h-0.5 w-16 bg-gradient-to-r from-primary to-primary-accent rounded-full"></div>
                </div>

                <!-- ZIP Code -->
                <div class="group">
                  <InputLabel for="zip_code" value="PLZ *" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="zip_code"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.zip_code"
                    required
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.zip_code" />
                </div>

                <!-- City -->
                <div class="group">
                  <InputLabel for="city" value="Ort *" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="city"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.city"
                    required
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.city" />
                </div>

                <!-- Street -->
                <div class="md:col-span-2 group">
                  <InputLabel for="street" value="Straße, Hausnummer" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="street"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.street"
                    placeholder="z.B. Hauptstraße 123"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.street" />
                </div>

                <!-- Personal information section -->
                <div class="md:col-span-2 mt-6 mb-2">
                  <h3 class="text-lg font-semibold text-foreground mb-1">Persönliche Informationen</h3>
                  <div class="h-0.5 w-16 bg-gradient-to-r from-primary to-primary-accent rounded-full"></div>
                </div>

                <!-- Last Name -->
                <div class="group">
                  <InputLabel for="last_name" value="Name *" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="last_name"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.last_name"
                    required
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.last_name" />
                </div>

                <!-- First Name -->
                <div class="group">
                  <InputLabel for="first_name" value="Vorname" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="first_name"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.first_name"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.first_name" />
                </div>

                <!-- Email -->
                <div class="group">
                  <InputLabel for="email" value="E-Mail *" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="email"
                    type="email"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.email"
                    placeholder="beispiel@email.de"
                    required
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.email" />
                </div>

                <!-- Phone -->
                <div class="group">
                  <InputLabel for="phone" value="Handynummer" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="phone"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.phone"
                    placeholder="z.B. 0170 1234567"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.phone" />
                </div>

                <!-- Class -->
                <div class="group">
                  <InputLabel for="class" value="Klasse (z.B. IT23x)" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="class"
                    type="text"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.class"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.class" />
                </div>

                <!-- Availability section -->
                <div class="md:col-span-2 mt-6 mb-2">
                  <h3 class="text-lg font-semibold text-foreground mb-1">Verfügbarkeit</h3>
                  <div class="h-0.5 w-16 bg-gradient-to-r from-primary to-primary-accent rounded-full"></div>
                </div>

                <!-- Valid From -->
                <div class="group">
                  <InputLabel for="valid_from" value="Gültig ab" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="valid_from"
                    type="date"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.valid_from"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.valid_from" />
                </div>

                <!-- Valid Until -->
                <div class="group">
                  <InputLabel for="valid_until" value="Gültig bis" class="text-sm font-medium flex items-center" />
                  <TextInput
                    id="valid_until"
                    type="date"
                    class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                    v-model="form.valid_until"
                  />
                  <InputError class="mt-1.5 text-sm" :message="form.errors.valid_until" />
                </div>
              </div>

              <!-- Additional information section -->
              <div class="md:col-span-2 mt-8 mb-2">
                <h3 class="text-lg font-semibold text-foreground mb-1">Zusätzliche Informationen</h3>
                <div class="h-0.5 w-16 bg-gradient-to-r from-primary to-primary-accent rounded-full"></div>
              </div>

              <!-- Cost Info -->
              <div class="mt-4 md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                  <InputLabel for="cost_info" value="Kostenbeteiligung" class="text-sm font-medium" />
                  <span class="text-xs text-muted-foreground">Optional</span>
                </div>
                <TextArea
                  id="cost_info"
                  class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                  v-model="form.cost_info"
                  rows="3"
                  placeholder="Beschreiben Sie hier Ihre Vorstellungen zur Kostenbeteiligung..."
                />
                <InputError class="mt-1.5 text-sm" :message="form.errors.cost_info" />
              </div>

              <!-- Additional Info -->
              <div class="mt-6 md:col-span-2">
                <div class="flex items-center justify-between mb-2">
                  <InputLabel for="additional_info" value="Sonstige Informationen" class="text-sm font-medium" />
                  <span class="text-xs text-muted-foreground">Optional</span>
                </div>
                <TextArea
                  id="additional_info"
                  class="mt-1.5 block w-full rounded-lg border-gray-200 dark:border-gray-700 focus:border-primary focus:ring focus:ring-primary/20 transition-all duration-200"
                  v-model="form.additional_info"
                  rows="3"
                  placeholder="Weitere Informationen zu Ihrer Mitfahrgelegenheit..."
                />
                <InputError class="mt-1.5 text-sm" :message="form.errors.additional_info" />
              </div>

              <!-- Form actions with modern styling -->
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end mt-10 pt-6 border-t border-gray-100/20 dark:border-gray-700/20">
                <Link
                  :href="route('home')"
                  class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-muted hover:bg-muted/80 text-muted-foreground font-medium rounded-lg shadow-sm transition-all duration-200 hover-scale mb-3 sm:mb-0"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                  </svg>
                  Abbrechen
                </Link>
                <PrimaryButton
                  class="w-full sm:w-auto sm:ml-4 gradient-primary button-glow px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-300"
                  :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
                  :disabled="form.processing"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  <span v-if="form.processing">Wird gespeichert...</span>
                  <span v-else>Mitfahrgelegenheit anbieten</span>
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
