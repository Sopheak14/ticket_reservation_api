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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->enum('type', ['booking_confirmation', 'payment_success', 'payment_failed','trip_reminder', 'cancellation', 'schedule_update', 'general',]);
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->enum('channel', ['in_app', 'email', 'sms'])->default('in_app');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
