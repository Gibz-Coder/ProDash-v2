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
            $table->string('created_by')->nullable()->after('updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
