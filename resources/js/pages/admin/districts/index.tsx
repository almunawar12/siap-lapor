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
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AppLayout } from '@/layouts/app-layout';
import { paginationLabel } from '@/lib/pagination';
import type { Paginated } from '@/types';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Row = {
    id: number;
    code: string;
    name: string;
    is_active: boolean;
    users_count: number;
};

export default function DistrictsIndex({
    districts,
}: {
    districts: Paginated<Row>;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        name: '',
        is_active: true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/admin/districts', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function toggle(district: Row) {
        router.patch(
            `/admin/districts/${district.id}`,
            {
                code: district.code,
                name: district.name,
                is_active: !district.is_active,
            },
            { preserveScroll: true },
        );
    }

    return (
        <AppLayout
            title="Data Kecamatan"
            description="Daftar kecamatan yang dipakai untuk penugasan akun dan pelaporan."
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Tambah Kecamatan
                    </CardTitle>
                    <CardDescription>
                        Daftar kecamatan resmi belum dikonfirmasi pemilik
                        proyek. Isi sesuai dokumen resmi wilayah Anda.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={submit}
                        className="grid gap-4 sm:grid-cols-[1fr_2fr_auto] sm:items-end"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="code">Kode</Label>
                            <Input
                                id="code"
                                value={data.code}
                                required
                                placeholder="Contoh: 32.04.01"
                                aria-invalid={Boolean(errors.code)}
                                onChange={(event) =>
                                    setData('code', event.target.value)
                                }
                            />
                            <FieldError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Kecamatan</Label>
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

                        <Button type="submit" disabled={processing}>
                            Tambah
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Daftar Kecamatan
                    </CardTitle>
                    <CardDescription>
                        Kecamatan tidak dihapus agar riwayat laporan tetap
                        tertaut. Nonaktifkan bila tidak dipakai lagi.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {districts.data.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada kecamatan.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Jumlah Akun</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aktif
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {districts.data.map((district) => (
                                        <TableRow key={district.id}>
                                            <TableCell className="font-mono text-xs">
                                                {district.code}
                                            </TableCell>
                                            <TableCell className="font-medium">
                                                {district.name}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {district.users_count}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        district.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {district.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Switch
                                                    checked={district.is_active}
                                                    aria-label={`Ubah status ${district.name}`}
                                                    onCheckedChange={() =>
                                                        toggle(district)
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}

                    {districts.last_page > 1 ? (
                        <div className="flex flex-wrap gap-1">
                            {districts.links.map((link, index) => (
                                <Button
                                    key={index}
                                    size="sm"
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    disabled={link.url === null}
                                    onClick={() =>
                                        link.url && router.get(link.url)
                                    }
                                >
                                    {paginationLabel(link.label)}
                                </Button>
                            ))}
                        </div>
                    ) : null}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
