<?php

use App\Http\Controllers\ClientBookingWebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Portal de Clientas (Paola Aguilera)
|--------------------------------------------------------------------------
*/

// Portal Principal de Reservas (Mobile-First / PWA)
Route::get('/', [ClientBookingWebController::class, 'index'])->name('portal.booking');

// Consulta de Estado de Reserva (Búsqueda por teléfono o código)
Route::get('/reserva/consulta', [ClientBookingWebController::class, 'lookup'])->name('portal.lookup');
Route::get('/consultar-cita', [ClientBookingWebController::class, 'lookup']);

// Confirmación y Voucher Digital
Route::get('/reserva/confirmacion/{appointmentNumber}', [ClientBookingWebController::class, 'confirmation'])->name('portal.confirmation');
Route::get('/citas/{appointmentNumber}', [ClientBookingWebController::class, 'confirmation'])->name('portal.citas');

// Confirmación Manual de Asistencia por la Clienta (POST desde botón o GET desde enlace directo de WhatsApp)
Route::get('/reserva/confirmar/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendanceViaLink'])->name('portal.confirm-attendance-link');
Route::get('/citas/confirmar/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendanceViaLink']);
Route::post('/reserva/confirmar-asistencia/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendance'])->name('portal.confirm-attendance');
Route::post('/citas/confirmar-asistencia/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendance']);

// Cancelación de Cita por la Clienta (>24h y motivo obligatorio)
Route::post('/reserva/cancelar/{appointmentNumber}', [ClientBookingWebController::class, 'cancelAppointment'])->name('portal.cancel-appointment');
Route::post('/citas/cancelar/{appointmentNumber}', [ClientBookingWebController::class, 'cancelAppointment']);
Route::post('/citas/{appointmentNumber}/cancel', [ClientBookingWebController::class, 'cancelAppointment']);

// Reagendamiento de Cita por la Clienta
Route::get('/reserva/reagendar/{appointmentNumber}', [ClientBookingWebController::class, 'showRescheduleForm'])->name('portal.reschedule-form');
Route::get('/citas/{appointmentNumber}/reagendar', [ClientBookingWebController::class, 'showRescheduleForm']);
Route::post('/reserva/reagendar/{appointmentNumber}', [ClientBookingWebController::class, 'rescheduleAppointment'])->name('portal.reschedule-appointment');
Route::post('/citas/{appointmentNumber}/reagendar', [ClientBookingWebController::class, 'rescheduleAppointment']);

// Descarga de Calendario (.ics)
Route::get('/reserva/calendar/{appointmentNumber}.ics', [ClientBookingWebController::class, 'downloadCalendar'])->name('portal.calendar');
Route::get('/citas/{appointmentNumber}/calendar', [ClientBookingWebController::class, 'downloadCalendar']);
Route::get('/citas/{appointmentNumber}/calendar.ics', [ClientBookingWebController::class, 'downloadCalendar']);

// SuperAdmin - Previsualización de Plantillas de Correo Electrónico
Route::get('/superadmin/email-preview/{key}', [\App\Filament\SuperAdmin\Pages\EmailTemplatesPage::class, 'renderStandalonePreview'])
    ->name('superadmin.email-preview');

// Redirección directa sin slug de tenant hacia la página de configuración de WhatsApp del salón activo
Route::get('/admin/configuracion-whatsapp', function () {
    $tenant = \App\Models\Tenant::where('is_active', true)->first();
    $slug = $tenant?->slug ?? 'paola-aguilera';
    return redirect("/admin/{$slug}/configuracion-whatsapp");
});

// Vinculación y Conexión Oficial de WhatsApp (Web)
Route::get('/admin/whatsapp', [\App\Http\Controllers\WhatsAppWebController::class, 'showQr'])->name('admin.whatsapp');
Route::get('/reserva/whatsapp-qr', [\App\Http\Controllers\WhatsAppWebController::class, 'showQr'])->name('portal.whatsapp-qr');
Route::get('/admin/whatsapp/status', [\App\Http\Controllers\WhatsAppWebController::class, 'status'])->name('admin.whatsapp.status');
Route::post('/admin/whatsapp/test', [\App\Http\Controllers\WhatsAppWebController::class, 'testMessage'])->name('admin.whatsapp.test');
Route::post('/admin/whatsapp/reset', [\App\Http\Controllers\WhatsAppWebController::class, 'resetInstance'])->name('admin.whatsapp.reset');
