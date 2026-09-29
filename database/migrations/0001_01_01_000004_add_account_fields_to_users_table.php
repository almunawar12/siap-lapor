<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->after('email');
            $table->foreignId('district_id')->nullable()->after('role')
                ->constrained('districts')->restrictOnDelete();
            $table->boolean('is_active')->default(true)->after('district_id');
            $table->boolean('must_change_password')->default(false)->after('is_active');
        });

        $roles = collect(UserRole::values())
            ->map(fn (string $role): string => "'".$role."'")
            ->implode(', ');

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ({$roles}))");

        // Akun kecamatan wajib terikat satu kecamatan; akun kabupaten tidak boleh terikat.
        DB::statement(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT users_role_district_check CHECK (
                (role = 'admin_kecamatan' AND district_id IS NOT NULL)
                OR (role = 'admin_kabupaten' AND district_id IS NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_district_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropColumn(['role', 'is_active', 'must_change_password']);
        });
    }
};
