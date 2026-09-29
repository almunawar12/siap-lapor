# PRD — Sistem LHP

Versi: 1.0 · 29 September 2026 · Status: spesifikasi MVP untuk implementasi.

## 1. Ringkasan produk

Aplikasi internal untuk menyusun, mengirim, memeriksa, merevisi, dan mengarsipkan **Laporan Hasil Pengawasan Pemutakhiran Data Pemilih Berkelanjutan**, mengikuti **Formulir Model A** pada foto referensi pengguna. Admin Kecamatan menyusun laporan; Admin Kabupaten memeriksa dan menyetujui atau mengembalikan dengan catatan.

LHP berarti Laporan Hasil Pengawasan. Tidak ada modul perolehan suara atau pertanggungjawaban keuangan dalam MVP. Sumber kebutuhan adalah percakapan pengguna dan foto formulir; dokumen ini bukan penafsiran regulasi.

## 2. Keputusan dan asumsi

**Sudah diputuskan:** dua role saja; Laravel stabil terbaru saat bootstrap, Inertia React, shadcn/ui, PostgreSQL; formulir mengikuti Model A; revisi disertai catatan; PDF dan arsip.

**Default implementasi yang dapat diubah:** satu kabupaten per instalasi; banyak akun dapat berada di satu kecamatan dan mengelola laporan wilayah yang sama; periode pelaporan dikelola kabupaten; tidak ada pendaftaran publik; lampiran opsional; nomor surat diinput manual; laporan terlambat tetap boleh dikirim dengan penanda.

**Perlu dikonfirmasi sebelum operasional:** nama instansi (percakapan awal menyebut KPU, sedangkan foto menyebut Bawaslu), nama kabupaten, daftar kecamatan resmi, format dan lingkup keunikan nomor surat, dokumen wajib, alur tanda tangan, serta retensi arsip. Gunakan nama netral “Sistem LHP” dan konfigurasi instansi; jangan mengarang logo, nama pejabat, atau ketentuan resmi. Asumsi tersebut tidak menghalangi pembangunan MVP.

## 3. Tujuan dan indikator

- Pelapor dapat mengisi Model A sekali dan memperoleh PDF dari data yang sama.
- Setiap catatan revisi dapat ditelusuri sampai versi laporan yang diperiksa.
- Tidak ada akses lintas kecamatan bagi Admin Kecamatan.
- Tidak ada perubahan diam-diam terhadap laporan yang telah diajukan.
- Dashboard menampilkan jumlah laporan menurut status dan cakupan wilayah.
- Target penerimaan: seluruh skenario kritis pada bagian 11 lulus. Target waktu layanan ditentukan dari pengukuran uji coba, bukan angka yang diasumsikan sebagai hasil nyata.

## 4. Role dan hak akses

| Kemampuan | Admin Kecamatan | Admin Kabupaten |
|---|---|---|
| Membuat/mengedit draf | Kecamatan sendiri | Tidak |
| Mengirim dan merevisi | Kecamatan sendiri | Tidak |
| Melihat laporan, PDF, lampiran, versi | Kecamatan sendiri | Seluruh kecamatan |
| Memulai pemeriksaan | Tidak | Ya |
| Membuat catatan, mengembalikan, menyetujui | Tidak | Ya |
| Membuka kembali laporan disetujui | Tidak | Ya, alasan wajib |
| Mengelola akun kecamatan dan periode | Tidak | Ya |
| Mengelola profil dan password sendiri | Ya | Ya |

Admin Kabupaten tidak mengubah isi laporan kecamatan. Akun kabupaten awal dibuat melalui perintah bootstrap aman, bukan halaman registrasi. Akun dinonaktifkan tanpa menghilangkan atribusi pada arsip. Perubahan penugasan kecamatan tidak disediakan dalam MVP; nonaktifkan akun lama dan buat akun penugasan baru.

## 5. Formulir Model A

Draf boleh belum lengkap. Saat dikirim, semua kolom di bawah wajib kecuali keterangan opsional/lampiran. Nomor LHP harus unik per instalasi dalam MVP; aturan ini dapat disesuaikan setelah format resmi diterima.

| Bagian | Field | Aturan input |
|---|---|---|
| Identitas | Nomor LHP | Teks maksimal 150 karakter; bukan nomor otomatis rekaan |
| Data Pengawas Pemilu | Nama pelaksana tugas pengawasan | Teks maksimal 255 |
| | Jabatan | Teks maksimal 255 |
| | Nomor surat perintah tugas | Teks maksimal 150 |
| | Tanggal surat perintah tugas | Tanggal valid |
| | Alamat | Teks maksimal 2.000 |
| Kegiatan Pengawasan | Kegiatan | Teks maksimal 500 |
| | Bentuk | Teks maksimal 2.000 |
| | Tujuan | Teks maksimal 5.000 |
| | Sasaran | Teks maksimal 5.000 |
| | Waktu dan tempat | Tanggal mulai wajib, tanggal selesai opsional, jam opsional, lokasi maksimal 2.000 |
| Hasil Pengawasan | Uraian singkat hasil pengawasan | Teks biasa multiline, maksimal 20.000; pertahankan paragraf |
| Pengesahan | Tempat dan tanggal penandatanganan | Tempat maksimal 255 dan tanggal valid |
| | Nama penandatangan | Teks maksimal 255 |
| | Kedudukan penandatangan | Ketua atau Anggota Pengawas Pemilu |

Tanggal selesai tidak boleh sebelum tanggal mulai. Jangan menerapkan aturan tanggal lain yang belum disepakati. Nama pelaksana dan penandatangan disimpan sebagai snapshot, tidak mengikuti perubahan profil akun.

Metadata aplikasi: kecamatan otomatis dari akun, periode, pembuat, waktu pembuatan/pengiriman, versi, pemeriksa, waktu keputusan. Kecamatan tidak dikirim sebagai sumber otoritatif dari frontend.

Lampiran pendukung opsional: PDF/JPEG/PNG, maksimal 10 MB per file dan 10 file aktif per versi; batas dapat dikonfigurasi. Simpan nama, kategori, dan deskripsi. Jangan menganggap surat tugas atau dokumentasi wajib hanya karena tersedia fitur unggah.

## 6. Alur dan status

| Status awal | Aksi | Pelaku | Status akhir |
|---|---|---|---|
| — | Buat laporan dan versi 1 | Kecamatan | draft |
| draft | Simpan | Kecamatan | draft |
| draft | Kirim setelah validasi | Kecamatan | submitted |
| submitted | Mulai pemeriksaan | Kabupaten | under_review |
| under_review | Kembalikan dengan catatan | Kabupaten | revision_required |
| revision_required | Simpan perbaikan | Kecamatan | revision_required |
| revision_required | Kirim ulang | Kecamatan | submitted |
| under_review | Setujui versi yang diperiksa | Kabupaten | approved |
| approved | Buka kembali, alasan wajib | Kabupaten | revision_required |

Pengembalian atau pembukaan kembali membuat satu salinan versi kerja baru; versi yang sudah dikirim tetap immutable. Pengiriman ulang tidak membuat salinan tambahan. Status “Diajukan ulang” menjadi label aktivitas, bukan status database tersendiri. Laporan approved yang dibuka kembali keluar dari total approved sampai versi baru disetujui; persetujuan lama tetap terlihat pada riwayat versi.

Pemeriksaan memiliki satu pemilik aktif. Kabupaten lain dapat melihat, tetapi tidak memutuskan tanpa mengambil alih dengan alasan tercatat. Pengambilan alih dan keputusan memerlukan pemeriksaan konflik untuk menghindari dua keputusan bersamaan.

Tidak ada hapus laporan dalam MVP. Perubahan administratif dilakukan secara terlacak. Periode tidak aktif mencegah pembuatan laporan baru; laporan yang sudah ada tetap dapat direvisi. Penanda terlambat tidak menggantikan status alur.

## 7. Catatan revisi

- Catatan dapat berupa catatan umum, terkait field tertentu, atau lampiran tertentu.
- Pengembalian wajib memiliki setidaknya satu catatan spesifik yang belum selesai.
- Kecamatan membaca catatan bersama nilai/dokumen pada versi yang diperiksa.
- Kecamatan menulis tanggapan untuk setiap catatan terbuka sebelum mengirim ulang; tanggapan tidak otomatis menyelesaikan catatan.
- Kabupaten menandai selesai atau membuka kembali catatan, lengkap dengan pelaku dan waktu.
- Persetujuan ditolak jika ada catatan terbuka dari siklus pemeriksaan sebelumnya maupun siklus aktif.
- Keputusan, tanggapan, dan pergantian lampiran tercatat. Jangan menimpa catatan historis.

Contoh: field `activity_location`, catatan “Mohon lengkapi lokasi pelaksanaan”, tanggapan “Lokasi sudah dilengkapi menjadi Kantor Kecamatan X.”

## 8. Halaman dan pengalaman pengguna

1. Login dan profil: bahasa Indonesia, pesan kesalahan jelas, tanpa registrasi publik.
2. Dashboard: kartu status, laporan terbaru, revisi menunggu, filter periode; kabupaten mempunyai filter kecamatan.
3. Daftar LHP: pencarian nomor/nama kegiatan, filter status/periode, pagination server, waktu pembaruan.
4. Form bertahap: Data Pengawas → Kegiatan → Hasil → Pengesahan & Lampiran. Tombol Simpan Draf, Pratinjau, Kirim. Validasi submit lengkap dan konfirmasi sebelum kirim.
5. Detail: status, versi aktif, isian, lampiran, timeline, dan catatan.
6. Pemeriksaan: desktop dua panel (laporan dan catatan); pada ponsel disusun vertikal. Aksi Setujui/Kembalikan hanya sesuai status dan izin.
7. Riwayat versi: pilih versi, bandingkan nilai sebelum/sesudah dan daftar lampiran; tampilkan label keputusan setiap versi.
8. Arsip: filter dan unduh PDF per laporan/versi. Ekspor massal dan Excel bukan MVP.
9. Pengelolaan akun, kecamatan, periode, dan identitas instansi oleh kabupaten.

Gunakan shadcn/ui untuk sidebar, card, table, input, textarea, select, badge, dialog, alert, tabs, dan pagination. Label status selalu berupa teks selain warna. Semua input memiliki label dan pesan kesalahan; navigasi keyboard dan tampilan 360 px harus berfungsi. Tidak perlu rich-text editor dalam MVP.

Dashboard “belum melapor” berarti kecamatan aktif tanpa pengiriman pada periode terpilih, bukan kewajiban satu laporan per kecamatan. Satu kecamatan dapat membuat banyak LHP dalam satu periode. Tampilkan definisi ini pada UI.

## 9. PDF dan pengesahan

- Cetak judul Formulir Model A, nomor, bagian I–III, tempat/tanggal, nama dan kedudukan penandatangan.
- Gunakan data versi tertentu; jangan mengambil isi draf terkini ketika mengunduh versi historis.
- Belum approved: penanda “BELUM TERVERIFIKASI”; versi lama yang pernah approved: “VERSI HISTORIS” ketika bukan persetujuan aktif.
- Persetujuan aplikasi tidak dianggap tanda tangan elektronik. Sediakan ruang tanda tangan; unggahan laporan bertanda tangan dapat menjadi lampiran opsional.
- Jangan memasukkan blok pengesahan peraturan oleh Ketua Bawaslu RI dari bawah foto ke setiap LHP tanpa konfirmasi.
- A4 potret menjadi default teknis yang dapat disesuaikan; foto bukan bukti ukuran/margin resmi. Uraian panjang boleh multipage; isi tidak dipotong. QA wajib mencakup 1 halaman dan beberapa halaman.

## 10. Batas lingkup

Termasuk: autentikasi, dua role, form, lampiran privat, versi, verifikasi, revisi, dashboard, notifikasi dalam aplikasi, PDF, audit, master data.

Tidak termasuk: perhitungan suara, keuangan, role ketiga, OCR/AI, tanda tangan elektronik tersertifikasi, WhatsApp/email otomatis, aplikasi native, akses publik, integrasi instansi, dan multi-kabupaten. Fitur tambahan memerlukan perubahan PRD.

## 11. Acceptance criteria

- AC01: akun kecamatan A tidak dapat membaca/mengubah laporan, versi, lampiran, PDF atau notifikasi kecamatan B, termasuk melalui URL langsung.
- AC02: draf parsial tersimpan; pengiriman dengan field wajib kosong ditolak dengan pesan per field.
- AC03: form menghasilkan PDF dengan semua isian Model A, Unicode dan paragraf utuh.
- AC04: setelah submit, edit/upload/hapus lampiran versi tersebut ditolak.
- AC05: pengembalian tanpa catatan ditolak; pengembalian valid menghasilkan tepat satu versi kerja baru.
- AC06: pengiriman ulang mempertahankan versi lama, tanggapan, dan lampiran lama.
- AC07: hanya pemilik pemeriksaan aktif dapat memutuskan; dua permintaan keputusan bersamaan menghasilkan satu keputusan sah.
- AC08: persetujuan dengan catatan terbuka ditolak; persetujuan valid menunjuk versi tepat yang diperiksa.
- AC09: pembukaan kembali memerlukan alasan dan menghasilkan revisi baru; riwayat persetujuan lama tidak hilang.
- AC10: role/district/status/approved_by dari payload tidak dapat meningkatkan hak pengguna.
- AC11: file tipe/ukuran tidak sesuai ditolak; file tidak bisa diakses tanpa otorisasi.
- AC12: dashboard dan filter menghormati cakupan dan status, termasuk laporan yang dibuka kembali.
- AC13: akun nonaktif tidak dapat memakai sesi lama; akun dengan aktivitas historis tetap tercatat.
- AC14: konflik penyimpanan draf menampilkan pesan dan tidak menimpa perubahan orang lain.
- AC15: nomor duplikat ditolak tanpa kehilangan data form; klik Kirim berulang tidak menggandakan versi atau notifikasi.
- AC16: PDF panjang tidak memotong uraian atau menumpuk area tanda tangan; layout ponsel dapat digunakan.

## 12. Tahapan rilis

M1 fondasi dan otorisasi → M2 form/versi/lampiran → M3 pengiriman/review/revisi → M4 PDF/dashboard/notifikasi → M5 pengujian dan dokumentasi operasional. Detail implementasi terdapat di `architecture.md`; aturan kerja Claude Code di `agent.md`.
