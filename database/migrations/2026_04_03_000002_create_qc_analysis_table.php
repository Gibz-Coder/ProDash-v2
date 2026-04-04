<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_analysis', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('model')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->string('lipas_yn')->nullable();
            $table->string('work_type')->nullable();
            $table->integer('inspection_times')->nullable();
            $table->integer('inspection_spl')->nullable();
            $table->string('defect_code')->nullable();
            $table->timestamp('analysis_start_at')->nullable();
            $table->longText('mold_image_before')->nullable();
            $table->longText('mold_image_after')->nullable();
            $table->string('mold_result')->nullable();
            $table->longText('reli_image_before')->nullable();
            $table->longText('reli_image_after')->nullable();
            $table->string('reli_result')->nullable();
            $table->longText('dipping_image_before')->nullable();
            $table->longText('dipping_image_after')->nullable();
            $table->string('dipping_result')->nullable();
            $table->longText('reflow_image_before')->nullable();
            $table->longText('reflow_image_after')->nullable();
            $table->string('reflow_result')->nullable();
            $table->longText('measure_image_before')->nullable();
            $table->longText('measure_image_after')->nullable();
            $table->string('measure_result')->nullable();
            $table->timestamp('analysis_completed_at')->nullable();
            $table->string('analysis_result')->nullable();
            $table->text('remarks')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('total_tat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_analysis');
    }
};
