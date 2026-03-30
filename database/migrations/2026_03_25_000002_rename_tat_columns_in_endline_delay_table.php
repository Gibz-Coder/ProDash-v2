<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('endline_delay', function (Blueprint $table): void {
            $table->renameColumn('qc_ana_tat', 'qc_ana_completed_at');
            $table->renameColumn('vi_techl_tat', 'vi_techl_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table): void {
            $table->renameColumn('qc_ana_completed_at', 'qc_ana_tat');
            $table->renameColumn('vi_techl_completed_at', 'vi_techl_tat');
        });
    }
};
