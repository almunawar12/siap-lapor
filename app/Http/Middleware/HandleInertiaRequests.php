<?php

namespace App\Http\Middleware;

use App\Models\ReportNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Hanya field yang dibutuhkan UI yang dibagikan. Jangan mengirim model user
     * lengkap (architecture.md bagian 8).
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('instansi.app_name'),
                'tagline' => config('instansi.app_tagline'),
                'institution_name' => config('instansi.institution_name'),
                'regency_name' => config('instansi.regency_name'),
            ],
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->role->label(),
                    'is_kabupaten' => $user->isKabupaten(),
                    'must_change_password' => $user->must_change_password,
                    'district' => $user->district?->only(['id', 'code', 'name']),
                ],
            ],
            // Jumlah notifikasi belum dibaca milik pengguna ini saja.
            'unread_notifications' => $user === null
                ? 0
                : ReportNotification::query()
                    ->where('notifiable_type', $user->getMorphClass())
                    ->where('notifiable_id', $user->getKey())
                    ->unread()
                    ->count(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
