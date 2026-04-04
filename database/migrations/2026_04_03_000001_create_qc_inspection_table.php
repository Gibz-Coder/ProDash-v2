<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_inspection', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('model')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->string('lipas_yn')->nullable();
            $table->string('work_type')->nullable();
            $table->integer('inspection_times')->nullable();
            $table->integer('inspection_spl')->nullable();
            $table->string('inspected_part')->nullable();
            $table->string('inspection_result')->nullable();
            $table->string('mainlot_result')->nullable();
            $table->string('r_rework_result')->nullable();
            $table->string('l_rework_result')->nullable();
            $table->string('defect_code')->nullable();
            $table->string('defect_flow')->nullable();
            $table->timestamp('analysis_start_at')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
            $table->string('analysis_result')->nullable();
            $table->timestamp('technical_start_at')->nullable();
            $table->timestamp('technical_completd_at')->nullable();
            $table->string('technical_result')->nullable();
            $table->timestamp('production_start')->nullable();
            $table->timestamp('production_completed_at')->nullable();
            $table->string('production_result')->nullable();
            $table->string('final_decision')->nullable();
            $table->text('remarks')->nullable();
            $table->string('output_status')->nullable();
            $table->timestamp('lot_completed_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('total_tat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspection');
    }
};
