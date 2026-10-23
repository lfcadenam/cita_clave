<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'whatsapp_driver')) {
                $table->string('whatsapp_driver', 30)->nullable()->after('whatsapp_number');
            }
            if (! Schema::hasColumn('tenants', 'meta_phone_number_id')) {
                $table->string('meta_phone_number_id', 100)->nullable()->after('whatsapp_driver');
            }
            if (! Schema::hasColumn('tenants', 'meta_waba_id')) {
                $table->string('meta_waba_id', 100)->nullable()->after('meta_phone_number_id');
            }
            if (! Schema::hasColumn('tenants', 'meta_access_token')) {
                $table->text('meta_access_token')->nullable()->after('meta_waba_id');
            }
            if (! Schema::hasColumn('tenants', 'meta_verify_token')) {
                $table->string('meta_verify_token', 150)->nullable()->after('meta_access_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = array_filter(
                ['whatsapp_driver', 'meta_phone_number_id', 'meta_waba_id', 'meta_access_token', 'meta_verify_token'],
                fn (string $col) => Schema::hasColumn('tenants', $col)
            );
            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
