<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endline_delay', function (Blueprint $table) {
            $table->string('qc_result', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table) {
            $table->integer('qc_result')->nullable()->change();
        });
    }
};
