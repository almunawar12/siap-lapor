<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory ini tidak membuat versi; gunakan action CreateReport atau helper
 * pengujian agar pointer versi dan constraint composite tetap konsisten.
 *
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $district = District::factory();

        return [
            'district_id' => $district,
            'reporting_period_id' => ReportingPeriod::factory(),
            'created_by' => User::factory()->kecamatan(),
            'status' => ReportStatus::Draft,
            'report_number' => null,
            'lock_version' => 0,
        ];
    }
}
