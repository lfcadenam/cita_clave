<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'phone']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('tenant_id')->constrained('customers')->nullOnDelete();
        });

        // Backfill de clientes existentes a partir de citas previas
        try {
            $existingAppointments = DB::table('appointments')
                ->whereNotNull('client_phone')
                ->where('client_phone', '!=', '')
                ->orderBy('created_at', 'asc')
                ->get();

            $processedPhones = [];

            foreach ($existingAppointments as $appointment) {
                $tenantId = $appointment->tenant_id;
                $cleanPhone = preg_replace('/[^0-9]/', '', (string) $appointment->client_phone);

                if (empty($cleanPhone)) {
                    continue;
                }

                $key = "{$tenantId}_{$cleanPhone}";

                if (!isset($processedPhones[$key])) {
                    $firstDate = $appointment->appointment_date ?? $appointment->created_at ?? now();
                    
                    $customerId = DB::table('customers')->insertGetId([
                        'tenant_id' => $tenantId,
                        'name' => $appointment->client_name ?: 'Clienta',
                        'phone' => $cleanPhone,
                        'email' => $appointment->client_email,
                        'registered_at' => $firstDate,
                        'created_at' => $firstDate,
                        'updated_at' => now(),
                    ]);

                    $processedPhones[$key] = $customerId;
                }

                DB::table('appointments')
                    ->where('id', $appointment->id)
                    ->update(['customer_id' => $processedPhones[$key]]);
            }
        } catch (\Throwable $e) {
            // Ignorar en caso de tablas vacías o entornos controlados
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
