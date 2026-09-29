<?php

use App\Enums\NoteStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->restrictOnDelete();
            $table->string('field_key', 60)->nullable();
            $table->foreignId('attachment_id')->nullable()
                ->constrained('version_attachments')->restrictOnDelete();
            $table->text('body');
            $table->string('status', 20);
            $table->foreignId('resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();

            $table->index(['review_id', 'status']);
        });

        $statuses = collect(NoteStatus::values())
            ->map(fn (string $status): string => "'".$status."'")
            ->implode(', ');

        DB::statement("ALTER TABLE revision_notes ADD CONSTRAINT revision_notes_status_check CHECK (status IN ({$statuses}))");

        // Catatan umum jika kedua target null; keduanya tidak boleh terisi bersamaan.
        DB::statement(<<<'SQL'
            ALTER TABLE revision_notes ADD CONSTRAINT revision_notes_target_check CHECK (
                field_key IS NULL OR attachment_id IS NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE revision_notes ADD CONSTRAINT revision_notes_resolved_check CHECK (
                (status = 'resolved' AND resolved_by IS NOT NULL AND resolved_at IS NOT NULL)
                OR (status = 'open' AND resolved_by IS NULL AND resolved_at IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_notes');
    }
};
