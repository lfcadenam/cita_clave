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

    public function test_client_can_manually_confirm_attendance(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Sara Restrepo',
            'client_phone' => '3195556677',
            'appointment_date' => Carbon::now()->next(Carbon::TUESDAY)->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:30:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->assertNull($appointment->attendance_confirmed_at);

        $response = $this->post("/reserva/confirmar-asistencia/{$appointment->appointment_number}");
        $response->assertRedirect();

        $this->assertNotNull($appointment->fresh()->attendance_confirmed_at);

        $citasResponse = $this->get("/citas/{$appointment->appointment_number}");
        $citasResponse->assertSuccessful();
        $citasResponse->assertSee('Asistencia Confirmada');
    }

    public function test_client_can_self_cancel_appointment_when_more_than_24h_with_mandatory_reason(): void
    {
        $service = Service::first();
        // Appointment in 3 days (> 24 hours)
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Valentina Gomez',
            'client_phone' => '3128889900',
            'appointment_date' => Carbon::now()->addDays(3)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 90000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->assertTrue($appointment->canBeCancelledByClient());

        // Cancel with mandatory reason
        $response = $this->post("/citas/{$appointment->appointment_number}/cancel", [
            'cancellation_reason' => 'Tengo un viaje imprevisto de trabajo ese fin de semana.',
        ]);

        $response->assertRedirect();
        $fresh = $appointment->fresh();
        $this->assertEquals(AppointmentStatus::CANCELLED, $fresh->status);
        $this->assertEquals('Tengo un viaje imprevisto de trabajo ese fin de semana.', $fresh->cancellation_reason);
        $this->assertNotNull($fresh->cancelled_at);

        // Verify that the slot is now free and no conflicts occur
        $this->assertFalse(Appointment::hasConflict(
            $fresh->appointment_date,
            $fresh->start_time,
            $fresh->end_time
        ));

        // View confirmation page shows cancelled state
        $pageResponse = $this->get("/citas/{$appointment->appointment_number}");
        $pageResponse->assertSuccessful();
        $pageResponse->assertSee('Cita Cancelada');
        $pageResponse->assertSee('Tengo un viaje imprevisto de trabajo ese fin de semana.');
    }

    public function test_client_cannot_cancel_without_reason(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Camila Duque',
            'client_phone' => '3131112233',
            'appointment_date' => Carbon::now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // Missing reason
        $response = $this->post("/citas/{$appointment->appointment_number}/cancel", [
            'cancellation_reason' => '',
        ]);
        $response->assertSessionHasErrors(['cancellation_reason']);
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);

        // Reason too short (< 5 chars)
        $shortResponse = $this->post("/citas/{$appointment->appointment_number}/cancel", [
            'cancellation_reason' => 'No',
        ]);
        $shortResponse->assertSessionHasErrors(['cancellation_reason']);
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);
    }

    public function test_client_cannot_self_cancel_when_less_than_24h_remaining(): void
    {
        $service = Service::first();
        // Appointment is today in 3 hours (strictly < 24 hours)
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Daniela Morales',
            'client_phone' => '3142223344',
            'appointment_date' => Carbon::now()->toDateString(),
            'start_time' => Carbon::now()->addHours(3)->format('H:i:s'),
            'end_time' => Carbon::now()->addHours(4)->format('H:i:s'),
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $this->assertFalse($appointment->canBeCancelledByClient());

        // Attempting to cancel should fail
        $response = $this->post("/citas/{$appointment->appointment_number}/cancel", [
            'cancellation_reason' => 'Me surgió un imprevisto esta misma tarde.',
        ]);

        $response->assertSessionHasErrors(['cancellation_error']);
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);

        // View confirmation page shows the <24h warning and disables self-cancel form
        $pageResponse = $this->get("/citas/{$appointment->appointment_number}");
        $pageResponse->assertSuccessful();
        $pageResponse->assertSee('Menos de 24 horas');
        $pageResponse->assertSee('solo puede ser gestionada directamente por la administración');
    }

    public function test_api_client_cancellation_flow(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Laura Rios',
            'client_phone' => '3153334455',
            'appointment_date' => Carbon::now()->addDays(5)->toDateString(),
            'start_time' => '15:00:00',
            'end_time' => '16:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 90000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // API Cancel with reason
        $response = $this->postJson("/api/v1/appointments/{$appointment->appointment_number}/cancel", [
            'cancellation_reason' => 'Cambio de horario de vuelo internacional.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => AppointmentStatus::CANCELLED->value,
                'cancellation_reason' => 'Cambio de horario de vuelo internacional.',
            ],
        ]);

        $this->assertEquals(AppointmentStatus::CANCELLED, $appointment->fresh()->status);
    }
}