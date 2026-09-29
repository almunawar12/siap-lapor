<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\User;
use App\Support\ReportPayload;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportVersion>
 */
class ReportVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'report_id' => Report::factory(),
            'version_number' => 1,
            'payload' => ReportPayload::empty(),
            'schema_version' => 1,
            'created_by' => User::factory()->kecamatan(),
        ];
    }
}
