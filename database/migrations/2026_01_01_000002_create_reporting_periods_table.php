<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('submission_deadline')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['is_active', 'starts_on']);
        });

        DB::statement('ALTER TABLE reporting_periods ADD CONSTRAINT reporting_periods_range_check CHECK (ends_on >= starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('reporting_periods');
    }
};
