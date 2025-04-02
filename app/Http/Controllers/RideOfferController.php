<?php

namespace App\Http\Controllers;

use App\Http\Requests\RideOfferRequest;
use App\Models\RideOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RideOfferController extends Controller
{
    /**
     * Display a listing of the ride offers.
     */
    public function index()
    {
        return Inertia::render('RideOffers/Index', [
            'rideOffers' => RideOffer::latest()->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new ride offer.
     */
    public function create()
    {
        return Inertia::render('RideOffers/Create');
    }

    /**
     * Store a newly created ride offer in storage.
     */
    public function store(RideOfferRequest $request)
    {
        // Rate limiting: Allow only 5 ride offers per hour per IP address
        // This helps prevent mass fake offers
        $rateLimitKey = 'ride-offers-create:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return back()
                ->withInput()
                ->withErrors(['rate_limit' => 'Zu viele Anfragen. Bitte versuchen Sie es in ' . $seconds . ' Sekunden erneut.']);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 3600); // Remember for 1 hour

        $validated = $request->validated();

        // Get coordinates for the address
        $coordinates = $this->getCoordinates($validated['zip_code'], $validated['city'], $validated['street'] ?? null);

        if ($coordinates) {
            $validated['latitude'] = $coordinates['latitude'];
            $validated['longitude'] = $coordinates['longitude'];
        }

        $rideOffer = RideOffer::create($validated);

        return redirect()->route('ride-offers.confirmation', ['edit_code' => $rideOffer->edit_code])
            ->with('success', 'Ride offer created successfully!');
    }

    /**
     * Display the specified ride offer.
     */
    public function show(RideOffer $rideOffer)
    {
        return Inertia::render('RideOffers/Show', [
            'rideOffer' => $rideOffer,
        ]);
    }

    /**
     * Show the form for editing the specified ride offer.
     */
    public function edit($editCode)
    {
        $rideOffer = RideOffer::where('edit_code', $editCode)->firstOrFail();

        return Inertia::render('RideOffers/Edit', [
            'rideOffer' => $rideOffer,
        ]);
    }

    /**
     * Update the specified ride offer in storage.
     */
    public function update(RideOfferRequest $request, $editCode)
    {
        // Rate limiting: Allow only 5 ride offer updates per hour per IP address
        // This helps prevent mass fake updates
        $rateLimitKey = 'ride-offers-update:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return back()
                ->withInput()
                ->withErrors(['rate_limit' => 'Zu viele Anfragen. Bitte versuchen Sie es in ' . $seconds . ' Sekunden erneut.']);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 3600); // Remember for 1 hour

        $rideOffer = RideOffer::where('edit_code', $editCode)->firstOrFail();

        $validated = $request->validated();

        // Get coordinates for the address if it changed
        if ($rideOffer->zip_code !== $validated['zip_code'] ||
            $rideOffer->city !== $validated['city'] ||
            $rideOffer->street !== ($validated['street'] ?? null)) {

            $coordinates = $this->getCoordinates($validated['zip_code'], $validated['city'], $validated['street'] ?? null);

            if ($coordinates) {
                $validated['latitude'] = $coordinates['latitude'];
                $validated['longitude'] = $coordinates['longitude'];
            }
        }

        $rideOffer->update($validated);

        return redirect()->route('ride-offers.confirmation', ['edit_code' => $rideOffer->edit_code])
            ->with('success', 'Ride offer updated successfully!');
    }

    /**
     * Remove the specified ride offer from storage.
     */
    public function destroy(Request $request, $editCode)
    {
        // Rate limiting: Allow only 5 ride offer deletions per hour per IP address
        // This helps prevent mass fake deletions
        $rateLimitKey = 'ride-offers-delete:' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($rateLimitKey);
            return back()
                ->withErrors(['rate_limit' => 'Zu viele Anfragen. Bitte versuchen Sie es in ' . $seconds . ' Sekunden erneut.']);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($rateLimitKey, 3600); // Remember for 1 hour

        $rideOffer = RideOffer::where('edit_code', $editCode)->firstOrFail();
        $rideOffer->delete();

        return redirect()->route('ride-offers.index')
            ->with('success', 'Ride offer deleted successfully!');
    }

    /**
     * Show the confirmation page with the edit code.
     */
    public function confirmation($editCode)
    {
        $rideOffer = RideOffer::where('edit_code', $editCode)->firstOrFail();

        return Inertia::render('RideOffers/Confirmation', [
            'rideOffer' => $rideOffer,
            'editCode' => $editCode,
        ]);
    }

    /**
     * Search for ride offers by ZIP code and city.
     */
    public function search(Request $request)
    {
        // If no search parameters are provided, just render the search form
        if (!$request->has('zip_code') && !$request->has('city')) {
            return Inertia::render('RideOffers/Search', [
                'rideOffers' => [],
                'searchParams' => ['radius' => 5], // Default radius
                'searchCoordinates' => null,
            ]);
        }

        // Validate the search parameters
        $validated = $request->validate([
            'zip_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'radius' => 'nullable|integer|min:1|max:100',
        ]);

        // Ensure radius is set and is an integer
        $radius = isset($validated['radius']) ? (int)$validated['radius'] : 5;
        $validated['radius'] = $radius;

        $query = RideOffer::query();

        // Get coordinates for the search location
        $coordinates = $this->getCoordinates($validated['zip_code'], $validated['city']);

        // Always ensure we have coordinates for the map
        $query->whereNotNull('latitude')
              ->whereNotNull('longitude');

        // If we have coordinates and radius is greater than 0, perform radius search
        if ($coordinates && $radius > 0) {
            $latitude = (float)$coordinates['latitude'];
            $longitude = (float)$coordinates['longitude'];

            // Haversine formula to calculate distance in kilometers
            // 6371 is the Earth's radius in kilometers
            $distanceFormula = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

            // For SQLite compatibility, we need to use the full formula in the WHERE clause
            $query->selectRaw("*, {$distanceFormula} AS distance", [$latitude, $longitude, $latitude])
                  ->whereRaw("{$distanceFormula} <= ?", [$latitude, $longitude, $latitude, $radius])
                  ->orderBy('distance');

            \Log::info("Performing radius search with coordinates: {$latitude}, {$longitude}, radius: {$radius}km");
        } else {
            // Fallback search if no coordinates or radius is 0
            \Log::info("Performing fallback search without radius");

            // More targeted search by ZIP code and city
            $query->where(function($q) use ($validated) {
                // Try to match both ZIP code and city if possible
                $q->where(function($subQ) use ($validated) {
                    $subQ->where('zip_code', $validated['zip_code'])
                         ->where('city', 'LIKE', '%' . $validated['city'] . '%');
                })
                // OR match ZIP code exactly
                ->orWhere('zip_code', $validated['zip_code'])
                // OR match city with a more strict LIKE
                ->orWhere('city', 'LIKE', $validated['city'] . '%');
            });
        }

        $rideOffers = $query->get();

        \Log::info("Search found " . count($rideOffers) . " results");

        // Add search coordinates to the response for map centering
        $searchCoordinates = $coordinates ?: null;

        // If this is an AJAX request, return JSON response
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'rideOffers' => $rideOffers,
                'searchParams' => $validated,
                'searchCoordinates' => $searchCoordinates,
            ]);
        }

        // Otherwise, return the Inertia view
        return Inertia::render('RideOffers/Search', [
            'rideOffers' => $rideOffers,
            'searchParams' => $validated,
            'searchCoordinates' => $searchCoordinates,
        ]);
    }

    /**
     * API endpoint for searching ride offers (AJAX)
     */
    public function searchApi(Request $request)
    {
        // Validate the search parameters
        $validated = $request->validate([
            'zip_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'radius' => 'nullable|integer|min:1|max:100',
        ]);

        // Ensure radius is set and is an integer
        $radius = isset($validated['radius']) ? (int)$validated['radius'] : 5;
        $validated['radius'] = $radius;

        $query = RideOffer::query();

        // Get coordinates for the search location
        $coordinates = $this->getCoordinates($validated['zip_code'], $validated['city']);

        // Always ensure we have coordinates for the map
        $query->whereNotNull('latitude')
              ->whereNotNull('longitude');

        // If we have coordinates and radius is greater than 0, perform radius search
        if ($coordinates && $radius > 0) {
            $latitude = (float)$coordinates['latitude'];
            $longitude = (float)$coordinates['longitude'];

            // Haversine formula to calculate distance in kilometers
            // 6371 is the Earth's radius in kilometers
            $distanceFormula = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

            // For SQLite compatibility, we need to use the full formula in the WHERE clause
            $query->selectRaw("*, {$distanceFormula} AS distance", [$latitude, $longitude, $latitude])
                  ->whereRaw("{$distanceFormula} <= ?", [$latitude, $longitude, $latitude, $radius])
                  ->orderBy('distance');

            \Log::info("API: Performing radius search with coordinates: {$latitude}, {$longitude}, radius: {$radius}km");
        } else {
            // Fallback search if no coordinates or radius is 0
            \Log::info("API: Performing fallback search without radius");

            // More targeted search by ZIP code and city
            $query->where(function($q) use ($validated) {
                // Try to match both ZIP code and city if possible
                $q->where(function($subQ) use ($validated) {
                    $subQ->where('zip_code', $validated['zip_code'])
                         ->where('city', 'LIKE', '%' . $validated['city'] . '%');
                })
                // OR match ZIP code exactly
                ->orWhere('zip_code', $validated['zip_code'])
                // OR match city with a more strict LIKE
                ->orWhere('city', 'LIKE', $validated['city'] . '%');
            });
        }

        $rideOffers = $query->get();

        \Log::info("API: Search found " . count($rideOffers) . " results");

        // Return JSON response
        return response()->json([
            'rideOffers' => $rideOffers,
            'searchParams' => $validated,
            'searchCoordinates' => $coordinates ?: null,
        ]);
    }

    /**
     * Display an overview of all ride offers on a map.
     */
    public function overview()
    {
        // Get all ride offers with coordinates
        $rideOffers = RideOffer::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return Inertia::render('RideOffers/Overview', [
            'rideOffers' => $rideOffers,
        ]);
    }

    /**
     * API endpoint for geocoding addresses
     */
    public function geocode(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:255',
        ]);

        $query = $request->input('query');

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Projektwoche/1.0'
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'limit' => 5,
                'countrycodes' => 'de',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $results = collect($data)->map(function ($item) {
                    return [
                        'display_name' => $item['display_name'],
                        'street' => isset($item['address']['road'])
                            ? $item['address']['road'] . (isset($item['address']['house_number']) ? ' ' . $item['address']['house_number'] : '')
                            : '',
                        'city' => $item['address']['city'] ?? $item['address']['town'] ?? $item['address']['village'] ?? $item['address']['municipality'] ?? '',
                        'zip_code' => $item['address']['postcode'] ?? '',
                        'latitude' => $item['lat'],
                        'longitude' => $item['lon'],
                    ];
                });

                return response()->json($results);
            }

            return response()->json([], 200);
        } catch (\Exception $e) {
            \Log::error('Geocoding API error: ' . $e->getMessage());
            return response()->json(['error' => 'Geocoding service unavailable'], 503);
        }
    }

    /**
     * Get coordinates (latitude, longitude) for an address using OpenStreetMap Nominatim API.
     * Includes retry mechanism for temporary failures.
     */
    private function getCoordinates($zipCode, $city, $street = null)
    {
        $maxRetries = 3;
        $retryDelay = 1000; // milliseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Projektwoche/1.0'
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'street' => $street,
                    'city' => $city,
                    'postalcode' => $zipCode,
                    'format' => 'json',
                    'limit' => 1,
                ]);

                if ($response->successful() && count($response->json()) > 0) {
                    $data = $response->json()[0];
                    return [
                        'latitude' => $data['lat'],
                        'longitude' => $data['lon'],
                    ];
                }

                // If we get here, the request was successful but no results were found
                if ($response->successful()) {
                    \Log::info("No geocoding results found for: $street, $city, $zipCode");
                    return null;
                }

                // If we get here, the request failed with an error status
                \Log::warning("Geocoding attempt $attempt failed with status: " . $response->status());

            } catch (\Exception $e) {
                \Log::warning("Geocoding attempt $attempt failed with exception: " . $e->getMessage());
            }

            // Only sleep if we're going to retry
            if ($attempt < $maxRetries) {
                usleep($retryDelay * 1000); // Convert to microseconds
                // Exponential backoff
                $retryDelay *= 2;
            }
        }

        \Log::error("Geocoding failed after $maxRetries attempts for: $street, $city, $zipCode");
        return null;
    }
}
