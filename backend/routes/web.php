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

// Confirmación Manual de Asistencia por la Clienta
Route::post('/reserva/confirmar-asistencia/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendance'])->name('portal.confirm-attendance');
Route::post('/citas/confirmar-asistencia/{appointmentNumber}', [ClientBookingWebController::class, 'confirmAttendance']);

// Cancelación de Cita por la Clienta (>24h y motivo obligatorio)
Route::post('/reserva/cancelar/{appointmentNumber}', [ClientBookingWebController::class, 'cancelAppointment'])->name('portal.cancel-appointment');
Route::post('/citas/cancelar/{appointmentNumber}', [ClientBookingWebController::class, 'cancelAppointment']);
Route::post('/citas/{appointmentNumber}/cancel', [ClientBookingWebController::class, 'cancelAppointment']);

// Descarga de Calendario (.ics)
Route::get('/reserva/calendar/{appointmentNumber}.ics', [ClientBookingWebController::class, 'downloadCalendar'])->name('portal.calendar');
Route::get('/citas/{appointmentNumber}/calendar', [ClientBookingWebController::class, 'downloadCalendar']);
Route::get('/citas/{appointmentNumber}/calendar.ics', [ClientBookingWebController::class, 'downloadCalendar']);

// SuperAdmin - Previsualización de Plantillas de Correo Electrónico
Route::get('/superadmin/email-preview/{key}', [\App\Filament\SuperAdmin\Pages\EmailTemplatesPage::class, 'renderStandalonePreview'])
    ->name('superadmin.email-preview');