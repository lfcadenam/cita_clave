<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->default('Bogotá');
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#0d9488');
            
            // Datos de Pago Nequi Directo
            $table->string('nequi_phone')->nullable();
            $table->string('nequi_account_holder')->nullable();
            $table->string('nequi_account_type')->default('Personal');
            $table->string('nequi_qr_image')->nullable();

            // Pasarela Bold
            $table->string('bold_api_key')->nullable();
            $table->string('bold_secret_key')->nullable();

            // Suscripcion SaaS
            $table->string('subscription_status')->default('active');
            $table->string('plan_name')->default('Pro Salon');
            $table->integer('max_appointments_per_month')->default(500);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
