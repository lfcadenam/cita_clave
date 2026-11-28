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
        if (Schema::hasTable('blocked_slots') && !Schema::hasColumn('blocked_slots', 'is_specific')) {
            Schema::table('blocked_slots', function (Blueprint $table) {
                $table->boolean('is_specific')->default(false)->after('is_recurring');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('blocked_slots') && Schema::hasColumn('blocked_slots', 'is_specific')) {
            Schema::table('blocked_slots', function (Blueprint $table) {
                $table->dropColumn('is_specific');
            });
        }
    }
};
