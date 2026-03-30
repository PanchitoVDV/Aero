<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->constrained();
            $table->unsignedBigInteger('virtfusion_server_id')->nullable();
            $table->string('name');
            $table->string('hostname')->nullable();
            $table->string('status')->default('pending'); // pending, active, suspended, building, error, deleted
            $table->string('power_status')->default('offline'); // online, offline, unknown
            $table->string('ip_address')->nullable();
            $table->string('os_template')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly, quarterly, yearly
            $table->timestamp('next_due_date')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
