{{--
    Formulir Model A — Laporan Hasil Pengawasan.

    Template ini terpisah dari React dan memakai snapshot payload versi. Seluruh
    nilai dicetak dengan {{ }} sehingga di-escape; uraian tidak pernah dirender
    sebagai HTML. Uraian panjang boleh melampaui satu halaman: tidak ada
    pemotongan teks dan blok pengesahan tidak dipaksa menempel di halaman pertama.
--}}
@php
    /** Tanggal sipil dicetak tanpa konversi timezone agar tanggal surat tidak bergeser. */
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    $tanggal = function (?string $value) use ($bulan): string {
        if ($value === null) {
            return '-';
        }

        [$y, $m, $d] = array_pad(array_map('intval', explode('-', $value)), 3, 0);

        return ($y && $m && $d) ? sprintf('%d %s %d', $d, $bulan[$m] ?? $m, $y) : $value;
    };

    $waktu = collect([
        $tanggal($payload['activity_start_date']),
        $payload['activity_end_date'] ? 's.d. '.$tanggal($payload['activity_end_date']) : null,
        $payload['activity_start_time']
            ? 'pukul '.$payload['activity_start_time']
                .($payload['activity_end_time'] ? '–'.$payload['activity_end_time'] : '').' WIB'
            : null,
    ])->filter()->implode(' ');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Formulir Model A — {{ $payload['report_number'] ?? 'tanpa nomor' }}</title>
    <style>
        @page { margin: 16mm 18mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5pt;
            line-height: 1.3;
            color: #000;
        }

        .marker {
            border: 1px solid #000;
            padding: 4pt 6pt;
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1pt;
            margin-bottom: 8pt;
        }

        .marker .note { font-weight: normal; letter-spacing: 0; font-size: 8pt; }

        header { text-align: center; margin-bottom: 10pt; }
        header .form-code { font-size: 9pt; letter-spacing: 2pt; }
        header h1 { font-size: 12pt; margin: 4pt 0 2pt; text-transform: uppercase; }
        header .institution { font-size: 9pt; }
        header .number { font-size: 10pt; margin-top: 4pt; }

        h2 {
            font-size: 10.5pt;
            margin: 11pt 0 5pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 2pt;
        }

        table.fields { width: 100%; border-collapse: collapse; }
        table.fields td { vertical-align: top; padding: 1.5pt 0; }
        table.fields td.label { width: 38%; }
        table.fields td.sep { width: 4%; }

        .findings { text-align: justify; white-space: pre-line; }

        /* Blok pengesahan tidak dipecah di tengah, tetapi boleh pindah halaman. */
        .signature { margin-top: 16pt; page-break-inside: avoid; }
        .signature .box { width: 55%; margin-left: 45%; }
        .signature .space { height: 40pt; }
        .signature .name { font-weight: bold; text-decoration: underline; }

        .attachments { margin-top: 12pt; font-size: 9.5pt; page-break-inside: avoid; }
        .attachments ol { margin: 4pt 0 0 14pt; padding: 0; }

        footer.meta {
            margin-top: 12pt;
            border-top: 1px solid #999;
            padding-top: 4pt;
            font-size: 7.5pt;
            color: #444;
        }
    </style>
</head>
<body>

@if ($marker)
    <div class="marker">
        {{ $marker['label'] }}
        <div class="note">{{ $marker['note'] }}</div>
    </div>
@endif

<header>
    <div class="form-code">FORMULIR MODEL A</div>
    <h1>Laporan Hasil Pengawasan</h1>
    @if ($payload['institution_name'] || $payload['regency_name'])
        <div class="institution">
            {{ collect([$payload['institution_name'], $payload['regency_name']])->filter()->implode(' — ') }}
        </div>
    @endif
    <div class="institution">{{ $payload['district_name'] ?? $report->district->name }}</div>
    <div class="number">Nomor: {{ $payload['report_number'] ?? '-' }}</div>
</header>

<h2>I. Data Pengawas Pemilu</h2>
<table class="fields">
    <tr>
        <td class="label">Nama pelaksana tugas pengawasan</td>
        <td class="sep">:</td>
        <td>{{ $payload['supervisor_name'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Jabatan</td>
        <td class="sep">:</td>
        <td>{{ $payload['supervisor_position'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Nomor surat perintah tugas</td>
        <td class="sep">:</td>
        <td>{{ $payload['assignment_number'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Tanggal surat perintah tugas</td>
        <td class="sep">:</td>
        <td>{{ $tanggal($payload['assignment_date']) }}</td>
    </tr>
    <tr>
        <td class="label">Alamat</td>
        <td class="sep">:</td>
        <td class="findings">{{ $payload['supervisor_address'] ?? '-' }}</td>
    </tr>
</table>

<h2>II. Kegiatan Pengawasan</h2>
<table class="fields">
    <tr>
        <td class="label">Kegiatan</td>
        <td class="sep">:</td>
        <td>{{ $payload['activity_name'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Bentuk</td>
        <td class="sep">:</td>
        <td class="findings">{{ $payload['activity_form'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Tujuan</td>
        <td class="sep">:</td>
        <td class="findings">{{ $payload['activity_purpose'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Sasaran</td>
        <td class="sep">:</td>
        <td class="findings">{{ $payload['activity_target'] ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Waktu</td>
        <td class="sep">:</td>
        <td>{{ $waktu !== '' ? $waktu : '-' }}</td>
    </tr>
    <tr>
        <td class="label">Tempat</td>
        <td class="sep">:</td>
        <td class="findings">{{ $payload['activity_location'] ?? '-' }}</td>
    </tr>
</table>

<h2>III. Uraian Singkat Hasil Pengawasan</h2>
<div class="findings">{{ $payload['findings'] ?? '-' }}</div>

<div class="signature">
    <div class="box">
        <div>{{ $payload['signing_place'] ?? '-' }}, {{ $tanggal($payload['signing_date']) }}</div>
        <div>{{ $signerCapacity ?? '-' }} Pengawas Pemilu</div>
        <div class="space"></div>
        <div class="name">{{ $payload['signer_name'] ?? '-' }}</div>
    </div>
</div>

@if ($version->attachments->isNotEmpty())
    <div class="attachments">
        <strong>Lampiran ({{ $version->attachments->count() }})</strong>
        <ol>
            @foreach ($version->attachments as $attachment)
                <li>
                    {{ $attachment->file->original_name }} — {{ $attachment->category->label() }}
                    @if ($attachment->description)
                        ({{ $attachment->description }})
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
@endif

<footer class="meta">
    Versi {{ $version->version_number }} ·
    {{ $version->submitted_at ? 'dikirim '.$version->submitted_at->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : 'belum dikirim' }} ·
    dicetak {{ $generatedAt->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB ·
    template v{{ $templateVersion }}.
    Persetujuan pada aplikasi bukan tanda tangan elektronik tersertifikasi.
</footer>

</body>
</html>
