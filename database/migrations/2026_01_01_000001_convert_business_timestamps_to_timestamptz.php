<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ARCHITECTURE.md bagian 3 mensyaratkan timestamptz untuk tabel bisnis.
 * Tabel users/districts dibuat starter kit dengan `timestamp without time zone`
 * berisi UTC, jadi konversi memakai `AT TIME ZONE 'UTC'` dan tidak mengubah
 * instan yang tersimpan. Tabel infrastruktur (sessions/cache/jobs) dibiarkan.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    protected array $columns = [
        'users' => ['email_verified_at', 'created_at', 'updated_at'],
        'districts' => ['created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->convertTo('timestamptz');
    }

    public function down(): void
    {
        $this->convertTo('timestamp without time zone');
    }

    /**
     * Bentuk SQL sama untuk kedua arah: nilai lama selalu dibaca sebagai UTC.
     */
    protected function convertTo(string $type): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement(
                    "ALTER TABLE {$table} ALTER COLUMN {$column} TYPE {$type} USING {$column} AT TIME ZONE 'UTC'"
                );
            }
        }
    }
};
