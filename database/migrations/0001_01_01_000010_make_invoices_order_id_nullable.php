<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->text('notes')->nullable()->after('paid_at');
            $table->string('description')->nullable()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['notes', 'description']);
            $table->dropForeign(['order_id']);
            $table->foreignId('order_id')->constrained()->onDelete('cascade')->change();
        });
    }
};
