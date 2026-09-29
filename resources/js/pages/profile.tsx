import { FieldError } from '@/components/field-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AppLayout } from '@/layouts/app-layout';
import { useForm, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';

export default function Profile() {
    const user = usePage().props.auth.user;

    const profile = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
    });

    const password = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    function submitProfile(event: FormEvent) {
        event.preventDefault();
        profile.patch('/profil', { preserveScroll: true });
    }

    function submitPassword(event: FormEvent) {
        event.preventDefault();
        password.put('/profil/kata-sandi', {
            preserveScroll: true,
            onSuccess: () => password.reset(),
        });
    }

    return (
        <AppLayout
            title="Profil Saya"
            description="Kelola identitas dan kata sandi akun Anda."
        >
            {user?.must_change_password ? (
                <Alert>
                    <KeyRound className="size-4" />
                    <AlertTitle>Ganti kata sandi sementara</AlertTitle>
                    <AlertDescription>
                        Akun Anda masih memakai kata sandi sementara dari Admin
                        Kabupaten. Segera ganti pada formulir di bawah.
                    </AlertDescription>
                </Alert>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Informasi Akun</CardTitle>
                    <CardDescription>
                        Role dan kecamatan hanya dapat diubah oleh Admin
                        Kabupaten.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={submitProfile}
                        className="grid max-w-md gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={profile.data.name}
                                required
                                aria-invalid={Boolean(profile.errors.name)}
                                onChange={(event) =>
                                    profile.setData('name', event.target.value)
                                }
                            />
                            <FieldError message={profile.errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={profile.data.email}
                                required
                                aria-invalid={Boolean(profile.errors.email)}
                                onChange={(event) =>
                                    profile.setData('email', event.target.value)
                                }
                            />
                            <FieldError message={profile.errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Role</Label>
                            <p className="text-sm text-muted-foreground">
                                {user?.role_label}
                                {user?.district
                                    ? ` · ${user.district.name}`
                                    : ''}
                            </p>
                        </div>

                        <div>
                            <Button type="submit" disabled={profile.processing}>
                                Simpan Perubahan
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Ganti Kata Sandi
                    </CardTitle>
                    <CardDescription>
                        Minimal 8 karakter. Gunakan kombinasi yang tidak mudah
                        ditebak.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={submitPassword}
                        className="grid max-w-md gap-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">
                                Kata Sandi Saat Ini
                            </Label>
                            <Input
                                id="current_password"
                                type="password"
                                autoComplete="current-password"
                                value={password.data.current_password}
                                required
                                aria-invalid={Boolean(
                                    password.errors.current_password,
                                )}
                                onChange={(event) =>
                                    password.setData(
                                        'current_password',
                                        event.target.value,
                                    )
                                }
                            />
                            <FieldError
                                message={password.errors.current_password}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="new_password">
                                Kata Sandi Baru
                            </Label>
                            <Input
                                id="new_password"
                                type="password"
                                autoComplete="new-password"
                                value={password.data.password}
                                required
                                aria-invalid={Boolean(password.errors.password)}
                                onChange={(event) =>
                                    password.setData(
                                        'password',
                                        event.target.value,
                                    )
                                }
                            />
                            <FieldError message={password.errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Ulangi Kata Sandi Baru
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={password.data.password_confirmation}
                                required
                                onChange={(event) =>
                                    password.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div>
                            <Button
                                type="submit"
                                disabled={password.processing}
                            >
                                Perbarui Kata Sandi
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
