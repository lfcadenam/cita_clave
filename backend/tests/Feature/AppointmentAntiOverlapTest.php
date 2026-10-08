<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAntiOverlapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cannot_hold_overlapping_appointment(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        // Ensure clear slot
        Appointment::whereDate('appointment_date', $testDate)->delete();

        // Create an existing confirmed appointment 10:00 - 11:00
        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Maria Existing',
            'client_phone' => '3001112233',
            'appointment_date' => $testDate,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'status' => AppointmentStatus::CONFIRMED,
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
        ]);

        // Attempt to hold an overlapping slot at 10:00
        $response = $this->postJson('/api/v1/appointments/hold', [
            'service_id' => $service->id,
            'date' => $testDate,
            'start_time' => '10:00',
            'client_name' => 'Sara Attempt',
            'client_phone' => '3123456789',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_cannot_book_overlapping_appointment_directly(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        Appointment::whereDate('appointment_date', $testDate)->delete();

        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Camila Existing',
            'client_phone' => '3001112233',
            'appointment_date' => $testDate,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'status' => AppointmentStatus::PENDING_VERIFICATION,
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
        ]);

        // Attempt to book overlapping slot at 14:00
        $response = $this->postJson('/api/v1/appointments/book', [
            'service_id' => $service->id,
            'date' => $testDate,
            'start_time' => '14:00',
            'client_name' => 'Catalina Attempt',
            'client_phone' => '3159998877',
            'payment_method' => 'NEQUI_TRANSFER',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_contiguous_appointments_are_permitted(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        Appointment::whereDate('appointment_date', $testDate)->delete();

        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Laura First',
            'client_phone' => '3001112233',
            'appointment_date' => $testDate,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => AppointmentStatus::CONFIRMED,
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
        ]);

        // Booking immediately contiguous slot at 10:00 - 11:00 should NOT conflict
        $conflict = Appointment::findConflicting($testDate, '10:00:00', '11:00:00');
        $this->assertNull($conflict);

        // Booking immediately contiguous slot before at 08:00 - 09:00 should NOT conflict
        $conflictBefore = Appointment::findConflicting($testDate, '08:00:00', '09:00:00');
        $this->assertNull($conflictBefore);
    }

    public function test_cancelled_appointment_slot_is_freed(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        Appointment::whereDate('appointment_date', $testDate)->delete();

        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Ana Cancelled',
            'client_phone' => '3001112233',
            'appointment_date' => $testDate,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'status' => AppointmentStatus::CANCELLED,
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
        ]);

        // Check if conflict exists
        $conflict = Appointment::findConflicting($testDate, '11:00:00', '12:00:00');
        $this->assertNull($conflict);
    }

    public function test_detects_partial_and_enclosed_overlaps(): void
    {
        $service = Service::where('duration_minutes', 60)->first();
        $testDate = Carbon::now()->next(Carbon::THURSDAY)->toDateString();

        Appointment::whereDate('appointment_date', $testDate)->delete();

        Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Valeria Active',
            'client_phone' => '3001112233',
            'appointment_date' => $testDate,
            'start_time' => '15:00:00',
            'end_time' => '16:30:00',
            'status' => AppointmentStatus::CONFIRMED,
            'total_amount' => 80000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
        ]);

        // Overlap starting before and ending inside: 14:30 - 15:30
        $this->assertNotNull(Appointment::findConflicting($testDate, '14:30:00', '15:30:00'));

        // Overlap starting inside and ending after: 16:00 - 17:00
        $this->assertNotNull(Appointment::findConflicting($testDate, '16:00:00', '17:00:00'));

        // Enclosed inside: 15:15 - 16:00
        $this->assertNotNull(Appointment::findConflicting($testDate, '15:15:00', '16:00:00'));

        // Completely enclosing: 14:00 - 17:00
        $this->assertNotNull(Appointment::findConflicting($testDate, '14:00:00', '17:00:00'));
    }
}
