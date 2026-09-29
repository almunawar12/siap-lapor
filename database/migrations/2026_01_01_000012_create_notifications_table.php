<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel notifikasi database Laravel, ditambah `report_id` untuk query ter-scope
 * dan `event_id` untuk deduplikasi. Satu peristiwa bisnis hanya menghasilkan
 * satu notifikasi per penerima, sehingga klik Kirim berulang atau retry job
 * tidak menggandakan notifikasi (ARCHITECTURE.md bagian 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('report_id')->nullable()->constrained('reports')->restrictOnDelete();
            $table->string('event_id', 150)->nullable();
            $table->jsonb('data');
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['report_id']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX notifications_event_recipient_unique
                ON notifications (notifiable_type, notifiable_id, event_id)
                WHERE event_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
