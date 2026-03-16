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
        Schema::create('endline_delay', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('model')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->string('lipas_yn')->nullable();
            $table->integer('qc_result')->nullable();
            $table->string('qc_defect')->nullable();
            $table->string('defect_class')->nullable();
            $table->dateTime('qc_ana_start')->nullable();
            $table->string('qc_ana_result')->nullable();
            $table->integer('qc_ana_tat')->nullable();
            $table->dateTime('vi_techl_start')->nullable();
            $table->string('vi_techl_result')->nullable();
            $table->integer('vi_techl_tat')->nullable();
            $table->string('final_decision')->nullable();
            $table->integer('total_tat')->nullable();
            $table->text('remarks')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endline_delay');
    }
};
