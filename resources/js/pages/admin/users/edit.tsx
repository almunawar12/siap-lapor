import { FieldError } from '@/components/field-error';
import { Badge } from '@/components/ui/badge';
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
import type { DistrictRef } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type EditableUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    must_change_password: boolean;
    district: DistrictRef | null;
};

export default function UserEdit({ user }: { user: EditableUser }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        patch(`/admin/users/${user.id}`);
    }

    return (
        <AppLayout
            title="Ubah Akun Kecamatan"
            description={
                user.district
                    ? `${user.district.name} (${user.district.code})`
                    : undefined
            }
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Data Akun</CardTitle>
                    <CardDescription>
                        Kecamatan tidak dapat dipindahkan pada versi ini.
                        Kosongkan kata sandi bila tidak ingin mengubahnya.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid max-w-md gap-4">
                        <div className="grid gap-2">
                            <Label>Status</Label>
                            <div>
                                <Badge
                                    variant={
                                        user.is_active ? 'default' : 'secondary'
                                    }
                                >
                                    {user.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={data.name}
                                required
                                aria-invalid={Boolean(errors.name)}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                            />
                            <FieldError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                required
                                aria-invalid={Boolean(errors.email)}
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                            />
                            <FieldError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Kata Sandi Baru (opsional)
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                aria-invalid={Boolean(errors.password)}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                            />
                            <FieldError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Ulangi Kata Sandi Baru
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Simpan Perubahan
                            </Button>
                            <Button asChild variant="outline" type="button">
                                <Link href="/admin/users">Batal</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
