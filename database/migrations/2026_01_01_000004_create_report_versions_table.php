<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->jsonb('payload');
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->timestampTz('submitted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['report_id', 'version_number']);
            $table->index(['report_id', 'submitted_at']);
        });

        // Dibutuhkan composite FK dari reports agar pointer versi tidak dapat
        // menunjuk versi milik laporan lain.
        DB::statement('ALTER TABLE report_versions ADD CONSTRAINT report_versions_report_id_id_unique UNIQUE (report_id, id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('report_versions');
    }
};
