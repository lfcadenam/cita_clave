<?php

use App\Http\Controllers\Api\v1\Admin\AppointmentAdminController;
use App\Http\Controllers\Api\v1\Admin\BlockedSlotAdminController;
use App\Http\Controllers\Api\v1\Admin\CalendarAdminController;
use App\Http\Controllers\Api\v1\Admin\CustomerAdminController;
use App\Http\Controllers\Api\v1\Admin\ServiceAdminController;
use App\Http\Controllers\Api\v1\Admin\WaitlistAdminController;
use App\Http\Controllers\Api\v1\Admin\WhatsAppAdminController;
use App\Http\Controllers\Api\v1\Admin\WhatsAppBroadcastAdminController;
use App\Http\Controllers\Api\v1\Admin\WorkingScheduleAdminController;
use App\Http\Controllers\Api\v1\AppointmentBookingController;
use App\Http\Controllers\Api\v1\Auth\AuthController;
use App\Http\Controllers\Api\v1\AvailabilityController;
use App\Http\Controllers\Api\v1\IntegrationController;
use App\Http\Controllers\Api\v1\MetaWhatsAppWebhookController;
use App\Http\Controllers\Api\v1\PaymentController;
use App\Http\Controllers\Api\v1\ServiceController;
use App\Http\Controllers\Api\v1\SuperAdmin\SuperAdminController;
use App\Http\Controllers\Api\v1\TenantProfileController;
use App\Http\Controllers\Api\v1\WaitlistController;
use App\Http\Middleware\IdentifyTenantMiddleware;
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

    // =========================================================================
    // 4. AUTENTICACIÓN Y ADMINISTRACIÓN HEADLESS (Angular Admin SPA)
    // =========================================================================
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::prefix('admin')->middleware(['auth:sanctum', IdentifyTenantMiddleware::class])->group(function () {
        // Citas / Agenda
        Route::get('/appointments', [AppointmentAdminController::class, 'index']);
        Route::post('/appointments', [AppointmentAdminController::class, 'store']);
        Route::get('/appointments/{id}', [AppointmentAdminController::class, 'show']);
        Route::put('/appointments/{id}', [AppointmentAdminController::class, 'update']);
        Route::patch('/appointments/{id}/status', [AppointmentAdminController::class, 'updateStatus']);
        Route::post('/appointments/{id}/approve-nequi', [AppointmentAdminController::class, 'approveNequi']);
        Route::post('/appointments/{id}/reject-nequi', [AppointmentAdminController::class, 'rejectNequi']);

        // Calendario y Disponibilidad Administrativa
        Route::get('/calendar/slots', [CalendarAdminController::class, 'slots']);
        Route::get('/calendar/summary', [CalendarAdminController::class, 'summary']);

        // Clientes (CRM)
        Route::get('/customers', [CustomerAdminController::class, 'index']);
        Route::post('/customers', [CustomerAdminController::class, 'store']);
        Route::get('/customers/{id}', [CustomerAdminController::class, 'show']);
        Route::put('/customers/{id}', [CustomerAdminController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerAdminController::class, 'destroy']);
        Route::post('/customers/{id}/toggle-active', [CustomerAdminController::class, 'toggleActive']);

        // Servicios / Catálogo
        Route::get('/services', [ServiceAdminController::class, 'index']);
        Route::post('/services', [ServiceAdminController::class, 'store']);
        Route::get('/services/{id}', [ServiceAdminController::class, 'show']);
        Route::put('/services/{id}', [ServiceAdminController::class, 'update']);
        Route::post('/services/{id}', [ServiceAdminController::class, 'update']);
        Route::patch('/services/{id}/toggle-favorite', [ServiceAdminController::class, 'toggleFavorite']);
        Route::delete('/services/{id}', [ServiceAdminController::class, 'destroy']);

        // Jornadas y Horarios de Trabajo
        Route::get('/working-schedules', [WorkingScheduleAdminController::class, 'index']);
        Route::put('/working-schedules/{id}', [WorkingScheduleAdminController::class, 'update']);
        Route::post('/working-schedules/batch', [WorkingScheduleAdminController::class, 'batchUpdate']);

        // Bloqueos de Horario (Almuerzo / Vacaciones)
        Route::get('/blocked-slots', [BlockedSlotAdminController::class, 'index']);
        Route::post('/blocked-slots', [BlockedSlotAdminController::class, 'store']);
        Route::delete('/blocked-slots/{id}', [BlockedSlotAdminController::class, 'destroy']);

        // Lista de Espera
        Route::get('/waitlist', [WaitlistAdminController::class, 'index']);
        Route::patch('/waitlist/{id}/status', [WaitlistAdminController::class, 'updateStatus']);
        Route::delete('/waitlist/{id}', [WaitlistAdminController::class, 'destroy']);

        // Canal WhatsApp (Meta Cloud API)
        Route::get('/settings/whatsapp', [WhatsAppAdminController::class, 'getSettings']);
        Route::get('/settings/whatsapp/status', [WhatsAppAdminController::class, 'checkStatus']);
        Route::put('/settings/whatsapp', [WhatsAppAdminController::class, 'updateSettings']);
        Route::post('/settings/whatsapp/register-pin', [WhatsAppAdminController::class, 'registerPin']);
        Route::get('/settings/whatsapp/templates', [WhatsAppAdminController::class, 'syncTemplates']);
        Route::get('/settings/whatsapp/templates/catalog', [WhatsAppAdminController::class, 'templatesCatalog']);
        Route::post('/settings/whatsapp/templates/create', [WhatsAppAdminController::class, 'createTemplate']);
        Route::put('/settings/whatsapp/templates/{key}', [WhatsAppAdminController::class, 'updateTemplate']);
        Route::post('/settings/whatsapp/templates/{key}/reset', [WhatsAppAdminController::class, 'resetTemplate']);
        Route::post('/settings/whatsapp/test', [WhatsAppAdminController::class, 'sendTest']);

        // Campañas y Listas de Difusión (WhatsApp)
        Route::get('/broadcasts/audience', [WhatsAppBroadcastAdminController::class, 'audience']);
        Route::get('/broadcasts', [WhatsAppBroadcastAdminController::class, 'index']);
        Route::post('/broadcasts', [WhatsAppBroadcastAdminController::class, 'store']);
        Route::post('/broadcasts/test', [WhatsAppBroadcastAdminController::class, 'sendTest']);
        Route::get('/broadcasts/{id}', [WhatsAppBroadcastAdminController::class, 'show']);
    });

    // =========================================================================
    // 5. SUPER ADMINISTRADOR NUVEX (Multi-Tenant & Plataforma)
    // =========================================================================
    Route::prefix('super-admin')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/analytics', [SuperAdminController::class, 'analytics']);
        Route::get('/tenants', [SuperAdminController::class, 'tenants']);
        Route::post('/tenants', [SuperAdminController::class, 'createTenant']);
        Route::put('/tenants/{id}', [SuperAdminController::class, 'updateTenant']);
        Route::get('/users', [SuperAdminController::class, 'users']);
        Route::post('/users', [SuperAdminController::class, 'createUser']);
        Route::put('/users/{id}', [SuperAdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [SuperAdminController::class, 'deleteUser']);

        // Horario y Automatización de Recordatorios
        Route::get('/reminder-settings', [SuperAdminController::class, 'getReminderSettings']);
        Route::put('/reminder-settings', [SuperAdminController::class, 'updateReminderSettings']);
        Route::post('/reminder-settings/trigger-manual', [SuperAdminController::class, 'triggerManualReminders']);
    });

});

