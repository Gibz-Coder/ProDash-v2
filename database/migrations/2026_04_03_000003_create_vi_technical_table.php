<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vi_technical', function (Blueprint $table) {
            $table->id();
            $table->string('lot_id');
            $table->string('model')->nullable();
            $table->integer('lot_qty')->nullable();
            $table->string('lipas_yn')->nullable();
            $table->string('work_type')->nullable();
            $table->integer('inspection_times')->nullable();
            $table->integer('inspection_spl')->nullable();
            $table->string('defect_code')->nullable();
            $table->timestamp('technical_start_at')->nullable();
            $table->string('eqp_number')->nullable();
            $table->string('eqp_maker')->nullable();
            $table->longText('defect_image1')->nullable();
            $table->longText('defect_image2')->nullable();
            $table->longText('defect_image3')->nullable();
            $table->longText('defect_image4')->nullable();
            $table->longText('defect_image5')->nullable();
            $table->longText('defect_image6')->nullable();
            $table->longText('defect_image7')->nullable();
            $table->longText('defect_image8')->nullable();
            $table->longText('defect_image9')->nullable();
            $table->longText('defect_image10')->nullable();
            $table->string('technical_result')->nullable();
            $table->text('remarks')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->integer('total_tat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vi_technical');
    }
};
