<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->unsignedBigInteger('package_id')->nullable()->change();
            $table->foreign('package_id')->references('id')->on('packages')->nullOnDelete();

            $table->unsignedInteger('custom_ram')->nullable()->after('os_template');
            $table->unsignedInteger('custom_cpu')->nullable()->after('custom_ram');
            $table->unsignedInteger('custom_storage')->nullable()->after('custom_cpu');
            $table->unsignedInteger('custom_ipv4')->nullable()->after('custom_storage');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('custom_ipv4');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->foreignId('package_id')->constrained()->change();

            $table->dropColumn(['custom_ram', 'custom_cpu', 'custom_storage', 'custom_ipv4', 'monthly_price']);
        });
    }
};
