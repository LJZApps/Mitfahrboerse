<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\RideOfferController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


// Ride Offers Routes
Route::get('/ride-offers', [RideOfferController::class, 'index'])->name('ride-offers.index');
Route::get('/ride-offers/create', [RideOfferController::class, 'create'])->name('ride-offers.create');
Route::post('/ride-offers', [RideOfferController::class, 'store'])->name('ride-offers.store');

// Edit routes using edit_code
Route::get('/ride-offers/edit/{editCode}', [RideOfferController::class, 'edit'])->name('ride-offers.edit');
Route::put('/ride-offers/{editCode}', [RideOfferController::class, 'update'])->name('ride-offers.update');
Route::delete('/ride-offers/{editCode}', [RideOfferController::class, 'destroy'])->name('ride-offers.destroy');

// Confirmation page with edit code
Route::get('/ride-offers/confirmation/{edit_code}', [RideOfferController::class, 'confirmation'])->name('ride-offers.confirmation');

// Show route - must be after other specific routes to avoid conflicts
Route::get('/ride-offers/{rideOffer}', [RideOfferController::class, 'show'])->name('ride-offers.show');

// Search routes
Route::get('/search', [RideOfferController::class, 'search'])->name('ride-offers.search');
Route::post('/search', [RideOfferController::class, 'search'])->name('ride-offers.search.post');

// API Search route for AJAX requests
Route::post('/api/search', [RideOfferController::class, 'searchApi'])->name('api.ride-offers.search');

// API Geocoding route
Route::get('/api/geocode', [RideOfferController::class, 'geocode'])->name('api.geocode');

// Overview route
Route::get('/overview', [RideOfferController::class, 'overview'])->name('ride-offers.overview');
