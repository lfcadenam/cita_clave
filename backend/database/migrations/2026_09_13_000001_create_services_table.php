<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('FACIAL'); // FACIAL, PESTANAS_CEJAS, LABIOS, CORPORAL_MASAJES, DEPILACION
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(60); // Duración exacta en minutos
            $table->decimal('base_price', 12, 2);              // Precio total en COP
            $table->decimal('deposit_amount', 12, 2)->default(0); // Monto requerido de abono en COP
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
