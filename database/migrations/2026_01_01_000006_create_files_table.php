<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 50);
            $table->string('path', 255)->unique();
            $table->string('original_name', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
