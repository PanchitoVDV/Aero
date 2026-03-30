<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('status')->default('unpaid'); // unpaid, paid, overdue, cancelled
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(21.00); // 21% BTW
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->timestamp('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
