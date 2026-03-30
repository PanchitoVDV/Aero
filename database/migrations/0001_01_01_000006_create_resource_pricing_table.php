<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type')->unique(); // ram_gb, cpu_core, storage_gb, ipv4, base_price
            $table->string('label');
            $table->string('unit');
            $table->decimal('price_per_unit', 10, 4);
            $table->integer('min_value')->default(1);
            $table->integer('max_value')->default(128);
            $table->integer('step')->default(1);
            $table->integer('default_value')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_pricing');
    }
};
