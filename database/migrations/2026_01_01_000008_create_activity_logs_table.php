<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log append-only: hanya created_at, tanpa updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->nullable()->constrained('reports')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 60);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->foreignId('report_version_id')->nullable()->constrained('report_versions')->restrictOnDelete();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['report_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
