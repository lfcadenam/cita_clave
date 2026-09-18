<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique(); // Ej: PA-2609-A1B2
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            
            // Datos del cliente
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email')->nullable();

            // Programación de la cita
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            
            // Estado y Métodos de Pago
            $table->string('status')->default('PENDING_DEPOSIT'); // PENDING_DEPOSIT, PENDING_VERIFICATION, CONFIRMED, IN_PROGRESS, COMPLETED, CANCELLED, NO_SHOW
            $table->string('payment_method')->nullable();         // BOLD_ONLINE, NEQUI_TRANSFER, CASH_AT_LOCATION

            // Valores en COP
            $table->decimal('total_amount', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('deposit_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);

            // Verificación de Comprobante Nequi / Pasarela
            $table->string('deposit_proof_image')->nullable();    // Ruta de la imagen del comprobante subido
            $table->text('verification_notes')->nullable();       // Notas de Paola al aprobar/rechazar
            $table->timestamp('verified_at')->nullable();         // Fecha de aprobación del abono
            $table->string('payment_gateway_reference')->nullable(); // ID de transacción de Bold/Wompi
            $table->json('payment_gateway_payload')->nullable();

            // Políticas de Cancelación y Notas
            $table->string('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('client_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Índices de alto rendimiento para el motor Anti-Huecos
            $table->index(['appointment_date', 'start_time', 'end_time']);
            $table->index('status');
            $table->index('client_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
