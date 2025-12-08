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
        Schema::create('monthly_plan', function (Blueprint $table) {
            $table->id();
            $table->string('model_15')->nullable();
            $table->string('lot_size')->nullable();
            $table->string('orig_lipas')->nullable();
            $table->string('vi_lipas')->nullable();
            $table->integer('volpas_plan')->nullable();
            $table->integer('lipas_plan')->nullable();
            $table->integer('vi_short')->nullable();
            $table->integer('oi_short')->nullable();
            $table->integer('week_num')->nullable();
            $table->date('Date')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_plan');
    }
};
