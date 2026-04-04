<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_ok', function (Blueprint $table) {
            $table->string('pending')->nullable()->after('technical_result');
        });
    }

    public function down(): void
    {
        Schema::table('qc_ok', function (Blueprint $table) {
            $table->dropColumn('pending');
        });
    }
};
