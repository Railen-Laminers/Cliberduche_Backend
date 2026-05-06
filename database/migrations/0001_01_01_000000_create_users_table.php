<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Personal details (replacing the old 'name' column)
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');

            // Contact and login
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('contact_number');

            // Role and status
            $table->enum('role', ['admin', 'client'])->default('client');
            $table->enum('account_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);

            // File paths
            $table->string('valid_id_path')->nullable();
            $table->string('profile_image_path')->nullable();

            // Rejection reason (only used when status is rejected)
            $table->text('rejection_reason')->nullable();

            // Timestamps for approvals/rejections
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Laravel defaults
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};