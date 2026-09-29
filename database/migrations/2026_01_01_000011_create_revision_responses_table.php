<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggapan bersifat append-only: tidak diubah dan tidak dihapus, hanya
 * created_at. Tanggapan terhubung ke versi kerja yang menanggapinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_note_id')->constrained('revision_notes')->restrictOnDelete();
            $table->foreignId('report_version_id')->constrained('report_versions')->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['revision_note_id', 'created_at']);
            $table->index(['report_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_responses');
    }
};
