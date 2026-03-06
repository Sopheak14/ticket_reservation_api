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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('channel', ['in_app', 'email', 'sms'])->default('in_app');
            $table->string('title', 255);
            $table->text('message');
            $table->dateTime('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');
            $table->enum('type', ['booking_confirmation', 'payment_success', 'payment_failed','trip_reminder', 'cancellation', 'schedule_update', 'general',])->default('general');
            $table->timestamps();
            $table->foreign('booking_id')->references('booking_id')->on('bookings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['booking_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
