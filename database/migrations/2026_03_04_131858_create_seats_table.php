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
        Schema::create('seats', function (Blueprint $table) {
            $table->id('seat_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->string('seat_number', 10);
            $table->enum('status', ['available', 'reserved', 'maintenance'])->default('available');
            $table->timestamps();
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            $table->unique(['vehicle_id', 'seat_number']);
            $table->index(['vehicle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
