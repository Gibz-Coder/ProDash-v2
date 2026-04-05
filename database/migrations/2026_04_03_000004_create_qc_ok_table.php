<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_ok', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('model')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->string('lipas_yn')->nullable();
            $table->string('work_type')->nullable();
            $table->integer('inspection_times')->nullable();
            $table->integer('inspection_spl')->nullable();
            $table->string('inspection_result')->nullable();
            $table->string('analysis_result')->nullable();
            $table->string('technical_result')->nullable();
            $table->string('pending')->nullable();
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
        Schema::dropIfExists('qc_ok');
    }
};
