<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Konflik penyimpanan: pengguna lain sudah mengubah laporan, atau versi kerja
 * yang dikirim bukan versi terkini. Tidak pernah menimpa perubahan orang lain
 * (PRD AC14).
 */
class StaleReportException extends RuntimeException implements HttpExceptionInterface
{
    public function __construct(
        public readonly string $reason = 'Laporan sudah diubah pengguna lain. Muat ulang halaman sebelum menyimpan.',
    ) {
        parent::__construct($reason, 409);
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * Request browser/Inertia menerima error bag `conflict` (redirect 303) agar
     * konflik tampil di dekat form dan isian lokal tidak hilang, bukan sebagai
     * halaman error 409 mentah. Klien JSON tetap mendapat status 409 apa adanya
     * (ARCHITECTURE.md bagian 7).
     */
    public function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->reason], 409);
        }

        return back(303)->withErrors(['conflict' => $this->reason]);
    }
}
