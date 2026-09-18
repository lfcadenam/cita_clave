<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalWebTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_client_portal_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertSuccessful();
        $response->assertSee('Paola Aguilera');
        $response->assertSee('Reserva tu Experiencia de Belleza');
        $response->assertSee('Limpieza Facial Profunda');
        $response->assertSee('Extensiones de Pestanas');
    }

    public function test_client_confirmation_page_loads_with_details(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Juliana Arango',
            'client_phone' => '3161112233',
            'appointment_date' => Carbon::now()->next(Carbon::FRIDAY)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'total_amount' => $service->base_price,
            'deposit_amount' => $service->deposit_amount,
            'deposit_paid' => $service->deposit_amount,
            'balance_due' => $service->base_price - $service->deposit_amount,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $response = $this->get("/reserva/confirmacion/{$appointment->appointment_number}");

        $response->assertSuccessful();
        $response->assertSee($appointment->appointment_number);
        $response->assertSee('Juliana Arango');
        $response->assertSee('Cita Confirmada');
        $response->assertSee($service->name);
    }

    public function test_client_lookup_page(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Catalina Pelaez',
            'client_phone' => '3174445566',
            'appointment_date' => Carbon::now()->next(Carbon::SATURDAY)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 90000,
            'payment_method' => PaymentMethod::BOLD_ONLINE,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // Search by phone
        $response = $this->get("/reserva/consulta?search=3174445566");
        $response->assertSuccessful();
        $response->assertSee($appointment->appointment_number);
        $response->assertSee($service->name);

        // Search by appointment number
        $responseApt = $this->get("/reserva/consulta?search={$appointment->appointment_number}");
        $responseApt->assertSuccessful();
        $responseApt->assertSee($appointment->appointment_number);
    }

    public function test_client_download_calendar_ics(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Manuela Ospina',
            'client_phone' => '3182223344',
            'appointment_date' => Carbon::now()->next(Carbon::MONDAY)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $response = $this->get("/reserva/calendar/{$appointment->appointment_number}.ics");

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('Paola Aguilera', $response->getContent());
    }
}