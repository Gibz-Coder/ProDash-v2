<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vi_technical', function (Blueprint $table) {
            $table->timestamp('technical_completed_at')->nullable()->after('technical_start_at');
        });
    }

    public function down(): void
    {
        Schema::table('vi_technical', function (Blueprint $table) {
            $table->dropColumn('technical_completed_at');
        });
    }
};
