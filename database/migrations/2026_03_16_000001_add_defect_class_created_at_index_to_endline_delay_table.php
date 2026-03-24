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
            $table->index(['defect_class', 'created_at'], 'endline_delay_defect_class_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('endline_delay', function (Blueprint $table): void {
            $table->dropIndex('endline_delay_defect_class_created_at_index');
        });
    }
};
