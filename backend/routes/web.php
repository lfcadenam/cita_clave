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

// Consulta de Estado de Reserva
Route::get('/reserva/consulta', [ClientBookingWebController::class, 'lookup'])->name('portal.lookup');

// Confirmación y Voucher Digital
Route::get('/reserva/confirmacion/{appointmentNumber}', [ClientBookingWebController::class, 'confirmation'])->name('portal.confirmation');

// Descarga de Calendario (.ics)
Route::get('/reserva/calendar/{appointmentNumber}.ics', [ClientBookingWebController::class, 'downloadCalendar'])->name('portal.calendar');