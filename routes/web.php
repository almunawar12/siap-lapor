<?php

use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\ReportingPeriodController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportAttachmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPdfController;
use App\Http\Controllers\ReportReviewController;
use App\Http\Controllers\RevisionNoteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

/*
| Tidak ada route registrasi publik. Akun Admin Kabupaten dibuat melalui
| perintah artisan `siaplapor:create-admin-kabupaten`, akun Admin Kecamatan
| dibuat oleh Admin Kabupaten pada /admin/users.
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profile.password');

    /*
    | Laporan Hasil Pengawasan. Tidak ada endpoint generik untuk mengubah
    | status; setiap transisi punya route sendiri.
    */
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::patch('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::post('reports/{report}/submit', [ReportController::class, 'submit'])->name('reports.submit');
    Route::get('reports/{report}/versions/{version}', [ReportController::class, 'version'])
        ->name('reports.versions.show');
    Route::get('reports/{report}/versions/{version}/pdf', ReportPdfController::class)
        ->name('reports.versions.pdf');

    /*
    | Pemeriksaan dan revisi. Setiap transisi punya route sendiri; tidak ada
    | endpoint generik untuk menetapkan status.
    */
    Route::post('reports/{report}/review/start', [ReportReviewController::class, 'start'])
        ->name('reports.review.start');
    Route::post('reports/{report}/review/takeover', [ReportReviewController::class, 'takeover'])
        ->name('reports.review.takeover');
    Route::post('reports/{report}/review/return', [ReportReviewController::class, 'returnForRevision'])
        ->name('reports.review.return');
    Route::post('reports/{report}/review/approve', [ReportReviewController::class, 'approve'])
        ->name('reports.review.approve');
    Route::post('reports/{report}/reopen', [ReportReviewController::class, 'reopen'])
        ->name('reports.reopen');

    Route::post('reports/{report}/notes', [RevisionNoteController::class, 'store'])
        ->name('reports.notes.store');
    Route::post('reports/{report}/notes/{note}/responses', [RevisionNoteController::class, 'respond'])
        ->name('reports.notes.responses.store');
    Route::post('reports/{report}/notes/{note}/resolve', [RevisionNoteController::class, 'resolve'])
        ->name('reports.notes.resolve');
    Route::post('reports/{report}/notes/{note}/reopen', [RevisionNoteController::class, 'reopen'])
        ->name('reports.notes.reopen');

    Route::post('reports/{report}/attachments', [ReportAttachmentController::class, 'store'])
        ->name('reports.attachments.store');
    Route::delete('reports/{report}/attachments/{attachment}', [ReportAttachmentController::class, 'destroy'])
        ->name('reports.attachments.destroy');
    Route::get('reports/{report}/attachments/{attachment}/download', [ReportAttachmentController::class, 'download'])
        ->name('reports.attachments.download');

    Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');
    Route::patch('notifikasi/{notification}', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    /*
    | Otorisasi sebenarnya ada pada policy di dalam controller. Prefix ini hanya
    | pengelompokan route, bukan satu-satunya kontrol akses.
    */
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/status', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
        Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::patch('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');

        Route::get('periods', [ReportingPeriodController::class, 'index'])->name('periods.index');
        Route::post('periods', [ReportingPeriodController::class, 'store'])->name('periods.store');
        Route::patch('periods/{period}', [ReportingPeriodController::class, 'update'])->name('periods.update');
    });
});
