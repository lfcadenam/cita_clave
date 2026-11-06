<?php

use App\Http\Controllers\Api\v1\AppointmentBookingController;
use App\Http\Controllers\Api\v1\AvailabilityController;
use App\Http\Controllers\Api\v1\IntegrationController;
use App\Http\Controllers\Api\v1\MetaWhatsAppWebhookController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ServiceController;
use App\Http\Controllers\Api\v1\TenantProfileController;
use App\Http\Controllers\Api\v1\WaitlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Nuvex Agendamiento Belleza & Estética
| Hardened Security & Rate Limiting (La Belle Nails Bogotá)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // 1. ENDPOINTS PÚBLICOS EXENTOS DE API KEY (Health check & Webhooks externos)
    // =========================================================================
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'Nuvex Agendamiento Belleza',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Webhooks de pasarela y mensajería oficial
    Route::post('/payments/bold/webhook', [PaymentController::class, 'handleBoldWebhook']);
    Route::get('/webhooks/whatsapp', [MetaWhatsAppWebhookController::class, 'verify']);
    Route::post('/webhooks/whatsapp', [MetaWhatsAppWebhookController::class, 'handle']);

    // =========================================================================
    // 2. ENDPOINTS FRONTEND PROTEGIDOS CON API KEY Y RATE LIMITING
    // =========================================================================
    Route::middleware(['api.key'])->group(function () {

        // --- Catálogo, Disponibilidad e Información de Pago (120 peticiones/minuto) ---
        Route::middleware(['throttle:api-public'])->group(function () {
            Route::get('/tenant/profile', [TenantProfileController::class, 'show']);
            Route::get('/services', [ServiceController::class, 'index']);
            Route::get('/services/{id}', [ServiceController::class, 'show']);
            Route::get('/availability/slots', [AvailabilityController::class, 'slots']);
            Route::get('/availability/month', [AvailabilityController::class, 'month']);
            Route::get('/payments/nequi-info', [PaymentController::class, 'getNequiInfo']);
        });

        // --- Endpoints Sensibles (60 peticiones/minuto) ---
        Route::middleware(['throttle:api-sensitive'])->group(function () {
            // Consulta de clientes y apartado
            Route::get('/clients/lookup', [AppointmentBookingController::class, 'lookupClient']);
            Route::post('/appointments/hold', [AppointmentBookingController::class, 'hold']);
            Route::post('/appointments/book', [AppointmentBookingController::class, 'book']);
            Route::get('/appointments/status/{appointmentNumber}', [AppointmentBookingController::class, 'status']);

            // Confirmación y Autogestión con Token de Seguridad
            Route::post('/appointments/confirm-attendance/{appointmentNumber}', [AppointmentBookingController::class, 'confirmAttendanceApi']);
            Route::post('/appointments/{appointmentNumber}/cancel', [AppointmentBookingController::class, 'cancelAppointmentApi']);
            Route::post('/appointments/{appointmentNumber}/reschedule', [AppointmentBookingController::class, 'rescheduleAppointmentApi']);

            // Pasarela de Pago
            Route::post('/payments/bold/checkout', [PaymentController::class, 'createBoldCheckout']);

            // Lista de espera
            Route::post('/waitlist/join', [WaitlistController::class, 'join']);
        });

    });

    // =========================================================================
    // 3. INTEGRACIONES EXTERNAS (n8n / Workflows Automatizados)
    // =========================================================================
    Route::prefix('integrations')->middleware(['api.key:integrations', 'throttle:api-integrations'])->group(function () {
        // n8n obtiene las citas del día para enviar recordatorios automáticos
        Route::get('/appointments/upcoming', [IntegrationController::class, 'upcomingAppointments']);

        // n8n confirma asistencia tras interacción en WhatsApp
        Route::post('/appointments/{appointmentNumber}/confirm', [IntegrationController::class, 'confirmAttendance']);

        // n8n reporta que envió el recordatorio
        Route::post('/appointments/{appointmentNumber}/notified', [IntegrationController::class, 'markNotified']);
    });

});
