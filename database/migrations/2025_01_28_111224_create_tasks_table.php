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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->ipAddress('created_ip')->nullable();
            $table->string('api_url');
            $table->enum('api_type', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']); // or any other method types
            $table->json('api_payload')->nullable();
            $table->json('api_parameters')->nullable();
            $table->json('api_headers')->nullable();
            $table->string('task_name');
            $table->enum('task_status', ['created', 'completed', 'failed', 'paused', 'other', 'none'])->default('created');
            $table->timestamp('task_execute_at')->nullable();
            $table->mediumText('api_response')->nullable();
            $table->string('response_email')->nullable();
            $table->enum('api_status', ['pending', 'success', 'failure', 'in_progress'])->default('pending');
            $table->mediumText('failed_error')->nullable();
            $table->integer('api_status_code')->nullable();
            $table->timestamp('schedule_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
