<?php
// routes/api.php - Update the flight-offers and selected-flights sections

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Auth\AuthController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Api\AirportController;
use App\Http\Controllers\Api\FlightSearchController;
use App\Http\Controllers\Api\FlightOffersController;
use App\Http\Controllers\Api\SelectedFlightController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-password-reset-link', [AuthController::class, 'sendPasswordResetLink'])->name('send-password-reset-link');
Route::get('/check-reset-token/{token}', [AuthController::class, 'checkResetToken'])->name('check-reset-token');
Route::post('/reset-password', [AuthController::class, 'resetPasswordhandler'])->name('reset-password');
Route::get('/verify-account/{token}', [AuthController::class, 'verifyAccount'])->name('verify-account');
Route::post('/resend-verification', [AuthController::class, 'resendVerificationEmail']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [ProfileController::class, 'getProfile']);
    Route::post('/user/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/user/update-profile', [ProfileController::class, 'updateProfile']);

    // Airport routes
    Route::get('/airports/search', [AirportController::class, 'search'])->name('airports.search');

    // Flight search routes
    Route::prefix('flight-searches')->name('flight-searches.')->group(function () {
        Route::post('/', [FlightSearchController::class, 'store'])->name('store');
        Route::get('/', [FlightSearchController::class, 'index'])->name('index');
        Route::get('/recent', [FlightSearchController::class, 'recent'])->name('recent');
        Route::get('/{flightSearch}', [FlightSearchController::class, 'show'])->name('show');
        Route::delete('/{flightSearch}', [FlightSearchController::class, 'destroy'])->name('destroy');
    });

    // Flight Offers routes (Duffel Integration)
    Route::prefix('flight-offers')->name('flight-offers.')->group(function () {
        // Search flights via Duffel API
        Route::post('/search', [FlightOffersController::class, 'search'])->name('search');
        
        // Get single offer details
        Route::get('/{offerId}', [FlightOffersController::class, 'show'])
            ->name('show')
            ->where('offerId', '[A-Za-z0-9_-]+');
        
        // Refresh offer pricing
        Route::post('/{offerId}/refresh', [FlightOffersController::class, 'refreshOffer'])->name('refresh');
        
        // NOTE: selectOffer method has been moved to SelectedFlightController
    });

    // Selected Flights routes (NEW - for saving selected flights)
    Route::prefix('selected-flights')->name('selected-flights.')->group(function () {
        // Select and save a flight
        Route::post('/', [SelectedFlightController::class, 'store'])->name('store');
        
        // Get selected flight details
        Route::get('/{id}', [SelectedFlightController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');
    });

    // Booking routes
    Route::prefix('bookings')->name('bookings.')->group(function () {
        // Create a new booking
        Route::post('/{selectedFlightId}', [BookingController::class, 'store'])
            ->name('store')
            ->where('selectedFlightId', '[0-9]+');
        
        // Get all bookings for user
        Route::get('/', [BookingController::class, 'index'])->name('index');
        
        // Get booking details
        Route::get('/{id}', [BookingController::class, 'show'])
            ->name('show')
            ->where('id', '[0-9]+');
        
        // Cancel booking
        Route::post('/{id}/cancel', [BookingController::class, 'cancel'])
            ->name('cancel')
            ->where('id', '[0-9]+');


            Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::post('/{selectedFlightId}', [BookingController::class, 'store'])->name('store');
    Route::get('/{id}', [BookingController::class, 'show'])->name('show');
    Route::put('/{id}', [BookingController::class, 'update'])->name('update');
    Route::post('/{id}/cancel', [BookingController::class, 'cancel'])->name('cancel');
    Route::delete('/{id}', [BookingController::class, 'destroy'])->name('destroy');
    Route::patch('/{id}/status', [BookingController::class, 'updateStatus'])->name('update-status');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});