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
        Schema::create('qc_defect_class', function (Blueprint $table) {
            $table->id();
            $table->string('defect_code')->unique();
            $table->string('defect_name');
            $table->string('defect_class');
            $table->string('defect_flow');
            $table->string('created_by');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_defect_class');
    }
};
