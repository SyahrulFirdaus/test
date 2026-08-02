<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->boolean('support_enabled')->default(false)->after('material');
            $table->string('support_type', 20)->nullable()->after('support_enabled');
            $table->decimal('support_volume_cm3', 12, 3)->nullable()->after('material_volume_cm3');
            $table->decimal('support_weight_g', 12, 2)->nullable()->after('estimated_weight_g');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_requests', function (Blueprint $table) {
            $table->dropColumn(['support_enabled', 'support_type', 'support_volume_cm3', 'support_weight_g']);
        });
    }
};
