<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

/**
 * Bootstrap akun Admin Kabupaten pertama. Password diminta interaktif dan
 * tersembunyi; tidak ada argumen password agar tidak bocor ke shell history,
 * dan tidak ada kredensial produksi bawaan di seeder.
 */
class CreateAdminKabupaten extends Command
{
    protected $signature = 'siaplapor:create-admin-kabupaten
                            {--name= : Nama lengkap admin kabupaten}
                            {--email= : Alamat email untuk login}';

    protected $description = 'Membuat akun Admin Kabupaten dengan kata sandi yang diinput tersembunyi';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: text(
            label: 'Nama lengkap Admin Kabupaten',
            required: true,
        ));

        $email = mb_strtolower(trim((string) ($this->option('email') ?: text(
            label: 'Email untuk login',
            required: true,
        ))));

        $password = promptPassword(label: 'Kata sandi', required: true);
        $confirmation = promptPassword(label: 'Ulangi kata sandi', required: true);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::default()->min(12)],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
            'password' => 'kata sandi',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->role = UserRole::AdminKabupaten;
        $user->district_id = null;
        $user->is_active = true;
        $user->must_change_password = false;
        $user->save();

        $this->components->info("Akun Admin Kabupaten dibuat untuk {$user->email}.");

        return self::SUCCESS;
    }
}
