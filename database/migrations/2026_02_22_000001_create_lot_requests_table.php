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
        Schema::create('lot_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->unique()->comment('Unique request number');
            $table->string('mc_no')->comment('Machine/Equipment number');
            $table->string('line')->comment('Production line');
            $table->string('area')->comment('Production area');
            $table->string('requestor')->comment('Person who made the request');
            $table->string('lot_no')->comment('Lot number');
            $table->string('model')->comment('Product model');
            $table->integer('quantity')->comment('Quantity requested');
            $table->char('lipas', 1)->default('N')->comment('LIPAS flag: Y or N');
            $table->string('lot_tat')->nullable()->comment('Lot Turn Around Time');
            $table->timestamp('requested')->comment('Request timestamp');
            $table->timestamp('completed')->nullable()->comment('Completion timestamp');
            $table->string('response_time')->nullable()->comment('Time taken to complete (e.g., 2h 15m)');
            $table->string('response_by')->nullable()->comment('Person who completed the request');
            $table->string('status')->default('PENDING')->comment('Request status: PENDING, COMPLETED, REJECTED');
            $table->text('remarks')->nullable()->comment('Additional notes or comments');
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('request_no');
            $table->index('mc_no');
            $table->index('line');
            $table->index('area');
            $table->index('status');
            $table->index('lipas');
            $table->index('requested');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lot_requests');
    }
};
