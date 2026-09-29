<?php

use App\Enums\AttachmentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('version_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_version_id')->constrained('report_versions')->restrictOnDelete();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->string('category', 30);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('replaces_attachment_id')->nullable();
            $table->timestampsTz();

            $table->unique(['report_version_id', 'file_id']);
            $table->foreign('replaces_attachment_id')->references('id')->on('version_attachments')->restrictOnDelete();
        });

        $categories = collect(AttachmentCategory::values())
            ->map(fn (string $category): string => "'".$category."'")
            ->implode(', ');

        DB::statement("ALTER TABLE version_attachments ADD CONSTRAINT version_attachments_category_check CHECK (category IN ({$categories}))");
    }

    public function down(): void
    {
        Schema::dropIfExists('version_attachments');
    }
};
