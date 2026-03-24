<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stub migration for the legacy equipment table.
 * The table pre-exists in production; this migration exists solely so that
 * subsequent ALTER migrations can run in the in-memory SQLite test database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('equipment')) {
            return;
        }

        Schema::create('equipment', function (Blueprint $table): void {
            $table->id();
            $table->string('eqp_no', 50)->nullable();
            $table->string('eqp_line', 10)->nullable();
            $table->string('eqp_area', 50)->nullable();
            $table->string('eqp_status', 50)->nullable();
            $table->string('alloc_type', 50)->nullable();
            $table->string('size', 10)->nullable();
            $table->bigInteger('oee_capa')->nullable();
            $table->string('ongoing_lot', 100)->nullable();
            $table->string('modified_by', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
