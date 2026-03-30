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
            $table->string('qc_ana_prog', 100)->nullable()->after('qc_ana_start');
            $table->string('vi_techl_prog', 100)->nullable()->after('vi_techl_start');
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table): void {
            $table->dropColumn(['qc_ana_prog', 'vi_techl_prog']);
        });
    }
};
