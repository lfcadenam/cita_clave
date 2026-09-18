<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\WorkingSchedule;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingAvailabilityEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertSuccessful();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_services_catalog_api(): void
    {
        $response = $this->getJson('/api/v1/services');
        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'name', 'duration_minutes', 'base_price', 'deposit_amount', 'category', 'is_active'],
            ],
            'meta' => ['total', 'categories'],
        ]);
    }

    public function test_availability_closed_on_sunday(): void
    {
        $service = Service::where('duration_minutes', 60)->first();

        // Next Sunday
        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $response = $this->getJson("/api/v1/availability/slots?service_id={$service->id}&date={$sunday}");
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'data' => [
                'is_available' => false,
                'total_slots' => 0,
                'slots' => [],
            ],
        ]);
    }

    public function test_availability_slots_respect_lunch_and_schedule(): void
    {
        $service = Service::where('duration_minutes', 60)->first();

        // Next Wednesday
        $wednesday = Carbon::now()->next(Carbon::WEDNESDAY)->toDateString();

        $response = $this->getJson("/api/v1/availability/slots?service_id={$service->id}&date={$wednesday}");
        $response->assertSuccessful();
        $response->assertJson(['success' => true]);

        $slots = $response->json('data.slots');
        $this->assertNotEmpty($slots);

        // Ensure no slot overlaps with 13:00 to 14:00 (lunch block)
        foreach ($slots as $slot) {
            $start = $slot['start_time'];
            $end = $slot['end_time'];

            // Slot cannot start during lunch (13:00 to 13:59)
            $this->assertFalse(
                $start >= '13:00' && $start < '14:00',
                "Slot {$start} - {$end} starts during lunch block."
            );

            // Slot cannot span across lunch (e.g., 12:30 to 13:30)
            if ($start < '13:00') {
                $this->assertTrue(
                    $end <= '13:00',
                    "Slot {$start} - {$end} overlaps into lunch block."
                );
            }
        }
    }

    public function test_availability_anti_gaps_engine_excludes_overlapping_appointments(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        // Create an appointment from 09:00 to 10:00
        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Ana Maria Lopez',
            'client_phone' => '3112223344',
            'appointment_date' => $testDate,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);
        $slots = $availabilityService->getAvailableSlots($service, $testDate, true);

        // 09:00 and 09:30 must NOT be available
        $startTimes = array_column($slots, 'start_time');
        $this->assertNotContains('09:00', $startTimes);
        $this->assertNotContains('09:15', $startTimes);
        $this->assertNotContains('09:30', $startTimes);
        $this->assertNotContains('09:45', $startTimes);

        // 08:00 (before) and 10:00 (immediately after) should be available and marked recommended
        $this->assertContains('08:00', $startTimes);
        $this->assertContains('10:00', $startTimes);
    }

    public function test_anti_gaps_engine_offers_immediate_slot_after_appointment(): void
    {
        $service90 = Service::where('duration_minutes', 90)->first();
        $testDate = Carbon::now()->next(Carbon::TUESDAY)->toDateString();

        // Clear any pre-seeded appointments on the test date to isolate anti-gaps behavior
        Appointment::whereDate('appointment_date', $testDate)->delete();

        // Existing appointment ending at 11:15
        Appointment::create([
            'service_id' => $service90->id,
            'client_name' => 'Luis Fernando Cadena',
            'client_phone' => '3106080402',
            'appointment_date' => $testDate,
            'start_time' => '09:15:00',
            'end_time' => '11:15:00',
            'total_amount' => 140000,
            'deposit_amount' => 40000,
            'deposit_paid' => 40000,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $availabilityService = app(BookingAvailabilityService::class);
        $slots = $availabilityService->getAvailableSlots($service90, $testDate, true);

        $startTimes = array_column($slots, 'start_time');

        // 11:15 must be the first available morning slot immediately following the previous client
        $this->assertContains('11:15', $startTimes);

        // Afternoon starts immediately at 14:00 (2:00 PM) post-lunch, followed by 15:30 (3:30 PM), not arbitrary 14:30
        $this->assertContains('14:00', $startTimes);
        $this->assertContains('15:30', $startTimes);
        $this->assertNotContains('14:30', $startTimes);
        $this->assertNotContains('14:45', $startTimes);
    }

    public function test_hold_appointment_lock_endpoint(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::FRIDAY)->toDateString();

        $response = $this->postJson('/api/v1/appointments/hold', [
            'service_id' => $service->id,
            'date' => $testDate,
            'start_time' => '08:00',
            'client_name' => 'Carolina Mendoza',
            'client_phone' => '3123456789',
            'client_email' => 'carolina@gmail.com',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'service' => ['name' => $service->name],
                'date' => $testDate,
                'start_time' => '08:00',
            ],
        ]);

        $appointmentId = $response->json('data.appointment_id');
        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => AppointmentStatus::PENDING_DEPOSIT->value,
        ]);
    }

    public function test_book_appointment_with_nequi_receipt_upload(): void
    {
        Storage::fake('public');

        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $receipt = UploadedFile::fake()->image('comprobante_nequi.jpg', 600, 800);

        $response = $this->postJson('/api/v1/appointments/book', [
            'service_id' => $service->id,
            'date' => $testDate,
            'start_time' => '08:00',
            'client_name' => 'Daniela Castro',
            'client_phone' => '3189991122',
            'client_email' => 'daniela@hotmail.com',
            'client_notes' => 'Tengo piel sensible',
            'payment_method' => 'NEQUI_TRANSFER',
            'receipt' => $receipt,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => AppointmentStatus::PENDING_VERIFICATION->value,
                'client_name' => 'Daniela Castro',
            ],
        ]);

        $aptNumber = $response->json('data.appointment_number');
        $appointment = Appointment::where('appointment_number', $aptNumber)->first();
        $this->assertNotNull($appointment);
        $this->assertNotNull($appointment->deposit_proof_image);
        Storage::disk('public')->assertExists($appointment->deposit_proof_image);

        // Test status check endpoint
        $statusResponse = $this->getJson("/api/v1/appointments/status/{$aptNumber}");
        $statusResponse->assertSuccessful();
        $statusResponse->assertJson([
            'success' => true,
            'data' => [
                'appointment_number' => $aptNumber,
                'status' => AppointmentStatus::PENDING_VERIFICATION->value,
            ],
        ]);
    }

    public function test_book_appointment_from_angular_client_payload(): void
    {
        Storage::fake('public');

        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::TUESDAY)->toDateString();
        $receipt = UploadedFile::fake()->image('comprobante_angular.jpg', 600, 800);

        // Angular payload format
        $response = $this->postJson('/api/v1/appointments/book', [
            'service_id' => $service->id,
            'booking_date' => $testDate,
            'start_time' => '08:00:00',
            'client_name' => 'Valentina Restrepo',
            'client_phone' => '3001234567',
            'client_email' => 'valentina@gmail.com',
            'client_notes' => '[Preferencias: ✨ Primera vez con Paola]',
            'payment_method' => 'NEQUI',
            'receipt' => $receipt,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => AppointmentStatus::PENDING_VERIFICATION->value,
                'client_name' => 'Valentina Restrepo',
            ],
        ]);
    }

    public function test_join_waitlist_endpoint(): void
    {
        $service = Service::first();
        $testDate = Carbon::now()->next(Carbon::SATURDAY)->toDateString();

        $response = $this->postJson('/api/v1/waitlist/join', [
            'service_id' => $service->id,
            'client_name' => 'Lucia Fernandez',
            'client_phone' => '3205556677',
            'client_email' => 'lucia@gmail.com',
            'requested_date' => $testDate,
            'preferred_time_range' => 'morning',
            'notes' => 'Si se cancela alguien en la mañana me avisan porfa',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'service_name' => $service->name,
                'requested_date' => $testDate,
                'preferred_time_range' => 'morning',
                'status' => 'waiting',
            ],
        ]);

        $this->assertDatabaseHas('waitlists', [
            'client_phone' => '3205556677',
            'status' => 'waiting',
        ]);
    }
}