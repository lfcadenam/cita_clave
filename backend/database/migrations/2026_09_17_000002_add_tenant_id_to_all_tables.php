<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            });
        }

        // 2. services
        if (Schema::hasTable('services')) {
            if (!Schema::hasColumn('services', 'tenant_id')) {
                Schema::table('services', function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                });
            }
            try {
                Schema::table('services', function (Blueprint $table) {
                    $table->dropUnique('services_slug_unique');
                });
            } catch (\Throwable $e) {}
            try {
                Schema::table('services', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'slug'], 'services_tenant_slug_unique');
                });
            } catch (\Throwable $e) {}
        }

        // 3. working_schedules
        if (Schema::hasTable('working_schedules')) {
            if (!Schema::hasColumn('working_schedules', 'tenant_id')) {
                Schema::table('working_schedules', function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                });
            }
            try {
                Schema::table('working_schedules', function (Blueprint $table) {
                    $table->dropUnique('working_schedules_day_of_week_unique');
                });
            } catch (\Throwable $e) {}
            try {
                Schema::table('working_schedules', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'day_of_week'], 'working_schedules_tenant_day_unique');
                });
            } catch (\Throwable $e) {}
        }

        // 4. blocked_slots
        if (Schema::hasTable('blocked_slots') && !Schema::hasColumn('blocked_slots', 'tenant_id')) {
            Schema::table('blocked_slots', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            });
        }

        // 5. appointments
        if (Schema::hasTable('appointments') && !Schema::hasColumn('appointments', 'tenant_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            });
        }

        // 6. waitlists
        if (Schema::hasTable('waitlists') && !Schema::hasColumn('waitlists', 'tenant_id')) {
            Schema::table('waitlists', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = ['waitlists', 'appointments', 'blocked_slots', 'working_schedules', 'services', 'users'];
        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};