# Architecture — Sistem LHP

Versi 1.0 · 29 September 2026. Kontrak kebutuhan: `prd.md`. Ini rancangan yang harus diimplementasikan, bukan klaim sistem sudah tersedia.

## 1. Stack dan strategi versi

Gunakan modular monolith: Laravel + Inertia + React/TypeScript dalam satu repositori, PostgreSQL, shadcn/ui + Tailwind, Vite. Baseline dokumentasi saat penyusunan: Laravel 13 dan React starter kit resmi. Saat bootstrap, verifikasi rilis stabil dan kompatibilitas PHP/Node/PostgreSQL/dependensi melalui dokumentasi resmi dan resolver. Jangan memakai prerelease atau menaikkan major pada proyek yang sudah mempunyai lockfile tanpa kebutuhan.

Commit `composer.lock` dan satu lockfile package manager. Catat versi runtime dan dependency hasil instalasi dalam README. Pilih npm sebagai default kecuali repositori sudah menggunakan manager lain. Tidak perlu REST API terpisah, JWT, microservices, atau SSR untuk MVP internal ini. Gunakan session authentication dan CSRF dari stack Laravel.

Rujukan resmi: https://laravel.com/docs/13.x ; https://laravel.com/starter-kits ; https://inertiajs.com ; https://ui.shadcn.com/docs/installation/laravel ; https://www.postgresql.org/docs/ . Verifikasi API terhadap versi yang benar-benar terpasang.

## 2. Pembagian tanggung jawab

- Route web + middleware: sesi, akun aktif, role kasar.
- Policy: role, kecamatan, status, versi, dan kepemilikan review; berlaku untuk setiap objek turunannya.
- Form Request: validasi payload dan whitelist field; request draf berbeda dari submit.
- Controller: tipis, otorisasi, panggil action, kembalikan Inertia/redirect/download.
- Action/domain service: transaksi, aturan transisi, snapshot versi, audit, notifikasi.
- Eloquent: relasi, casts, query scope; jangan menyembunyikan transisi kritis dalam observer.
- React: presentasi dan interaksi. Server tetap sumber kebenaran status/hak akses.
- Blade khusus PDF: template terpisah dari React, berbasis data snapshot versi.

Direktori yang disarankan: `app/Actions/Reports`, `app/Enums`, `app/Http/Controllers`, `app/Http/Requests`, `app/Models`, `app/Policies`, `app/Queries`, `app/Services/Pdf`; frontend di `resources/js/pages`, `components`, `layouts`, `types`; PDF di `resources/views/pdf`; pengujian di `tests/Feature` dan `tests/Unit`. Ikuti struktur starter kit aktual bila penamaannya berbeda.

## 3. Model data

Gunakan bigint ID internal, timestamp UTC dengan `timestamptz`, tanggal sipil sebagai `date`; UI/PDF memakai Asia/Jakarta. Status/role berupa varchar + CHECK dan PHP backed enum. FK historis memakai RESTRICT; nonaktifkan pengguna/wilayah, jangan cascade-delete arsip. Semua tabel bisnis memiliki created_at/updated_at kecuali log append-only yang cukup created_at.

| Tabel | Kolom pokok dan constraint |
|---|---|
| districts | id, code UNIQUE, name, is_active |
| users | id, name, email UNIQUE (normalisasi lower-case), password, role, district_id nullable FK, is_active, must_change_password; CHECK role kecamatan memerlukan district_id dan role kabupaten harus null |
| reporting_periods | id, name, starts_on, ends_on, submission_deadline nullable, is_active; ends_on >= starts_on |
| application_settings | id singleton, institution_name, regency_name; jangan menaruh secret di sini |
| reports | id, district_id FK, reporting_period_id FK, created_by FK, report_number nullable UNIQUE, status, current_version_id nullable FK, approved_version_id nullable FK, lock_version integer default 0, first_submitted_at nullable |
| report_versions | id, report_id FK, version_number, payload JSONB, schema_version default 1, submitted_at nullable, created_by FK; UNIQUE(report_id, version_number) |
| files | id, disk, path UNIQUE, original_name, mime_type, size_bytes, sha256, uploaded_by FK |
| version_attachments | id, report_version_id FK, file_id FK, category, description nullable, replaces_attachment_id nullable FK; UNIQUE(report_version_id,file_id) |
| reviews | id, report_version_id FK, reviewer_id FK, status(active/changes_requested/approved), general_note nullable, started_at, decided_at nullable |
| revision_notes | id, review_id FK, field_key nullable, attachment_id nullable FK, body, status(open/resolved), resolved_by nullable FK, resolved_at nullable; umum jika kedua target null; keduanya tidak boleh terisi bersamaan |
| revision_responses | id, revision_note_id FK, report_version_id FK, author_id FK, body, created_at; append-only |
| activity_logs | id, report_id nullable FK, actor_id nullable FK, event, from_status nullable, to_status nullable, report_version_id nullable FK, metadata JSONB, created_at |
| notifications | tabel notifikasi database Laravel, dengan identitas penerima dan report_id/jenis peristiwa |

Tambahkan tabel infrastruktur sessions/password reset/jobs sesuai starter kit dan driver yang digunakan.

`report_versions.payload` adalah objek typed dan tervalidasi, bukan JSON bebas. Keys: `report_number`, `district_name`, `institution_name`, `regency_name`, `supervisor_name`, `supervisor_position`, `assignment_number`, `assignment_date`, `supervisor_address`, `activity_name`, `activity_form`, `activity_purpose`, `activity_target`, `activity_start_date`, `activity_end_date`, `activity_start_time`, `activity_end_time`, `activity_location`, `findings`, `signing_place`, `signing_date`, `signer_name`, `signer_capacity`.

Nama wilayah/instansi dan nomor di payload merupakan snapshot untuk PDF historis. Saat submit, nama wilayah/instansi diambil dari konfigurasi server lalu dibekukan. Report number aktif disinkronkan ke `reports.report_number` untuk unique/search; nomor versi sebelumnya tetap ada di payload. String kosong dinormalisasi menjadi null; nomor yang digunakan laporan lain tidak dapat dipakai. Algoritme nomor otomatis di luar MVP.

Index: reports(district_id, reporting_period_id, status), reports(status, updated_at), report_versions(report_id, version_number), revision_notes(review_id,status), activity_logs(report_id,created_at), review(version,status). Buat partial UNIQUE index reviews(report_version_id) WHERE status='active'. Optimisasi pencarian JSON activity_name hanya jika EXPLAIN/pengukuran membuktikan kebutuhan.

FK current/approved version ditambahkan setelah kedua tabel tersedia. Pastikan versi berasal dari laporan yang sama melalui composite FK `(id,current_version_id)` ke `(report_id,id)` dan pasangan approved; tambahkan UNIQUE(report_id,id). Draf version dibuat lalu current pointer diisi dalam satu transaksi. Status approved wajib memiliki approved_version_id = current_version_id; status lain approved_version_id null.

## 4. Lifecycle versi dan transaksi

**Create:** server menetapkan district dari akun; buat reports(draft), versi 1 yang editable dan current pointer dalam satu transaksi.

**Save draft/revision:** lock baris report; validasi expected `lock_version`; hanya status draft/revision_required dan current version dengan submitted_at null. Update payload/attachment serta increment lock_version. Konflik menghasilkan 409, UI menawarkan reload dan menjaga isian lokal. Jangan last-write-wins.

**Submit:** `DB::transaction` dan `lockForUpdate` report; cocokkan status, version_id, lock_version, izin. Validasi payload lengkap, nomor unik, batas lampiran, serta tanggapan setiap catatan terbuka. Bekukan payload/attachment dengan submitted_at, set status submitted, set first_submitted_at bila kosong, increment lock_version, audit. Notifikasi after commit. Submit ulang versi yang sama tidak menciptakan event kedua; kembalikan hasil yang sudah ada atau konflik terkontrol.

**StartReview:** lock report; dari submitted buat satu active review current version, status under_review. Reviewer menjadi pemilik. Pengambilalihan review memerlukan alasan, audit reviewer lama/baru, dan optimistic lock.

**ReturnForRevision:** lock report; pastikan review active milik aktor dan current version sesuai. Minimal satu catatan terbuka (catatan baru pada review aktif atau catatan historis yang belum selesai). Tutup review changes_requested; salin payload ke versi N+1 dengan submitted_at null; salin link lampiran yang dipertahankan; current pointer ke N+1, status revision_required. Audit + notifikasi. Jangan mengubah versi N.

**Resubmit:** jalankan Submit pada working version N+1. Catatan lama tetap terhubung ke versi sumber melalui review; respons baru terhubung ke working/submitted version yang menanggapinya. Pengiriman ulang tidak membuat N+2.

**ResolveNote:** hanya reviewer aktif laporan, saat under_review, boleh resolve/reopen catatan dari laporan yang sama, termasuk siklus lama. Catat event; body catatan historis tidak diubah. Kecamatan tidak dapat resolve sendiri.

**Approve:** lock report; reviewer aktif harus pemilik; version yang diperiksa harus current dan submitted; tidak ada catatan open dari seluruh siklus laporan. Review menjadi approved, reports menjadi approved, approved_version_id=current_version_id. Audit + notifikasi dalam satu keputusan. Keputusan berbasis data lama ditolak 409.

**ReopenApproved:** kabupaten memberi alasan wajib; buat audit dan catatan umum revisi pada review persetujuan yang lama, tanpa mengubah keputusan approved historis. Buat versi N+1 editable dengan salinan link lampiran; current pointer diperbarui, approved_version_id dikosongkan, status revision_required. Versi lama tetap mempunyai review approved sehingga dapat ditampilkan sebagai historis. Jalankan siklus submit/review normal sesudahnya.

Semua mutasi child (catatan, respons, lampiran) juga lock report dan increment lock_version. Payload tidak boleh menentukan role, district, reviewer, status, atau submitted_at. Ketika dua request bersaing, hanya satu yang cocok dengan lock_version. Gunakan constraint DB sebagai pengaman akhir; tangani violation menjadi pesan domain.

## 5. File dan batas transaksi

File disimpan privat di luar webroot (local pada development; disk privat kompatibel S3 dapat dipakai di deployment). Route stream/download selalu melalui policy report dan version. Jangan `storage:link` untuk lampiran. UUID/random path; MIME/content diperiksa, bukan ekstensi saja; larang SVG/HTML/executable. PDF sebaiknya disajikan dengan header aman atau sebagai download. Jangan menampilkan HTML lampiran sebagai markup aplikasi.

Byte upload tidak atomik dengan transaksi PostgreSQL: unggah ke lokasi privat sementara, validasi, lalu dalam transaksi cek izin/status/lock dan daftarkan file + link. Pada rollback, hapus file baru yang belum direferensikan. Job cleanup berjangka hanya membersihkan orphan/staging yang tidak direferensikan dan melewati grace period; jangan menghapus file historis.

Saat revisi, link ke file immutable dapat disalin tanpa menyalin byte. Penggantian membuat file baru dan melepas link hanya di working version. Old link/byte pada submitted version tetap ada. Unduhan filename disanitasi dan `nosniff` digunakan.

## 6. Otorisasi dan akun

Role enum hanya `admin_kecamatan`, `admin_kabupaten`. Implementasikan ReportPolicy, VersionPolicy, AttachmentPolicy dan pemeriksaan review/notes. Gunakan query ter-scope untuk list, count, search, notifikasi, ekspor. Route binding nested harus memastikan relasi induk-anak; ID sulit ditebak tidak menggantikan otorisasi.

Nonaktifkan registrasi publik. Bootstrap kabupaten melalui artisan command interaktif dengan password tersembunyi, bukan password di argumen command atau seed produksi. Admin kabupaten membuat akun kecamatan dengan credential sementara yang harus diganti; jangan mengirim pesan otomatis. Password dihash; middleware memblokir akun nonaktif setiap request dan mengakhiri sesi. Cookie secure di HTTPS, CSRF, rate limit login, APP_DEBUG=false di produksi.

## 7. Route kontrak

| Method/path | Tujuan |
|---|---|
| GET /dashboard | Dashboard scoped |
| GET /reports | List/filter/pagination |
| GET /reports/create; POST /reports | Form baru dan create |
| GET /reports/{report}; GET /reports/{report}/edit | Detail dan form edit |
| PATCH /reports/{report} | Simpan editable current version |
| POST /reports/{report}/submit | Submit/resubmit |
| POST /reports/{report}/review/start | Mulai pemeriksaan |
| POST /reports/{report}/review/takeover | Alih pemeriksa, alasan wajib |
| POST /reports/{report}/review/return | Kembalikan untuk revisi |
| POST /reports/{report}/review/approve | Setujui |
| POST /reports/{report}/reopen | Buka kembali approved |
| POST /reports/{report}/notes | Tambah catatan pada review aktif |
| POST /reports/{report}/notes/{note}/responses | Tambah tanggapan |
| POST /reports/{report}/notes/{note}/resolve atau /reopen | Keputusan catatan |
| POST /reports/{report}/attachments | Upload working version |
| DELETE /reports/{report}/attachments/{attachment} | Lepas link working version |
| GET /reports/{report}/versions/{version} | Detail historis/compare |
| GET /reports/{report}/versions/{version}/pdf | PDF versi tertentu |
| GET /reports/{report}/attachments/{attachment}/download | File berotorisasi |
| GET /notifications; PATCH /notifications/{notification}/read | Notifikasi milik pengguna |
| /admin/users, /admin/districts, /admin/periods, /admin/settings | CRUD terbatas sesuai PRD |

Tidak ada generic endpoint update status. Request mutasi report membawa current_version_id dan lock_version. HTTP 403/404 untuk izin/objek, validasi melalui error bag Inertia, 409 untuk konflik. Sesuaikan cara error Inertia dengan versi terpasang; jangan biarkan 409 muncul sebagai halaman error mentah.

## 8. UI dan data delivery

Gunakan typed props minimal: DTO laporan, versi, capabilities server, pagination, filters, error bag. Jangan mengirim model user lengkap atau path privat. Pagination server default 20; eager load relasi untuk mencegah N+1. Form menggunakan mekanisme form Inertia; multipart upload dengan progress. Tombol pending dinonaktifkan, tetapi server tetap harus menangani request ganda.

Form draf disimpan eksplisit; peringatkan saat meninggalkan perubahan belum disimpan. Jangan menyimpan data laporan sensitif di localStorage. Flash toast untuk keberhasilan, pesan dekat input untuk kesalahan. Sidebar/menu menyesuaikan role; penyembunyian menu bukan kontrol keamanan. Diff versi membandingkan typed payload dan metadata lampiran, dengan output escaped.

## 9. PDF, notifikasi, dan audit

Default renderer: package PDF Laravel berbasis Dompdf apabila kompatibel dengan Laravel/PHP yang dipasang; buktikan melalui Composer. Jika tidak kompatibel, dokumentasikan alternatif sebelum mengganti. Template Blade sederhana dengan font Unicode dan CSS print, remote asset fetching dinonaktifkan. Jangan merender findings sebagai HTML mentah. PDF selalu menyertakan penanda status versi, sementara isi dan snapshot penandatangan tetap dari versi itu.

MVP dapat menghasilkan PDF on-demand. Pengujian render wajib mencakup multiline, nama panjang, dan >1 halaman. Jangan mengklaim tanda tangan digital. Simpan template_version pada metadata PDF jika nanti PDF disimpan untuk arsip byte-identik; byte-identik lintas pembaruan template bukan janji MVP.

Notifikasi database untuk submitted/resubmitted ke kabupaten, returned/approved/reopened ke akun aktif kecamatan terkait. Buat after commit dan deduplikasi dengan event_id + recipient. Isi singkat tanpa menyalin uraian sensitif. Tidak ada pengiriman email/WhatsApp. Jika memakai queue, sediakan worker dan pantau failed_jobs.

Activity log append-only lewat aplikasi: event, actor, report/version, transisi, alasan, waktu. Jangan log password, token, file byte, atau seluruh uraian secara berulang. Audit aplikasi ini bukan jaminan kebal perubahan oleh administrator database.

## 10. Testing dan deployment

Gunakan Pest atau PHPUnit mengikuti starter kit; PostgreSQL sungguhan untuk integration tests, bukan substitusi SQLite untuk constraint JSONB/partial index/concurrency. Pisahkan database test dan cegah command destruktif mengenai database produksi.

Suite prioritas: policy lintas wilayah untuk semua resource; submit/return/resubmit/approve/reopen; constraint parent-version; duplikasi nomor; dua keputusan bersamaan; optimistic locking draf; preservation file/versi; status akun; notifikasi setelah commit; output PDF. UI smoke test login dua role, form, catatan, upload, dan tampilan ponsel. Satu E2E utama: kecamatan submit → kabupaten return → kecamatan revisi → kabupaten approve → unduh PDF.

CI: dependency install dari lockfile, migration database test, test backend, typecheck TypeScript, lint, production frontend build. Gunakan nama script yang benar-benar tersedia pada package.json; dokumentasikan tambahan script.

Deployment: web server menuju public/, PHP runtime kompatibel, PostgreSQL, private storage persisten, worker/scheduler jika dipakai. Secret lewat environment; commit .env.example berisi placeholder. Backup database dan file sebagai satu set, uji restore. Migration produksi non-destruktif dan backup sebelum perubahan berisiko. Jangan menjalankan migrate:fresh pada data pengguna. Ukur list/dashboard di staging dengan dataset representatif sebelum menetapkan target performa.

## 11. Keputusan arsitektur

ADR-01 monolith Inertia untuk dua role dalam satu aplikasi. ADR-02 JSONB payload tervalidasi per versi agar snapshot surat utuh. ADR-03 report status + immutable submitted versions untuk audit. ADR-04 role enum/policy tanpa paket role dinamis karena hanya dua role. ADR-05 file privat immutable dan version links untuk retensi bukti. ADR-06 instansi configurable karena KPU/Bawaslu belum dikonfirmasi. Perubahan terhadap keputusan ini harus dicatat bersama dampaknya pada PRD dan test.
