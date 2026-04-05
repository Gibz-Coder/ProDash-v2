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
            $table->json('defect_details')->nullable()->after('defect_code');
        });
    }

    public function down(): void
    {
        Schema::table('vi_technical', function (Blueprint $table) {
            $table->dropColumn('defect_details');
        });
    }
};
