<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si el índice ya existe de forma idempotente para evitar error 1061
        if (Schema::hasTable('appointments')) {
            try {
                if (method_exists(Schema::class, 'hasIndex') && Schema::hasIndex('appointments', 'idx_appointments_calendar_range')) {
                    return;
                }
            } catch (\Throwable $e) {}

            try {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->index(['tenant_id', 'appointment_date', 'status'], 'idx_appointments_calendar_range');
                });
            } catch (\Throwable $e) {
                // Si el índice ya existe en la base de datos de producción, continuar limpiamente
                if (!str_contains($e->getMessage(), 'Duplicate key name') && !str_contains($e->getMessage(), '1061')) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            try {
                Schema::table('appointments', function (Blueprint $table) {
                    $table->dropIndex('idx_appointments_calendar_range');
                });
            } catch (\Throwable $e) {}
        }
    }
};
