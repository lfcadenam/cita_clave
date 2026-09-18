<?php

use App\Http\Controllers\Api\v1\AppointmentBookingController;
use App\Http\Controllers\Api\v1\AvailabilityController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ServiceController;
use App\Http\Controllers\Api\v1\WaitlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Nuvex Agendamiento Belleza & Estética (Paola Aguilera)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Nuvex Agendamiento Belleza (Paola Aguilera)',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Catálogo de Servicios
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);

    // Motor de Disponibilidad Anti-Huecos
    Route::get('/availability/slots', [AvailabilityController::class, 'slots']);
    Route::get('/availability/month', [AvailabilityController::class, 'month']);

    // Proceso de Reserva y Apartado de Citas
    Route::get('/clients/lookup', [AppointmentBookingController::class, 'lookupClient']);
    Route::post('/appointments/hold', [AppointmentBookingController::class, 'hold']);
    Route::post('/appointments/book', [AppointmentBookingController::class, 'book']);
    Route::get('/appointments/status/{appointmentNumber}', [AppointmentBookingController::class, 'status']);

    // Módulo Híbrido de Pagos (Bold & Nequi Directo)
    Route::get('/payments/nequi-info', [PaymentController::class, 'getNequiInfo']);
    Route::post('/payments/bold/checkout', [PaymentController::class, 'createBoldCheckout']);
    Route::post('/payments/bold/webhook', [PaymentController::class, 'handleBoldWebhook']);

    // Lista de Espera Inteligente
    Route::post('/waitlist/join', [WaitlistController::class, 'join']);
});