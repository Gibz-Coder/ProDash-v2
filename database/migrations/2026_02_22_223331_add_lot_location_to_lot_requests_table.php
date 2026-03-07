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
            $table->string('lot_location')->nullable()->after('lot_tat')->comment('Lot location/storage area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_requests', function (Blueprint $table) {
            $table->dropColumn('lot_location');
        });
    }
};
