<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('q'));

        $users = User::query()
            ->kecamatan()
            ->with('district:id,code,name')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'district' => $user->district?->only(['id', 'code', 'name']),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('admin/users/create', [
            'districts' => $this->activeDistricts(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = new User;
        $user->fill($request->safe()->only(['name', 'email', 'password']));

        // Role dan kecamatan ditetapkan server, bukan dari payload.
        $user->role = UserRole::AdminKecamatan;
        $user->district_id = $request->integer('district_id');
        $user->is_active = true;
        $user->must_change_password = true;
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil dibuat. Sampaikan kata sandi sementara melalui kanal resmi.");
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->loadMissing('district:id,code,name');

        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'district' => $user->district?->only(['id', 'code', 'name']),
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = (string) $request->string('password');
            $user->must_change_password = true;
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Akun tidak dihapus agar atribusi pada arsip tetap utuh (prd.md bagian 4).
     */
    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with(
            'success',
            $user->is_active
                ? "Akun {$user->name} diaktifkan."
                : "Akun {$user->name} dinonaktifkan."
        );
    }

    /** @return array<int, array{id: int, code: string, name: string}> */
    protected function activeDistricts(): array
    {
        return District::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (District $d): array => ['id' => $d->id, 'code' => $d->code, 'name' => $d->name])
            ->all();
    }
}
