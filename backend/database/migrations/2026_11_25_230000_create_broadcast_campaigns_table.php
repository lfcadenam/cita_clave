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
        if (!Schema::hasTable('broadcast_campaigns')) {
            Schema::create('broadcast_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('channel', 30)->default('whatsapp');
                $table->string('template_key', 80)->nullable();
                $table->string('segment', 50)->default('all');
                $table->text('message_body');
                $table->string('status', 30)->default('draft'); // draft, processing, completed, failed, cancelled
                $table->unsignedInteger('total_recipients')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('broadcast_recipients')) {
            Schema::create('broadcast_recipients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('broadcast_campaign_id')->constrained('broadcast_campaigns')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('phone', 30);
                $table->string('client_name', 150);
                $table->string('client_nickname', 100)->nullable();
                $table->string('status', 30)->default('pending'); // pending, sent, failed
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['broadcast_campaign_id', 'status']);
                $table->index(['phone']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_recipients');
        Schema::dropIfExists('broadcast_campaigns');
    }
};
