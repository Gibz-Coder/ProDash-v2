<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_inspection', function (Blueprint $table) {
            $table->renameColumn('r_rework_result', 'rr_result');
            $table->renameColumn('l_rework_result', 'ly_result');
        });
    }

    public function down(): void
    {
        Schema::table('qc_inspection', function (Blueprint $table) {
            $table->renameColumn('rr_result', 'r_rework_result');
            $table->renameColumn('ly_result', 'l_rework_result');
        });
    }
};
