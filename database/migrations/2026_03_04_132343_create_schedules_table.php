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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id('schedule_id');
            $table->unsignedBigInteger('vehicle_id');
            $table->unsignedBigInteger('route_id');
            $table->dateTime('departure_datetime');
            $table->dateTime('arrival_datetime');
            $table->date('travel_date');
            $table->integer('available_seats')->default(0);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->enum('status', ['active', 'cancelled', 'completed', 'maintenance',])->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('restrict');
            $table->foreign('route_id')->references('route_id')->on('routes')->onDelete('restrict');
            $table->index(['travel_date', 'status']);
            $table->index(['route_id', 'travel_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
