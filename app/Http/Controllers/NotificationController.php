<?php

namespace App\Http\Controllers;

use App\Models\ReportNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Notifikasi selalu ter-scope ke penerimanya melalui relasi notifiable;
     * tidak ada cara membaca notifikasi pengguna lain.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = ReportNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->orderByDesc('created_at')
            ->paginate((int) config('siaplapor.pagination.per_page'))
            ->through(fn (ReportNotification $notification): array => [
                'id' => $notification->id,
                'event' => $notification->data['event'] ?? null,
                'title' => $notification->data['title'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'report_id' => $notification->report_id,
                'report_number' => $notification->data['report_number'] ?? null,
                'district_name' => $notification->data['district_name'] ?? null,
                'status_label' => $notification->data['status_label'] ?? null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unread_count' => $this->unreadCount($request),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $this->ownedQuery($request)->whereKey($notification)->firstOrFail()->markAsRead();

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->ownedQuery($request)->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /** @return Builder<ReportNotification> */
    protected function ownedQuery(Request $request)
    {
        $user = $request->user();

        return ReportNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey());
    }

    protected function unreadCount(Request $request): int
    {
        return $this->ownedQuery($request)->unread()->count();
    }
}
