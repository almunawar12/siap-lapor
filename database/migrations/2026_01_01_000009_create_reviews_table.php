<?php

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_version_id')->constrained('report_versions')->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 30);
            $table->text('general_note')->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->index(['report_version_id', 'status']);
        });

        $statuses = collect(ReviewStatus::values())
            ->map(fn (string $status): string => "'".$status."'")
            ->implode(', ');

        DB::statement("ALTER TABLE reviews ADD CONSTRAINT reviews_status_check CHECK (status IN ({$statuses}))");

        // Pemeriksaan hanya boleh punya satu pemilik aktif per versi. Partial
        // unique index inilah pengaman akhir terhadap dua keputusan bersamaan.
        DB::statement("CREATE UNIQUE INDEX reviews_one_active_per_version ON reviews (report_version_id) WHERE status = 'active'");

        // Keputusan wajib punya waktu; pemeriksaan aktif belum punya.
        DB::statement(<<<'SQL'
            ALTER TABLE reviews ADD CONSTRAINT reviews_decided_at_check CHECK (
                (status = 'active' AND decided_at IS NULL)
                OR (status <> 'active' AND decided_at IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
