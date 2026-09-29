# SIAP LAPOR — Instruksi Proyek

Sistem Informasi Administrasi Pengawasan dan Pelaporan.
Tagline: **Pelaporan tertata, pengawasan terlacak.**

Nama "Sistem LHP" pada dokumen lama kini menjadi **SIAP LAPOR**. Judul surat pada
hasil cetak tetap mengikuti **Formulir Model A**.

Dokumen acuan, baca sebelum mengubah kode:

@AGENTS.md

Kontrak kebutuhan ada di `PRD.md`, rancangan implementasi di `ARCHITECTURE.md`.

## Status

M1 (fondasi/otorisasi), M2 (formulir Model A, versi laporan, lampiran privat),
M3 (pemeriksaan, catatan revisi, persetujuan, buka kembali), dan M4 (cetak PDF
Model A, notifikasi database) selesai. Berikutnya M5: smoke test responsif dan
aksesibilitas, konkurensi paralel, data demo, dokumentasi operasional.

## Catatan cepat

- Nama instansi (KPU/Bawaslu) belum dikonfirmasi: gunakan `config/instansi.php`.
- Kontrol akses wajib di backend (policy + form request), bukan penyembunyian tombol.
- Bahasa UI dan pesan validasi: Indonesia. Nama kode: Inggris.
- Jangan menjalankan `migrate:fresh` pada database pengguna.
- Payload versi: ubah `app/Support/ReportPayload.php` dan `resources/js/types/report.ts`
  bersama-sama; jangan menambah key JSONB tanpa keduanya.
- Setiap mutasi laporan wajib membawa `current_version_id` + `lock_version`, dikunci
  dengan `lockForUpdate` di dalam action, dan menaikkan `lock_version`.
- Versi yang sudah `submitted_at` bersifat immutable, termasuk lampirannya.
- Batas lampiran dan pagination ada di `config/siaplapor.php`.
- Policy menjawab "peran ini berhak sama sekali?"; status, kepemilikan pemeriksaan, dan
  konkurensi diperiksa di dalam action agar klik ganda menghasilkan hasil idempoten
  atau konflik terkontrol, bukan 403 yang menyesatkan.
- Catatan revisi dan tanggapan tidak pernah diubah atau dihapus. Tanggapan tidak
  menyelesaikan catatan; hanya Admin Kabupaten yang menandai selesai.
- Persetujuan wajib menolak bila ada catatan terbuka dari siklus mana pun
  (`Report::notesQuery()`), bukan hanya siklus aktif.
- PDF selalu dari `$version->normalizedPayload()`, jangan dari draf terkini. Naikkan
  `ReportPdf::TEMPLATE_VERSION` bila tata letak berubah.
- Isi surat diuji pada tingkat HTML template (`ReportPdf::viewData()`), bukan dengan
  mengurai biner PDF: Dompdf mensubset font sehingga teks tidak terbaca mentah.
- Notifikasi selalu `DB::afterCommit` dan membawa `event_id` deterministik untuk
  deduplikasi; jangan mengirim notifikasi di dalam transaksi.
