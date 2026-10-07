<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CurrentMonthAppointmentsSeeder extends Seeder
{
    /**
     * Genera datos de citas realistas para el mes actual en el panel de Paola Aguilera.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'paola-aguilera'],
            [
                'name' => 'Paola Andrea Aguilera Camacho',
                'domain' => 'paola.salonesgo.com',
                'phone' => '3106080402',
                'email' => 'paola@nuvex-belleza.com',
                'is_active' => true,
            ]
        );

        $services = Service::where('tenant_id', $tenant->id)->get();
        if ($services->isEmpty()) {
            $this->call(DemoDataSeeder::class);
            $services = Service::where('tenant_id', $tenant->id)->get();
        }

        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;

        $clients = [
            ['name' => 'Mariana Salazar', 'phone' => '3114529810', 'email' => 'mariana.salazar@gmail.com'],
            ['name' => 'Daniela Morales Pardo', 'phone' => '3142208941', 'email' => 'daniela.morales@outlook.com'],
            ['name' => 'Isabella Cárdenas', 'phone' => '3187654321', 'email' => 'isabella.cardenas@gmail.com'],
            ['name' => 'Juliana Henao Castro', 'phone' => '3009871234', 'email' => 'juliana.henao@yahoo.com'],
            ['name' => 'Carolina Echeverri', 'phone' => '3123456789', 'email' => 'carolina.echeverri@hotmail.com'],
            ['name' => 'Natalia Osorio', 'phone' => '3156781234', 'email' => 'natalia.osorio@gmail.com'],
            ['name' => 'Gabriela Pardo Mejia', 'phone' => '3178901234', 'email' => 'gabriela.pardo@gmail.com'],
            ['name' => 'Andrea Restrepo', 'phone' => '3109876543', 'email' => 'andrea.restrepo@gmail.com'],
            ['name' => 'Laura Vanessa Duque', 'phone' => '3134567890', 'email' => 'laura.duque@gmail.com'],
            ['name' => 'Tatiana Martinez', 'phone' => '3167890123', 'email' => 'tatiana.martinez@gmail.com'],
            ['name' => 'Sofia Vergara Gomez', 'phone' => '3190123456', 'email' => 'sofia.vergara@gmail.com'],
            ['name' => 'Alejandra Quintero', 'phone' => '3128901234', 'email' => 'alejandra.quintero@gmail.com'],
            ['name' => 'Manuela Gomez Londoño', 'phone' => '3145678901', 'email' => 'manuela.gomez@gmail.com'],
            ['name' => 'Catalina Botero', 'phone' => '3189012345', 'email' => 'catalina.botero@gmail.com'],
            ['name' => 'Paula Andrea Rios', 'phone' => '3012345678', 'email' => 'paula.rios@gmail.com'],
        ];

        // Definición de citas a lo largo del mes actual
        $appointmentsPlan = [
            // Semana 1
            ['day' => 1, 'start' => '09:00:00', 'end' => '10:30:00', 'service_index' => 0, 'client_index' => 0, 'status' => AppointmentStatus::COMPLETED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 2, 'start' => '11:00:00', 'end' => '13:00:00', 'service_index' => 1, 'client_index' => 1, 'status' => AppointmentStatus::COMPLETED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 3, 'start' => '14:30:00', 'end' => '15:45:00', 'service_index' => 2, 'client_index' => 2, 'status' => AppointmentStatus::COMPLETED, 'method' => PaymentMethod::BOLD_ONLINE],
            
            // Semana 2 (Semana actual)
            ['day' => 5, 'start' => '10:00:00', 'end' => '11:30:00', 'service_index' => 0, 'client_index' => 3, 'status' => AppointmentStatus::COMPLETED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 6, 'start' => '09:00:00', 'end' => '11:00:00', 'service_index' => 1, 'client_index' => 4, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 6, 'start' => '14:00:00', 'end' => '15:15:00', 'service_index' => 2, 'client_index' => 5, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 7, 'start' => '09:30:00', 'end' => '11:00:00', 'service_index' => 0, 'client_index' => 6, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 7, 'start' => '11:30:00', 'end' => '14:00:00', 'service_index' => 3, 'client_index' => 7, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 7, 'start' => '15:00:00', 'end' => '16:00:00', 'service_index' => 4, 'client_index' => 8, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 8, 'start' => '10:00:00', 'end' => '12:00:00', 'service_index' => 1, 'client_index' => 9, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 8, 'start' => '14:30:00', 'end' => '15:30:00', 'service_index' => 5, 'client_index' => 10, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 9, 'start' => '09:00:00', 'end' => '10:30:00', 'service_index' => 0, 'client_index' => 11, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 9, 'start' => '11:00:00', 'end' => '12:15:00', 'service_index' => 2, 'client_index' => 12, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 10, 'start' => '09:00:00', 'end' => '11:30:00', 'service_index' => 3, 'client_index' => 13, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 10, 'start' => '12:00:00', 'end' => '13:00:00', 'service_index' => 7, 'client_index' => 14, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],

            // Semana 3
            ['day' => 13, 'start' => '10:00:00', 'end' => '12:00:00', 'service_index' => 1, 'client_index' => 0, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 14, 'start' => '14:00:00', 'end' => '15:30:00', 'service_index' => 0, 'client_index' => 1, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 15, 'start' => '09:30:00', 'end' => '10:45:00', 'service_index' => 2, 'client_index' => 2, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 16, 'start' => '11:00:00', 'end' => '13:30:00', 'service_index' => 3, 'client_index' => 3, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 17, 'start' => '10:00:00', 'end' => '11:00:00', 'service_index' => 4, 'client_index' => 4, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],

            // Semana 4 y fin de mes
            ['day' => 20, 'start' => '09:00:00', 'end' => '11:00:00', 'service_index' => 1, 'client_index' => 5, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 21, 'start' => '14:00:00', 'end' => '15:15:00', 'service_index' => 2, 'client_index' => 6, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 22, 'start' => '10:30:00', 'end' => '12:00:00', 'service_index' => 0, 'client_index' => 7, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 23, 'start' => '13:00:00', 'end' => '15:30:00', 'service_index' => 3, 'client_index' => 8, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 24, 'start' => '09:00:00', 'end' => '10:00:00', 'service_index' => 5, 'client_index' => 9, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 27, 'start' => '11:00:00', 'end' => '12:30:00', 'service_index' => 0, 'client_index' => 10, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::NEQUI_TRANSFER],
            ['day' => 28, 'start' => '14:00:00', 'end' => '16:00:00', 'service_index' => 1, 'client_index' => 11, 'status' => AppointmentStatus::PENDING_VERIFICATION, 'method' => PaymentMethod::NEQUI_TRANSFER, 'receipt' => true],
            ['day' => 29, 'start' => '10:00:00', 'end' => '11:15:00', 'service_index' => 2, 'client_index' => 12, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
            ['day' => 30, 'start' => '09:30:00', 'end' => '12:00:00', 'service_index' => 3, 'client_index' => 13, 'status' => AppointmentStatus::CONFIRMED, 'method' => PaymentMethod::BOLD_ONLINE],
        ];

        foreach ($appointmentsPlan as $index => $plan) {
            $service = $services[$plan['service_index'] % $services->count()];
            $client = $clients[$plan['client_index'] % count($clients)];

            $date = Carbon::createFromDate($year, $month, $plan['day']);
            if ($date->isSunday()) {
                $date->addDay(); // Mover a lunes si caía en domingo
            }

            $aptCode = sprintf('PA-%s-%02d', $date->format('ym'), $index + 1);

            $totalPrice = $service->base_price ?: 120000;
            $depositAmount = $service->deposit_amount ?: 30000;
            $status = $plan['status'];

            $depositPaid = ($status === AppointmentStatus::CONFIRMED || $status === AppointmentStatus::COMPLETED) ? $depositAmount : 0;
            $balanceDue = ($status === AppointmentStatus::COMPLETED) ? 0 : ($totalPrice - $depositPaid);

            $proofImage = (!empty($plan['receipt']) && $status === AppointmentStatus::PENDING_VERIFICATION)
                ? 'samples/comprobante_nequi_muestra.png'
                : null;

            Appointment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'appointment_number' => $aptCode],
                [
                    'tenant_id' => $tenant->id,
                    'service_id' => $service->id,
                    'client_name' => $client['name'],
                    'client_phone' => $client['phone'],
                    'client_email' => $client['email'],
                    'appointment_date' => $date->toDateString(),
                    'start_time' => $plan['start'],
                    'end_time' => $plan['end'],
                    'status' => $status,
                    'payment_method' => $plan['method'],
                    'total_amount' => $totalPrice,
                    'deposit_amount' => $depositAmount,
                    'deposit_paid' => $depositPaid,
                    'balance_due' => $balanceDue,
                    'deposit_proof_image' => $proofImage,
                    'verified_at' => ($depositPaid > 0) ? $date->copy()->subHours(2) : null,
                    'verification_notes' => ($depositPaid > 0) ? 'Abono verificado exitosamente' : null,
                    'client_notes' => 'Cita reservada a través de la plataforma web',
                ]
            );
        }
    }
}
