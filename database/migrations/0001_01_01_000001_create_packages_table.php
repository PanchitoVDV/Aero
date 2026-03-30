<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('virtfusion_package_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('vps'); // vps, dedicated, game
            $table->integer('memory'); // MB
            $table->integer('storage'); // GB
            $table->integer('cpu_cores');
            $table->integer('traffic'); // GB, 0 = unlimited
            $table->integer('network_speed_in')->default(0); // kB/s, 0 = unlimited
            $table->integer('network_speed_out')->default(0);
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_quarterly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable(); // ["DDoS Protection", "99.9% Uptime"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
