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
            $table->dateTime('qc_ana_completed_at')->nullable()->change();
            $table->dateTime('vi_techl_completed_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table): void {
            $table->integer('qc_ana_completed_at')->nullable()->change();
            $table->integer('vi_techl_completed_at')->nullable()->change();
        });
    }
};
