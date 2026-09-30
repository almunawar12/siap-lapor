# SIAP LAPOR

**Sistem Informasi Administrasi Pengawasan dan Pelaporan**
_Pelaporan tertata, pengawasan terlacak._

Aplikasi internal untuk menyusun, mengirim, memeriksa, merevisi, dan
mengarsipkan Laporan Hasil Pengawasan (LHP) Pemutakhiran Data Pemilih
Berkelanjutan mengikuti **Formulir Model A**. Judul surat pada hasil cetak tetap
memakai penamaan Formulir Model A.

Kontrak kebutuhan ada di `PRD.md`, rancangan implementasi di `ARCHITECTURE.md`,
aturan kerja agent di `AGENTS.md`.

## Status implementasi

Tahap yang sudah selesai: **M1 fondasi dan otorisasi**, **M2 formulir Model A,
versi laporan, dan lampiran privat**, **M3 pemeriksaan, catatan revisi,
persetujuan, dan buka kembali**, **M4 cetak PDF Model A dan notifikasi dalam
aplikasi**, sebagian **M5**: data demo Kabupaten Garut, uji konkurensi dua proses
paralel, dan perbaikan aksesibilitas formulir (lihat [Tahap berikutnya](#tahap-berikutnya)
untuk yang belum).

Sudah bekerja:

- Login dan logout dengan sesi Laravel, rate limit, dan pesan bahasa Indonesia.
- Registrasi publik tidak tersedia (tidak ada route-nya sama sekali).
- Dua role saja: `admin_kecamatan` dan `admin_kabupaten`, dijaga CHECK constraint.
- Data kecamatan (`districts`) dan akun pengguna beserta status aktif.
- Akun kecamatan terikat tepat satu kecamatan; akun kabupaten tidak terikat kecamatan.
- Perintah bootstrap Admin Kabupaten dengan input kata sandi tersembunyi.
- Pengelolaan akun Admin Kecamatan dan data kecamatan oleh Admin Kabupaten.
- Middleware akun aktif yang juga memutus sesi yang masih berjalan.
- Layout responsif shadcn/ui: sidebar, header, menu profil, dashboard per role.
- Dashboard hanya menampilkan angka dari data nyata, selebihnya empty state.
- Periode pelaporan dikelola Admin Kabupaten; periode nonaktif memblokir laporan baru.
- Formulir Model A bertahap (Data Pengawas → Kegiatan → Hasil → Pengesahan & Lampiran)
  dengan simpan draf parsial, pratinjau, dan konfirmasi sebelum kirim.
- Payload versi tervalidasi sebagai JSONB dengan daftar key kanonik, bukan JSON bebas.
- Optimistic locking `lock_version`: penyimpanan bentrok ditolak dan tidak menimpa
  perubahan orang lain.
- Submit membekukan versi (immutable) dan membekukan snapshot kecamatan/instansi
  dari konfigurasi server; klik Kirim berulang tidak menggandakan versi atau audit.
- Lampiran privat di luar webroot: PDF/JPEG/PNG, diperiksa berdasarkan isi berkas,
  unduhan hanya melalui route ber-policy, dan berkas yatim dibersihkan saat rollback.
- Daftar LHP dengan pencarian nomor/nama kegiatan, filter status/periode/kecamatan,
  dan pagination server.
- Riwayat versi dan halaman versi historis.
- Activity log append-only untuk seluruh transisi, catatan, tanggapan, dan lampiran.
- Pemeriksaan dengan satu pemilik aktif per versi, dijaga partial unique index
  `reviews(report_version_id) WHERE status='active'`.
- Ambil alih pemeriksaan dengan alasan wajib; pemeriksa lama dan baru tercatat.
- Catatan revisi umum, terkait field Model A tertentu, atau terkait lampiran tertentu.
- Tanggapan kecamatan per catatan, append-only, terikat ke versi kerja yang menanggapi.
  Tanggapan tidak menyelesaikan catatan; itu tetap keputusan Admin Kabupaten.
- Pengembalian menolak tanpa catatan terbuka dan membuat tepat satu versi kerja baru;
  versi yang diperiksa tidak pernah berubah dan link lampiran disalin tanpa menyalin byte.
- Pengiriman ulang wajib menanggapi setiap catatan terbuka dan tidak membuat versi ekstra.
- Persetujuan menolak bila ada catatan terbuka dari siklus mana pun dan menunjuk versi
  tepat yang diperiksa; hanya pemeriksa aktif yang dapat memutuskan.
- Buka kembali laporan disetujui: alasan wajib, keputusan approved lama tetap utuh pada
  riwayat, laporan keluar dari hitungan disetujui, versi kerja baru dibuat.
- Perbandingan dua versi: nilai sebelum/sesudah per field dan perubahan daftar lampiran.
- Riwayat siklus pemeriksaan dan riwayat aktivitas pada halaman detail.

- Cetak PDF Formulir Model A per versi dari snapshot versi tersebut, bukan draf terkini.
  Penanda BELUM TERVERIFIKASI untuk versi yang belum disetujui dan VERSI HISTORIS untuk
  versi yang pernah disetujui tetapi bukan persetujuan aktif.
- PDF A4 potret, font Unicode (DejaVu Sans), paragraf uraian utuh, uraian panjang boleh
  multipage tanpa terpotong, blok pengesahan tidak dipecah di tengah.
- PDF diunduh lewat route ber-policy dengan header `nosniff`; nama berkas disanitasi.
- Notifikasi dalam aplikasi: submitted/resubmitted ke Admin Kabupaten aktif;
  returned/approved/reopened ke akun kecamatan aktif pada wilayah laporan.
- Notifikasi dibuat setelah commit dan dideduplikasi per (penerima, peristiwa) lewat
  unique index, sehingga klik ganda atau retry tidak menggandakan.
- Halaman notifikasi dengan penanda belum dibaca, tandai dibaca per item atau semuanya,
  serta lonceng berhitung pada header.

Belum ada: ekspor massal dan ekspor Excel (di luar lingkup MVP).
Lihat bagian [Tahap berikutnya](#tahap-berikutnya).

## Versi runtime yang diverifikasi

| Komponen   | Versi terpasang saat pengembangan |
| ---------- | --------------------------------- |
| PHP        | 8.3.31 (NTS)                      |
| Laravel    | 13.34.0                           |
| Inertia    | `inertiajs/inertia-laravel` ^3.0  |
| PostgreSQL | 17.2                              |
| Node.js    | 22.12.0                           |
| npm        | 10.9.0                            |
| React      | 19                                |
| Tailwind   | 4                                 |
| Pest       | 4                                 |
| Dompdf     | `barryvdh/laravel-dompdf` ^3.1    |

Package manager: **npm** (`package-lock.json`). `composer.lock` ikut di-commit.

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

## Konfigurasi database PostgreSQL

Aplikasi memakai PostgreSQL, bukan SQLite. Buat dua database: satu untuk
pengembangan, satu khusus pengujian.

```sql
CREATE DATABASE db_siaplapor;
CREATE DATABASE db_siaplapor_test;
```

Isi bagian berikut pada `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_siaplapor
DB_USERNAME=postgres
DB_PASSWORD=          # isi sendiri, jangan di-commit
DB_TEST_DATABASE=db_siaplapor_test
```

Nama database pengujian juga tertulis pada `phpunit.xml` (`DB_DATABASE`), karena
`.env` belum termuat saat XML dibaca. Ubah keduanya bila memakai nama lain.

## Migration

```bash
php artisan migrate
```

Jangan menjalankan `migrate:fresh` pada database yang sudah berisi data.

## Bootstrap akun pertama

Tidak ada kredensial bawaan dan seeder tidak membuat akun apa pun. Akun Admin
Kabupaten pertama dibuat lewat perintah interaktif; kata sandi diminta secara
tersembunyi dan tidak pernah menjadi argumen perintah.

```bash
php artisan siaplapor:create-admin-kabupaten
```

Perintah akan menanyakan nama, email, dan kata sandi (minimal 12 karakter, dua
kali input). Nama dan email juga dapat diisi lewat opsi `--name` dan `--email`;
kata sandi tetap wajib lewat prompt.

Setelah masuk sebagai Admin Kabupaten:

1. Buka **Data Kecamatan**, isi kode dan nama kecamatan sesuai dokumen resmi.
2. Buka **Akun Kecamatan → Tambah Akun**, pilih kecamatan, dan tetapkan kata
   sandi sementara. Pengguna wajib menggantinya dari halaman Profil.

Sampaikan kata sandi sementara lewat kanal resmi Anda. Aplikasi ini tidak
mengirim email atau pesan otomatis.

## Data demo Kabupaten Garut (local/testing saja)

`DemoSeeder` terpisah dari `DatabaseSeeder` dan menolak berjalan di luar
`APP_ENV=local` atau `testing`. Isinya:

- 42 kecamatan Kabupaten Garut dengan kode Kemendagri `32.05.01`–`32.05.42`
  (dibuat/diperbarui berdasarkan kode, jadi aman bila sebagian sudah ada).
- 2 akun Admin Kabupaten dan 1 akun Admin Kecamatan per kecamatan, semua bernama
  fiktif, email `<slug>@demo.siaplapor.test`, misalnya
  `rina.kabupaten@demo.siaplapor.test` dan `garut-kota@demo.siaplapor.test`.
- Periode Triwulan II 2026 (ditutup) dan Triwulan III 2026 (aktif).
- 8 laporan fiktif yang dibuat lewat action aplikasi, mencakup semua status:
  draf parsial, diajukan, sedang diperiksa, perlu revisi, disetujui langsung,
  disetujui setelah satu siklus revisi, dan disetujui lalu dibuka kembali.
  Lampiran tidak diseed.

Tidak ada kata sandi bawaan. Isi `DEMO_PASSWORD` (minimal 12 karakter) pada
`.env`, lalu:

```bash
php artisan db:seed --class=DemoSeeder
```

Seeder berhenti tanpa mengubah apa pun bila akun `@demo.siaplapor.test` sudah
ada. Jangan jalankan pada database produksi.

## Menjalankan aplikasi

Mode pengembangan (server PHP, queue listener, dan Vite sekaligus):

```bash
composer dev
```

Atau terpisah:

```bash
php artisan serve      # http://localhost:8000
npm run dev
```

Produksi:

```bash
npm run build
php artisan serve      # atau arahkan web server ke direktori public/
```

## Pengujian dan pemeriksaan

```bash
php artisan test        # Pest, memakai db_siaplapor_test
composer lint:check     # Pint (format PHP)
composer types:check    # PHPStan / Larastan
npm run types:check     # tsc --noEmit
npm run check           # lint + format frontend (vite-plus)
npm run build           # build produksi frontend
```

`composer test` menjalankan lint, PHPStan, dan Pest sekaligus.

Suite `tests/Concurrency` menjalankan dua proses PHP sungguhan secara bersamaan
(`tests/Concurrency/worker.php`) terhadap PostgreSQL: kirim ganda, dua Admin
Kabupaten memulai pemeriksaan serentak, dan persetujuan ganda. Suite ini memakai
`DatabaseTruncation` (data harus di-commit agar terlihat proses lain), jadi isi
database pengujian dikosongkan setelah tiap test. Jalankan terpisah dengan
`php artisan test --testsuite=Concurrency`.
`composer ci:check` menambah pemeriksaan frontend.

Pengujian memakai PostgreSQL sungguhan, bukan SQLite, karena CHECK constraint
role/kecamatan dan perilaku JSONB pada tahap berikutnya tidak dapat diuji di
SQLite. `php artisan test` akan memigrasikan ulang `db_siaplapor_test`, jadi
pastikan nama database pengujian berbeda dari database kerja Anda.

## Konfigurasi identitas instansi

Nama instansi belum dikonfirmasi pemilik proyek (percakapan awal menyebut KPU,
foto formulir menyebut Bawaslu). Karena itu nama instansi dan nama kabupaten
berupa konfigurasi, bukan nilai hardcode. Isi pada `.env`:

```dotenv
APP_NAME="SIAP LAPOR"
APP_TAGLINE="Pelaporan tertata, pengawasan terlacak."
INSTITUTION_NAME=          # contoh: Badan Pengawas Pemilihan Umum
REGENCY_NAME=              # contoh: Kabupaten Contoh
```

Dibaca melalui `config/instansi.php`. Bila dikosongkan, aplikasi tetap berjalan
dan baris instansi tidak ditampilkan — tidak ada nama instansi karangan.

## Penyimpanan privat dan batas lampiran

Lampiran disimpan pada disk `local`, yang berakar di `storage/app/private` — di
luar webroot. **Jangan** menjalankan `storage:link` untuk lampiran: berkas hanya
boleh diakses lewat route `reports/{report}/attachments/{attachment}/download`
yang memeriksa policy laporan.

Batas dapat diubah dari `.env` (lihat `config/siaplapor.php`):

```dotenv
ATTACHMENT_DISK=local
ATTACHMENT_MAX_SIZE_KB=10240   # 10 MB per berkas
ATTACHMENT_MAX_FILES=10        # per versi laporan
```

Tipe berkas dibatasi PDF, JPEG, dan PNG, diperiksa dari isi berkas (`mimetypes`),
bukan hanya ekstensi. SVG, HTML, dan executable ditolak.

## Backup dan restore

Backup database dan direktori `storage/app` sebagai satu set, karena metadata
lampiran di database dan byte file saling merujuk.

```bash
pg_dump -U postgres -Fc db_siaplapor > backup-db.dump
tar -czf backup-storage.tar.gz storage/app
```

Restore:

```bash
pg_restore -U postgres -d db_siaplapor --clean backup-db.dump
tar -xzf backup-storage.tar.gz
```

Uji restore secara berkala pada database terpisah. Lakukan backup sebelum
migration yang berisiko.

## Deployment

- Arahkan web server ke direktori `public/`.
- `APP_DEBUG=false` dan `APP_ENV=production`.
- HTTPS agar cookie sesi bersifat secure.
- PostgreSQL dan storage privat yang persisten.
- Worker queue belum diperlukan: notifikasi dikirim sinkron (lihat bagian Notifikasi).
- Semua secret lewat environment; `.env` tidak pernah di-commit.

## Struktur pokok

```
app/Enums/                                 UserRole, ReportStatus, ReviewStatus,
                                           NoteStatus, SignerCapacity,
                                           AttachmentCategory
app/Models/                                User, District, ReportingPeriod, Report,
                                           ReportVersion, File, VersionAttachment,
                                           Review, RevisionNote, RevisionResponse,
                                           ActivityLog
app/Actions/Reports/                       CreateReport, SaveDraft, SubmitReport,
                                           AttachFile, DetachFile, StartReview,
                                           TakeoverReview, ManageNotes,
                                           ReturnForRevision, ApproveReport,
                                           ReopenApproved, CreateWorkingVersion,
                                           RecordActivity
app/Support/ReportPayload.php              Kontrak payload Formulir Model A
app/Support/ReportPresenter.php            Props Inertia ter-scope
app/Services/Pdf/ReportPdf.php             Cetak Model A dari snapshot versi
app/Notifications/                         Notifikasi database + channel kustom
resources/views/pdf/model-a.blade.php      Template cetak, terpisah dari React
app/Policies/                              Otorisasi laporan, versi, lampiran, akun
app/Exceptions/StaleReportException.php     Konflik penyimpanan (409 / error bag)
app/Http/Middleware/EnsureAccountIsActive  Blokir akun nonaktif tiap request
app/Http/Requests/                         Validasi dan whitelist field
app/Console/Commands/CreateAdminKabupaten  Bootstrap akun pertama
config/instansi.php                        Identitas instansi (configurable)
config/siaplapor.php                       Batas lampiran dan pagination
lang/id/                                   Pesan validasi bahasa Indonesia
resources/js/layouts/                      Layout auth dan aplikasi
resources/js/pages/                        Halaman Inertia
resources/js/components/ui/                Komponen shadcn/ui
tests/Feature/                             Pengujian berbasis risiko
```

## Alur kerja lengkap

Panduan pengguna langkah demi langkah, termasuk diagram status dan tabel
"tombol tidak muncul?", ada di [docs/PANDUAN-ALUR.md](docs/PANDUAN-ALUR.md).

1. Admin Kabupaten membuka **Periode Pelaporan**.
2. Admin Kecamatan **Buat LHP** → isi 4 tahap → **Simpan Draf** → **Kirim**.
3. Admin Kabupaten membuka detail laporan → **Mulai Pemeriksaan**.
4. Menambahkan catatan revisi (umum / per field / per lampiran), lalu
   **Kembalikan untuk Revisi** (minimal satu catatan terbuka) atau
   **Setujui Laporan** (tanpa catatan terbuka).
5. Admin Kecamatan membaca catatan, menulis tanggapan tiap catatan, memperbaiki
   isian, lalu **Kirim** ulang.
6. Admin Kabupaten memeriksa ulang, menandai catatan selesai, lalu menyetujui.
7. Bila perlu, **Buka Kembali** laporan disetujui dengan alasan wajib.

Pemeriksaan punya satu pemilik aktif. Admin Kabupaten lain dapat melihat, tetapi
harus **Ambil Alih Pemeriksaan** dengan alasan sebelum memutuskan.

## Cetak PDF

PDF dibuat on-demand dari payload versi yang diminta:

```
GET /reports/{report}/versions/{version}/pdf
```

Renderer: `barryvdh/laravel-dompdf` (Dompdf), dipilih setelah resolver Composer
memverifikasi kompatibilitas dengan Laravel 13 dan PHP 8.3. Remote asset fetching
dinonaktifkan, jadi template tidak mengambil gambar atau CSS dari internet.

`ReportPdf::TEMPLATE_VERSION` ikut tercetak pada catatan kaki. PDF **tidak**
disimpan sebagai berkas arsip pada MVP ini, sehingga byte identik lintas
pembaruan template bukan jaminan. Persetujuan pada aplikasi bukan tanda tangan
elektronik tersertifikasi; template hanya menyediakan ruang tanda tangan.

## Notifikasi

Kanal `database` saja — tidak ada email atau WhatsApp. Notifikasi dibuat setelah
commit dan dideduplikasi lewat unique index `(notifiable, event_id)`.

Saat ini notifikasi dikirim sinkron (`sendNow`), sehingga **worker queue belum
diperlukan**. Bila nanti dipindahkan ke queue, jalankan `php artisan queue:work`
dan pantau tabel `failed_jobs`.

## Tahap berikutnya

Sisa M5: smoke test responsif dan aksesibilitas **di browser nyata** belum
dijalankan (belum ada tooling browser di proyek). Pemeriksaan statis sudah
dilakukan: tombol ikon memiliki `aria-label`, tabel lebar dibungkus
`overflow-x-auto`, dan kontrol Formulir Model A kini menautkan pesan galat dan
petunjuk lewat `aria-describedby` serta menandai field wajib dengan
`aria-required`. Daftar periksa manual dengan data demo:

1. Lebar 360 px: login, dashboard, daftar LHP, keempat tahap formulir, detail
   laporan dengan panel pemeriksaan, halaman notifikasi — tanpa scroll
   horizontal halaman.
2. Navigasi keyboard saja: menu seluler, pindah tahap formulir, simpan draf,
   dialog konfirmasi kirim, fokus kembali setelah dialog ditutup.
3. Pembaca layar (NVDA/VoiceOver): label field, pesan galat setelah submit tidak
   valid, jumlah notifikasi belum dibaca pada lonceng.
