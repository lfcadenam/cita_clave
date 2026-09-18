<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_slots', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Almuerzo / Descanso');
            $table->date('blocked_date')->nullable(); // Si es null con is_recurring = true, aplica todos los días
            $table->time('start_time');               // Ej: 13:00:00
            $table->time('end_time');                 // Ej: 14:00:00
            $table->boolean('is_recurring')->default(false); // Si se repite diariamente (ej. almuerzo diario)
            $table->string('reason')->nullable();     // Almuerzo, Médico, Personal, Mantenimiento
            $table->timestamps();

            $table->index(['blocked_date', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_slots');
    }
};
