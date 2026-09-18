<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Domingo, 1 = Lunes, ..., 6 = Sábado
            $table->string('day_name');                 // Lunes, Martes, etc.
            $table->boolean('is_working_day')->default(true);
            $table->time('open_time')->default('08:00:00');
            $table->time('close_time')->default('18:00:00');
            $table->integer('slot_interval_minutes')->default(15); // Intervalo de granularidad
            $table->timestamps();

            $table->unique('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_schedules');
    }
};
