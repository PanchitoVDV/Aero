<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('package_id')->constrained();
            $table->string('type'); // new, upgrade, downgrade, renewal
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled, refunded
            $table->decimal('amount', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_cycle')->default('monthly');
            $table->string('mollie_payment_id')->nullable();
            $table->string('mollie_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
