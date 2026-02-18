<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipment_snapshots', function (Blueprint $table) {
            $table->id();
            
            // Aggregated counts by equipment status
            $table->integer('operational')->default(0); // Total operational machines
            $table->integer('breakdown')->default(0);   // Breakdown status
            $table->integer('full_stop')->default(0);   // Full stop status
            $table->integer('plan_stop')->default(0);   // Planned stop status
            $table->integer('idle')->default(0);        // Idle status
            $table->integer('advance')->default(0);     // Advance status
            
            // Aggregated counts by lot assignment (derived from operational)
            $table->integer('run')->default(0);         // Operational + with ongoing lot
            $table->integer('wait')->default(0);        // Operational + without ongoing lot
            $table->integer('not_use')->default(0);     // Not operational (breakdown + full_stop + plan_stop + idle + advance)
            
            $table->text('remarks')->nullable();        // Summary remarks
            $table->date('date');                       // 2026-02-18
            $table->integer('hour');                    // 10, 11, 12, etc.
            $table->timestamp('snapshot_at');           // 2026-02-18 10:30:00
            
            // Indexes for efficient querying
            $table->index(['date', 'hour'], 'idx_date_hour');
            $table->index('snapshot_at', 'idx_snapshot_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_snapshots');
    }
};
