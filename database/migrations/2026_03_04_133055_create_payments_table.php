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
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('payment_reference', 100)->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_status', ['pending', 'success', 'failed', 'refunded',])->default('pending');
            $table->dateTime('payment_datetime')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('payment_method_id')->references('payment_method_id')->on('payment_methods')->onDelete('restrict');
            $table->index(['payment_status', 'payment_datetime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
