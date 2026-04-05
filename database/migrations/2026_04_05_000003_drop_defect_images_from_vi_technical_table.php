<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vi_technical', function (Blueprint $table) {
            $table->dropColumn([
                'defect_image1', 'defect_image2', 'defect_image3',
                'defect_image4', 'defect_image5', 'defect_image6',
                'defect_image7', 'defect_image8', 'defect_image9',
                'defect_image10',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('vi_technical', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $table->longText("defect_image{$i}")->nullable();
            }
        });
    }
};
