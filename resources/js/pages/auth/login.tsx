import { FieldError } from '@/components/field-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AuthLayout } from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    }

    return (
        <>
            <Head title="Masuk" />
            <AuthLayout
                title="Masuk"
                description="Gunakan akun yang diberikan Admin Kabupaten."
            >
                {status ? (
                    <Alert className="mb-4">
                        <AlertDescription>{status}</AlertDescription>
                    </Alert>
                ) : null}

                <form onSubmit={submit} className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            autoComplete="username"
                            autoFocus
                            required
                            aria-invalid={Boolean(errors.email)}
                            onChange={(event) =>
                                setData('email', event.target.value)
                            }
                        />
                        <FieldError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Kata Sandi</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            autoComplete="current-password"
                            required
                            aria-invalid={Boolean(errors.password)}
                            onChange={(event) =>
                                setData('password', event.target.value)
                            }
                        />
                        <FieldError message={errors.password} />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="remember"
                            className="size-4 rounded border-input"
                            checked={data.remember}
                            onChange={(event) =>
                                setData('remember', event.target.checked)
                            }
                        />
                        Ingat saya di perangkat ini
                    </label>

                    <Button type="submit" disabled={processing}>
                        {processing ? 'Memproses…' : 'Masuk'}
                    </Button>
                </form>

                <p className="mt-6 text-xs text-muted-foreground">
                    Pendaftaran mandiri tidak tersedia. Hubungi Admin Kabupaten
                    untuk pembuatan atau pemulihan akun.
                </p>
            </AuthLayout>
        </>
    );
}
