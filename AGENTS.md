# Panduan Agent — Implementasi Sistem LHP dengan Claude Code

## Aktivasi di Claude Code

Taruh `prd.md`, `architecture.md`, dan `agent.md` pada root proyek. Buat `CLAUDE.md` di root dengan satu baris berikut (tanpa pagar code dalam file sebenarnya):

```text
@agent.md
```

Claude Code mendukung import file melalui CLAUDE.md. Nama `agent.md` adalah nama yang diminta pemilik proyek; jangan mengandalkan nama ini saja untuk pemuatan otomatis. Jika CLAUDE.md sudah ada, tambahkan import tanpa menimpa instruksi yang masih relevan. Referensi: https://code.claude.com/docs/en/memory .

## Misi

Bangun aplikasi LHP — Laporan Hasil Pengawasan Pemutakhiran Data Pemilih Berkelanjutan, Formulir Model A. Baca `prd.md` dan `architecture.md` sebelum implementasi. Jangan berhenti pada UI mockup: hasil harus tersambung ke Laravel, PostgreSQL, penyimpanan privat, dan alur review yang bekerja.

Instruksi eksplisit pengguna terbaru menentukan kebutuhan; PRD menentukan perilaku produk; architecture menentukan implementasi; dokumen ini menentukan cara kerja. Jika ada pertentangan, jelaskan dan perbarui dokumen terkait secara konsisten. Jangan mengklaim persyaratan resmi yang tidak diberikan.

## Batas yang wajib dijaga

- Tepat dua role: admin_kecamatan dan admin_kabupaten.
- Admin Kecamatan menulis laporan wilayahnya. Admin Kabupaten memeriksa, memberi catatan, dan menyetujui; tidak mengedit substansi laporan.
- Form mengikuti seluruh field Model A pada PRD, bukan template keuangan atau hasil suara.
- Default nama aplikasi Sistem LHP; nama instansi configurable karena KPU/Bawaslu belum dikonfirmasi.
- Laravel stabil terbaru untuk proyek baru, React/TypeScript melalui Inertia, shadcn/ui, PostgreSQL. Pertahankan versi proyek existing kecuali upgrade diminta.
- submitted version immutable; edit revisi memakai working version baru sesuai lifecycle architecture.
- Tidak ada status bebas dari client, role tambahan, registrasi publik, public attachment URL, tanda tangan digital rekaan, atau notifikasi eksternal.
- Kewenangan wajib divalidasi server pada laporan dan semua resource turunannya.

## Mulai bekerja

1. Inspeksi repository, instruksi proyek, git status, composer.json, package.json, lockfile, dan versi runtime. Jangan menimpa perubahan pengguna.
2. Jika folder hanya berisi dokumen, bootstrap Laravel React starter kit dengan aman; gunakan direktori staging bila installer memerlukan direktori kosong, lalu integrasikan tanpa menghapus dokumen.
3. Verifikasi kompatibilitas versi dari dokumentasi resmi dan dependency resolver. Catat versi terpasang di README. Jangan menebak flag installer atau API versi baru.
4. Gunakan PostgreSQL development/test terpisah. Bila koneksi belum tersedia, siapkan contoh konfigurasi dan lanjutkan bagian yang dapat dibuat; laporkan test yang belum dapat dijalankan secara jujur.
5. Buat rencana milestone singkat. Pilihan rutin diselesaikan sendiri sesuai dokumen; pertanyaan hanya untuk keputusan yang mengubah scope/otoritas atau tindakan destruktif.

## Tahapan implementasi

- M1: starter kit, nonaktifkan registrasi, role enum, districts, akun aktif, bootstrap kabupaten, policy, sidebar sesuai role. Gate: test login dan isolasi wilayah.
- M2: periods, reports, report_versions, typed payload, draf bertahap, private files, preview. Gate: draf parsial dan validasi submit; attachment scope.
- M3: transaction actions submit/start/return/resubmit/resolve/approve/reopen; review ownership/takeover; respons dan diff versi; audit. Gate: lifecycle penuh, konflik request, immutable history.
- M4: PDF Model A, dashboard scoped, arsip/filter/pagination, notifikasi database, akun dan pengaturan. Gate: PDF panjang dan konsistensi count/status.
- M5: uji E2E, responsive/accessibility smoke, typecheck/lint/build, README setup/backup/deployment, data demo terpisah. Gate: acceptance criteria PRD dan catatan keterbatasan.

Setiap milestone harus menghasilkan fungsi end-to-end yang bisa diuji. Jangan mengisi dashboard dengan angka statis lalu menyatakan fitur selesai. Jangan memperluas scope ke OCR, WhatsApp, atau tanda tangan tersertifikasi.

## Konvensi kode

- Bahasa UI dan pesan validasi: Indonesia. Nama kelas/field/kode: Inggris yang konsisten dengan architecture.
- Controller tipis; Form Request untuk input; Policy untuk akses; Action untuk transaksi alur; enum untuk role/status.
- Hindari generic repository/service abstrak yang tidak memberi manfaat. Gunakan Eloquent dan query scopes secara jelas.
- Jangan mass assign request->all(); whitelist field. Identitas wilayah, actor, status, dan reviewer dari server.
- JSONB payload memiliki schema dan DTO/type TS; jangan menggunakan any untuk menyembunyikan ketidaksesuaian.
- Gunakan komponen shadcn yang sudah ada sebelum menambah komponen serupa. Ikuti formatter starter kit.
- Semua teks pengguna escaped; tidak ada dangerouslySetInnerHTML atau raw Blade output untuk findings.
- Tanggal disimpan sesuai architecture; jangan mengubah tanggal surat akibat konversi timezone timestamp.
- File privat, tidak menaruh bukti di public/. Pastikan authorization untuk preview/download/PDF.
- Semua perubahan status dan child data memakai lock/transaction/expected version; tangani stale edit secara ramah.
- Catatan historis dan versi yang sudah dikirim tidak dihapus untuk mempermudah implementasi.

## Pengujian wajib berbasis risiko

Implementasikan acceptance criteria PRD sebagai pengujian yang relevan, terutama:

1. Isolasi kecamatan untuk list/detail/update/version/attachment/PDF/notification, termasuk URL langsung.
2. Submit valid/invalid; draf terkunci sesudah kirim.
3. Return tanpa catatan ditolak; satu return membuat tepat satu working version.
4. Resubmit mempertahankan old payload/file dan tidak menciptakan versi ekstra.
5. Approval menolak note terbuka dan stale/current-version mismatch.
6. Double submit dan keputusan serentak tidak menggandakan transisi/riwayat/notifikasi.
7. Reopen approved memerlukan alasan, menjaga keputusan lama, dan keluar dari approved count.
8. Lock conflict, nomor unik, inactive account, MIME/size file, rollback cleanup.
9. PDF multiline/multipage dan label status versi benar.

Gunakan PostgreSQL untuk pengujian perilaku database. Jangan melemahkan policy/test agar suite hijau. Jika environment tidak mendukung pengujian tertentu, sebutkan apa yang belum diuji dan cara menjalankannya.

## Perintah dan dokumentasi

Setelah dependency terpasang, jalankan perintah yang tersedia di repository. Backend umumnya `php artisan test`; frontend `npm run build`; typecheck/lint gunakan script package.json aktual atau tambahkan script yang terdokumentasi. Jalankan formatter PHP dari dependency proyek jika tersedia. Jangan mengklaim perintah berhasil jika tidak dijalankan.

README harus menjelaskan: versi runtime, setup .env, PostgreSQL, migration, bootstrap admin, dev server, worker/scheduler bila digunakan, test, build, private storage, backup/restore, dan konfigurasi instansi. .env.example tidak boleh berisi credential nyata. Demo seed menggunakan nama fiktif dan hanya local/test, tanpa password produksi bawaan.

## Keamanan kerja dan penyelesaian

- Jangan reset git, menghapus data, menjalankan migrate:fresh pada database pengguna, atau deploy produksi tanpa otorisasi yang sesuai.
- Jangan mencetak secret di log/output, commit .env, atau menyisipkan credential ke shell argument.
- Pertahankan data historis ketika membuat migration; dokumentasikan perubahan yang memerlukan backfill.
- Laporkan progres berdasarkan fitur yang sudah bekerja, test yang dilakukan, dan masalah konkret.
- Pada akhir pekerjaan: sebutkan file/modul utama berubah, cara menjalankan, hasil test, dan hal yang masih memerlukan konfigurasi instansi. Jangan menyatakan production-ready hanya karena build berhasil.

## Prompt awal yang dapat digunakan pemilik proyek

“Baca agent.md, prd.md, dan architecture.md. Inspeksi kondisi repository, lalu implementasikan MVP Sistem LHP secara bertahap mulai dari fondasi Laravel React starter kit, PostgreSQL, dan dua role. Ikuti lifecycle versi dan otorisasi yang ditentukan. Jalankan pengujian setiap milestone dan lanjutkan sampai alur pelaporan, revisi, persetujuan, dan PDF bekerja. Jangan menambahkan fitur di luar scope atau mengubah data existing tanpa alasan.”
