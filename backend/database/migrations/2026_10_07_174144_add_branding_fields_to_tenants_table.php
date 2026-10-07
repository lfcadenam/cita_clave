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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('short_name')->nullable()->after('name');
            $table->string('business_type')->nullable()->default('ESTUDIO DE BELLEZA')->after('short_name');
            $table->string('specialties')->nullable()->default('Pestañas · Cejas · Faciales · Micropigmentación')->after('city');
            $table->string('schedule_summary')->nullable()->default('Lunes a Sábado 8:00 AM - 6:00 PM | Almuerzo 1:00 PM - 2:00 PM')->after('specialties');
            $table->string('whatsapp_number')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['short_name', 'business_type', 'specialties', 'schedule_summary', 'whatsapp_number']);
        });
    }
};
