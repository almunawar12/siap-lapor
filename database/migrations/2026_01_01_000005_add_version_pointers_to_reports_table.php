<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pointer versi dijaga di level database: pasangan (id, current_version_id)
 * harus cocok dengan (report_id, id) pada report_versions, sehingga versi milik
 * laporan lain tidak dapat ditunjuk. Status approved wajib menunjuk versi yang
 * sama dengan current version; status lain wajib approved_version_id null.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE reports ADD CONSTRAINT reports_id_unique UNIQUE (id)');

        DB::statement(<<<'SQL'
            ALTER TABLE reports
                ADD CONSTRAINT reports_current_version_fk
                FOREIGN KEY (id, current_version_id)
                REFERENCES report_versions (report_id, id)
                ON DELETE RESTRICT
                DEFERRABLE INITIALLY IMMEDIATE
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE reports
                ADD CONSTRAINT reports_approved_version_fk
                FOREIGN KEY (id, approved_version_id)
                REFERENCES report_versions (report_id, id)
                ON DELETE RESTRICT
                DEFERRABLE INITIALLY IMMEDIATE
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE reports
                ADD CONSTRAINT reports_approved_version_status_check CHECK (
                    (status = 'approved' AND approved_version_id IS NOT NULL AND approved_version_id = current_version_id)
                    OR (status <> 'approved' AND approved_version_id IS NULL)
                )
        SQL);
    }

    public function down(): void
    {
        foreach ([
            'reports_approved_version_status_check',
            'reports_approved_version_fk',
            'reports_current_version_fk',
            'reports_id_unique',
        ] as $constraint) {
            DB::statement("ALTER TABLE reports DROP CONSTRAINT IF EXISTS {$constraint}");
        }

        unset($constraint);
    }
};
