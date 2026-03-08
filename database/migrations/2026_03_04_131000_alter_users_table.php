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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
            $table->string('phone', 20)->unique()->nullable()->after('name');
            $table->enum('status', ['active', 'inactive', 'disabled'])->default('active')->after('password');
            $table->softDeletes();
            $table->foreign('role_id')->references('role_id')->on('roles')->onDelete('restrict');
            $table->index(['status', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['users_status_role_id_index']);
            $table->dropColumn(['role_id', 'phone', 'status', 'deleted_at']);
        });
    }
};
