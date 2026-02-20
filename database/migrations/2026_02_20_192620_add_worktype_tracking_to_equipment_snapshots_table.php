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
        Schema::table('equipment_snapshots', function (Blueprint $table) {
            // Add worktype-specific columns for each fixed worktype
            // NORMAL worktype
            $table->integer('normal_run')->default(0)->after('not_use');
            $table->integer('normal_wait')->default(0)->after('normal_run');
            $table->integer('normal_not_use')->default(0)->after('normal_wait');
            
            // PROCESS RW worktype
            $table->integer('process_rw_run')->default(0)->after('normal_not_use');
            $table->integer('process_rw_wait')->default(0)->after('process_rw_run');
            $table->integer('process_rw_not_use')->default(0)->after('process_rw_wait');
            
            // RL REWORK worktype
            $table->integer('rl_rework_run')->default(0)->after('process_rw_not_use');
            $table->integer('rl_rework_wait')->default(0)->after('rl_rework_run');
            $table->integer('rl_rework_not_use')->default(0)->after('rl_rework_wait');
            
            // WH REWORK worktype
            $table->integer('wh_rework_run')->default(0)->after('rl_rework_not_use');
            $table->integer('wh_rework_wait')->default(0)->after('wh_rework_run');
            $table->integer('wh_rework_not_use')->default(0)->after('wh_rework_wait');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'normal_run',
                'normal_wait',
                'normal_not_use',
                'process_rw_run',
                'process_rw_wait',
                'process_rw_not_use',
                'rl_rework_run',
                'rl_rework_wait',
                'rl_rework_not_use',
                'wh_rework_run',
                'wh_rework_wait',
                'wh_rework_not_use',
            ]);
        });
    }
};
