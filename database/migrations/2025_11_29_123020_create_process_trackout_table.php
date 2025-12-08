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
        Schema::create('process_trackout', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id')->nullable();
            $table->string('model_15')->nullable();
            $table->string('lot_size')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->char('auto_yn', 1)->nullable();
            $table->string('work_type')->nullable();
            $table->string('eqp_line')->nullable();
            $table->string('derive')->nullable();
            $table->integer('week_num')->nullable();
            $table->date('Date')->nullable();
            $table->char('lipas_yn', 1)->nullable();
            $table->string('cut_off')->nullable();
            $table->decimal('stagnant_tat', 10, 2)->nullable();
            $table->string('worktype_2')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_trackout');
    }
};
