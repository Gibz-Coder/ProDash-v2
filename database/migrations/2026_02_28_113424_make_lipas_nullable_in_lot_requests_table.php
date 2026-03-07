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
        Schema::table('lot_requests', function (Blueprint $table) {
            $table->char('lipas', 1)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_requests', function (Blueprint $table) {
            $table->char('lipas', 1)->default('N')->nullable(false)->change();
        });
    }
};
