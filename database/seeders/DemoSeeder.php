<?php

namespace Database\Seeders;

use App\Actions\Reports\ApproveReport;
use App\Actions\Reports\CreateReport;
use App\Actions\Reports\ManageNotes;
use App\Actions\Reports\ReopenApproved;
use App\Actions\Reports\ReturnForRevision;
use App\Actions\Reports\SaveDraft;
use App\Actions\Reports\StartReview;
use App\Actions\Reports\SubmitReport;
use App\Enums\NoteStatus;
use App\Enums\SignerCapacity;
use App\Enums\UserRole;
use App\Models\District;
use App\Models\Report;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Data demo Kabupaten Garut untuk pengembangan dan presentasi.
 *
 * Kecamatan memakai 42 kecamatan Kabupaten Garut dengan kode Kemendagri
 * 32.05.xx. Seluruh nama orang, nomor surat, dan isi laporan fiktif. Laporan
 * dibuat lewat action yang sama dengan aplikasi, sehingga versi, catatan,
 * audit, dan notifikasi konsisten dengan alur nyata.
 *
 * Hanya berjalan pada APP_ENV local/testing. Kata sandi seluruh akun demo
 * diambil dari DEMO_PASSWORD; tidak ada kata sandi bawaan.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const EMAIL_DOMAIN = 'demo.siaplapor.test';

    /** @var array<string, string> */
    public const DISTRICTS = [
        '32.05.01' => 'Garut Kota',
        '32.05.02' => 'Karangpawitan',
        '32.05.03' => 'Wanaraja',
        '32.05.04' => 'Tarogong Kaler',
        '32.05.05' => 'Tarogong Kidul',
        '32.05.06' => 'Banyuresmi',
        '32.05.07' => 'Samarang',
        '32.05.08' => 'Pasirwangi',
        '32.05.09' => 'Leles',
        '32.05.10' => 'Kadungora',
        '32.05.11' => 'Leuwigoong',
        '32.05.12' => 'Cibatu',
        '32.05.13' => 'Kersamanah',
        '32.05.14' => 'Malangbong',
        '32.05.15' => 'Sukawening',
        '32.05.16' => 'Karangtengah',
        '32.05.17' => 'Bayongbong',
        '32.05.18' => 'Cigedug',
        '32.05.19' => 'Cilawu',
        '32.05.20' => 'Cisurupan',
        '32.05.21' => 'Sukaresmi',
        '32.05.22' => 'Cikajang',
        '32.05.23' => 'Banjarwangi',
        '32.05.24' => 'Singajaya',
        '32.05.25' => 'Cihurip',
        '32.05.26' => 'Peundeuy',
        '32.05.27' => 'Pameungpeuk',
        '32.05.28' => 'Cisompet',
        '32.05.29' => 'Cibalong',
        '32.05.30' => 'Cikelet',
        '32.05.31' => 'Bungbulang',
        '32.05.32' => 'Mekarmukti',
        '32.05.33' => 'Pakenjeng',
        '32.05.34' => 'Pamulihan',
        '32.05.35' => 'Cisewu',
        '32.05.36' => 'Caringin',
        '32.05.37' => 'Talegong',
        '32.05.38' => 'Balubur Limbangan',
        '32.05.39' => 'Selaawi',
        '32.05.40' => 'Cibiuk',
        '32.05.41' => 'Pangatikan',
        '32.05.42' => 'Sucinaraja',
    ];

    private string $password;

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder hanya untuk APP_ENV local atau testing.');
        }

        $this->password = (string) config('siaplapor.demo_password');

        if (mb_strlen($this->password) < 12) {
            throw new RuntimeException('Isi DEMO_PASSWORD (minimal 12 karakter) pada .env sebelum menjalankan DemoSeeder.');
        }

        // Hash sekali; cast `hashed` tidak meng-hash ulang nilai yang sudah ter-hash.
        $this->password = Hash::make($this->password);

        if (User::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN)->exists()) {
            $this->command->warn('Data demo sudah ada; tidak ada yang diubah.');

            return;
        }

        $districts = collect(self::DISTRICTS)->map(fn (string $name, string $code): District => District::query()->updateOrCreate(
            ['code' => $code],
            ['name' => 'Kecamatan '.$name, 'is_active' => true],
        ));

        $kabupaten = $this->user('Rina Kartikasari', 'rina.kabupaten', UserRole::AdminKabupaten);
        $this->user('Dedi Supriatna', 'dedi.kabupaten', UserRole::AdminKabupaten);

        $kecamatan = $districts->map(fn (District $district, string $code): User => $this->user(
            'Operator '.$district->name,
            Str::slug(self::DISTRICTS[$code]),
            UserRole::AdminKecamatan,
            $district,
        ));

        // Periode lama: satu laporan disetujui, lalu periode ditutup.
        $previous = ReportingPeriod::query()->create([
            'name' => 'Triwulan II 2026',
            'starts_on' => '2026-04-01',
            'ends_on' => '2026-06-30',
            'submission_deadline' => '2026-07-10',
            'is_active' => true,
        ]);
        $this->approved($kecamatan['32.05.03'], $kabupaten, $previous, 1, '2026-06-15');
        $previous->update(['is_active' => false]);

        $current = ReportingPeriod::query()->create([
            'name' => 'Triwulan III 2026',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-09-30',
            'submission_deadline' => '2026-10-10',
            'is_active' => true,
        ]);

        $this->approved($kecamatan['32.05.01'], $kabupaten, $current, 2, '2026-08-04');
        $this->approvedAfterRevision($kecamatan['32.05.05'], $kabupaten, $current, 3, '2026-08-11');
        $this->returned($kecamatan['32.05.04'], $kabupaten, $current, 4, '2026-08-18');
        $this->underReview($kecamatan['32.05.02'], $kabupaten, $current, 5, '2026-09-02');
        $this->submitted($kecamatan['32.05.06'], $current, 6, '2026-09-09');
        $this->reopened($kecamatan['32.05.10'], $kabupaten, $current, 7, '2026-08-25');

        // Draf parsial: hanya tahap pertama yang terisi.
        $draft = app(CreateReport::class)->handle($kecamatan['32.05.09'], $current);
        $this->save($kecamatan['32.05.09'], $draft, array_intersect_key(
            $this->payload($kecamatan['32.05.09'], 8, '2026-09-16'),
            array_flip(['report_number', 'supervisor_name', 'supervisor_position', 'assignment_number', 'assignment_date']),
        ));

        $this->command->info(sprintf(
            'Data demo Kabupaten Garut dibuat: %d kecamatan, %d akun, %d laporan. Email akun: <slug>@%s, contoh rina.kabupaten@%s dan garut-kota@%s.',
            $districts->count(),
            $kecamatan->count() + 2,
            Report::query()->count(),
            self::EMAIL_DOMAIN,
            self::EMAIL_DOMAIN,
            self::EMAIL_DOMAIN,
        ));
    }

    private function user(string $name, string $local, UserRole $role, ?District $district = null): User
    {
        $user = new User(['name' => $name, 'email' => $local.'@'.self::EMAIL_DOMAIN, 'password' => $this->password]);
        $user->role = $role;
        $user->district_id = $district?->id;
        $user->is_active = true;
        $user->must_change_password = false;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    private function approved(User $kec, User $kab, ReportingPeriod $period, int $seq, string $date): Report
    {
        $report = $this->submitted($kec, $period, $seq, $date);
        $this->review($kab, $report);
        app(ApproveReport::class)->handle($kab, $report, 'Laporan lengkap dan sesuai.', $report->current_version_id, $report->lock_version);

        return $report->refresh();
    }

    private function approvedAfterRevision(User $kec, User $kab, ReportingPeriod $period, int $seq, string $date): void
    {
        $report = $this->returned($kec, $kab, $period, $seq, $date);
        $notes = $report->notesQuery()->where('status', NoteStatus::Open->value)->get();

        foreach ($notes as $note) {
            app(ManageNotes::class)->addResponse($kec, $report, $note, 'Sudah diperbaiki sesuai catatan.', $report->lock_version);
            $report->refresh();
        }

        $this->save($kec, $report, $this->payload($kec, $seq, $date, revised: true));
        $this->submit($kec, $report);
        $this->review($kab, $report);

        foreach ($notes as $note) {
            app(ManageNotes::class)->decideNote($kab, $report, $note, NoteStatus::Resolved, $report->lock_version);
            $report->refresh();
        }

        app(ApproveReport::class)->handle($kab, $report, 'Perbaikan sudah sesuai.', $report->current_version_id, $report->lock_version);
    }

    private function returned(User $kec, User $kab, ReportingPeriod $period, int $seq, string $date): Report
    {
        $report = $this->submitted($kec, $period, $seq, $date);
        $this->review($kab, $report);

        $notes = app(ManageNotes::class);
        $notes->addNote($kab, $report, 'Sebutkan nama desa dan RT/RW lokasi pengawasan secara lengkap.', 'activity_location', null, $report->lock_version);
        $report->refresh();
        $notes->addNote($kab, $report, 'Uraian belum menyebutkan jumlah pemilih yang dicoret dan alasannya.', 'findings', null, $report->lock_version);
        $report->refresh();

        app(ReturnForRevision::class)->handle($kab, $report, 'Dua bagian perlu dilengkapi.', $report->current_version_id, $report->lock_version);

        return $report->refresh();
    }

    private function underReview(User $kec, User $kab, ReportingPeriod $period, int $seq, string $date): void
    {
        $this->review($kab, $this->submitted($kec, $period, $seq, $date));
    }

    private function reopened(User $kec, User $kab, ReportingPeriod $period, int $seq, string $date): void
    {
        $report = $this->approved($kec, $kab, $period, $seq, $date);
        app(ReopenApproved::class)->handle(
            $kab,
            $report,
            'Tanggal surat perintah tugas tidak sesuai dengan arsip SPT. Mohon diperiksa kembali.',
            $report->current_version_id,
            $report->lock_version,
        );
    }

    private function submitted(User $kec, ReportingPeriod $period, int $seq, string $date): Report
    {
        $report = $this->draft($kec, $period, $this->payload($kec, $seq, $date));
        $this->submit($kec, $report);

        return $report;
    }

    /** @param  array<string, string|null>  $payload */
    private function draft(User $kec, ReportingPeriod $period, array $payload): Report
    {
        $report = app(CreateReport::class)->handle($kec, $period);
        $this->save($kec, $report, $payload);

        return $report;
    }

    /** @param  array<string, string|null>  $payload */
    private function save(User $kec, Report $report, array $payload): void
    {
        app(SaveDraft::class)->handle($kec, $report, $payload, $report->current_version_id, $report->lock_version);
        $report->refresh();
    }

    private function submit(User $kec, Report $report): void
    {
        app(SubmitReport::class)->handle($kec, $report, $report->current_version_id, $report->lock_version);
        $report->refresh();
    }

    private function review(User $kab, Report $report): void
    {
        app(StartReview::class)->handle($kab, $report, $report->current_version_id, $report->lock_version);
        $report->refresh();
    }

    /**
     * Isi Formulir Model A fiktif. Tanggal kegiatan = $date, surat tugas
     * seminggu sebelumnya, pengesahan sehari sesudahnya.
     *
     * @return array<string, string|null>
     */
    private function payload(User $kec, int $seq, string $date, bool $revised = false): array
    {
        /** @var District $district */
        $district = $kec->district;
        $name = Str::after($district->name, 'Kecamatan ');
        $abbr = strtoupper(Str::substr(Str::slug($name, ''), 0, 3));
        $roman = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int) substr($date, 5, 2)];
        $day = fn (int $offset): string => date('Y-m-d', (int) strtotime("$date $offset day"));
        $location = $revised
            ? "Kantor Desa di wilayah Kecamatan $name, RT 002/RW 005"
            : "Kantor Desa di wilayah Kecamatan $name";
        $supervisors = ['Asep Hidayat', 'Neng Yuliani', 'Ujang Permana', 'Euis Rahmawati', 'Cecep Saepudin', 'Lilis Suryani', 'Dadan Ramdani', 'Iis Nurhayati'];

        return [
            'report_number' => sprintf('%03d/LHP/%s/%s/2026', $seq, $abbr, $roman),
            'supervisor_name' => $supervisors[$seq % count($supervisors)],
            'supervisor_position' => 'Anggota Pengawas Kecamatan '.$name,
            'assignment_number' => sprintf('%03d/ST/%s/%s/2026', $seq, $abbr, $roman),
            'assignment_date' => $day(-7),
            'supervisor_address' => "Jalan Raya $name Nomor $seq\nKecamatan $name, Kabupaten Garut",
            'activity_name' => 'Pengawasan pemutakhiran data pemilih berkelanjutan',
            'activity_form' => 'Pengawasan langsung dan uji petik data pemilih',
            'activity_purpose' => 'Memastikan pemutakhiran data pemilih berkelanjutan di tingkat kecamatan berjalan sesuai prosedur dan data pemilih akurat.',
            'activity_target' => "Petugas pemutakhiran data pemilih dan pemilih di Kecamatan $name",
            'activity_start_date' => $date,
            'activity_end_date' => $day(1),
            'activity_start_time' => '08:00',
            'activity_end_time' => '15:30',
            'activity_location' => $location,
            'findings' => implode("\n\n", array_filter([
                "Pengawasan dilakukan terhadap rekapitulasi data pemilih berkelanjutan di Kecamatan $name. Petugas telah menyandingkan data kependudukan terbaru dengan daftar pemilih.",
                'Ditemukan pemilih yang telah meninggal dunia namun masih tercantum dalam daftar, serta pemilih baru yang genap berusia 17 tahun belum tercatat.',
                $revised ? 'Jumlah pemilih yang dicoret sebanyak 14 orang karena meninggal dunia dan 6 orang karena pindah domisili, masing-masing dibuktikan dengan dokumen kependudukan.' : null,
                'Saran perbaikan telah disampaikan secara langsung kepada petugas untuk ditindaklanjuti pada rekapitulasi berikutnya.',
            ])),
            'signing_place' => $name,
            'signing_date' => $day(2),
            'signer_name' => $supervisors[($seq + 3) % count($supervisors)],
            'signer_capacity' => ($seq % 2 === 0 ? SignerCapacity::Ketua : SignerCapacity::Anggota)->value,
        ];
    }
}
