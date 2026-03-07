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
            $table->string('lot_no')->nullable()->change();
            $table->integer('quantity')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lot_requests', function (Blueprint $table) {
            $table->string('lot_no')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
        });
    }
};
