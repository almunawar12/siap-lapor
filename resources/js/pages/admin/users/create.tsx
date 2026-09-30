import { FieldError } from '@/components/field-error';
import { OptionSelect } from '@/components/option-select';
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
import type { DistrictRef } from '@/types';
import { Link, useForm } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { FormEvent } from 'react';

export default function UserCreate({
    districts,
}: {
    districts: DistrictRef[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        district_id: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/admin/users');
    }

    return (
        <AppLayout
            title="Tambah Akun Kecamatan"
            description="Akun baru selalu dibuat dengan role Admin Kecamatan."
        >
            {districts.length === 0 ? (
                <Alert>
                    <Info className="size-4" />
                    <AlertTitle>Belum ada kecamatan aktif</AlertTitle>
                    <AlertDescription>
                        Tambahkan data kecamatan terlebih dahulu pada menu{' '}
                        <Link
                            href="/admin/districts"
                            className="font-medium underline"
                        >
                            Data Kecamatan
                        </Link>
                        .
                    </AlertDescription>
                </Alert>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Data Akun</CardTitle>
                    <CardDescription>
                        Kata sandi sementara wajib diganti pengguna saat masuk
                        pertama kali. Sampaikan melalui kanal resmi, bukan dari
                        aplikasi ini.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid max-w-md gap-4">
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
                            <Label htmlFor="district_id">Kecamatan</Label>
                            <OptionSelect
                                id="district_id"
                                value={data.district_id}
                                aria-required
                                aria-invalid={Boolean(errors.district_id)}
                                onValueChange={(value) =>
                                    setData('district_id', value)
                                }
                                placeholder="Pilih kecamatan"
                                options={districts.map((district) => ({
                                    value: district.id,
                                    label: `${district.name} (${district.code})`,
                                }))}
                            />
                            <FieldError message={errors.district_id} />
                            <p className="text-xs text-muted-foreground">
                                Kecamatan akun tidak dapat diubah setelah
                                disimpan. Nonaktifkan akun lama dan buat akun
                                baru bila terjadi mutasi.
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                Kata Sandi Sementara
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                autoComplete="new-password"
                                value={data.password}
                                required
                                aria-invalid={Boolean(errors.password)}
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                            />
                            <FieldError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Ulangi Kata Sandi Sementara
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                value={data.password_confirmation}
                                required
                                onChange={(event) =>
                                    setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing || districts.length === 0}
                            >
                                Simpan Akun
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
