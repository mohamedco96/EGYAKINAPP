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
                if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Event information
            $table->string('event_type'); // created, updated, deleted, login, logout, etc.
            $table->string('auditable_type')->nullable(); // Model class name
            $table->unsignedBigInteger('auditable_id')->nullable(); // Model ID

            // User information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable(); // User class name
            $table->string('user_name')->nullable(); // User name for quick reference
            $table->string('user_email')->nullable(); // User email for quick reference

            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE, etc.
            $table->json('request_data')->nullable(); // Request parameters

            // Change information
            $table->json('old_values')->nullable(); // Original values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->json('changed_attributes')->nullable(); // List of changed attributes

            // Additional context
            $table->string('tags')->nullable(); // Comma-separated tags for categorization
            $table->text('description')->nullable(); // Human-readable description
            $table->json('metadata')->nullable(); // Additional context data

            // Session and device info
            $table->string('session_id')->nullable();
            $table->string('device_type')->nullable(); // web, mobile, api
            $table->string('platform')->nullable(); // iOS, Android, Web

            // Location and timing
            $table->string('location')->nullable(); // Geographic location if available
            $table->timestamp('performed_at')->useCurrent();

            $table->timestamps();

            // Indexes for performance
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'user_type']);
            $table->index(['event_type']);
            $table->index(['performed_at']);
            $table->index(['ip_address']);
            $table->index(['session_id']);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
