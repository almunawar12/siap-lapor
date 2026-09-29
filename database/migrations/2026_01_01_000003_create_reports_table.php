<?php

use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained('districts')->restrictOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('report_number', 150)->nullable()->unique();
            $table->string('status', 30);
            // Pointer versi ditambahkan setelah report_versions tersedia.
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('approved_version_id')->nullable();
            $table->integer('lock_version')->default(0);
            $table->timestampTz('first_submitted_at')->nullable();
            $table->timestampsTz();

            $table->index(['district_id', 'reporting_period_id', 'status']);
            $table->index(['status', 'updated_at']);
        });

        $statuses = collect(ReportStatus::values())
            ->map(fn (string $status): string => "'".$status."'")
            ->implode(', ');

        DB::statement("ALTER TABLE reports ADD CONSTRAINT reports_status_check CHECK (status IN ({$statuses}))");
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
