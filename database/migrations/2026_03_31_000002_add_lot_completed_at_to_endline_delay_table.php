<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endline_delay', function (Blueprint $table) {
            $table->dateTime('lot_completed_at')->nullable()->after('output_status');
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table) {
            $table->dropColumn('lot_completed_at');
        });
    }
};
