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
import { formatTanggal } from '@/lib/format';
import { paginationLabel } from '@/lib/pagination';
import type { Paginated } from '@/types';
import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Row = {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    submission_deadline: string | null;
    is_active: boolean;
    reports_count: number;
};

export default function PeriodsIndex({ periods }: { periods: Paginated<Row> }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        starts_on: '',
        ends_on: '',
        submission_deadline: '',
        is_active: true,
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/admin/periods', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    function toggle(period: Row) {
        router.patch(
            `/admin/periods/${period.id}`,
            {
                name: period.name,
                starts_on: period.starts_on,
                ends_on: period.ends_on,
                submission_deadline: period.submission_deadline ?? '',
                is_active: !period.is_active,
            },
            { preserveScroll: true },
        );
    }

    return (
        <AppLayout
            title="Periode Pelaporan"
            description="Periode menentukan kapan laporan baru dapat dibuat."
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Tambah Periode</CardTitle>
                    <CardDescription>
                        Batas pengiriman bersifat opsional. Laporan yang
                        melewati batas tetap dapat dikirim, hanya diberi penanda
                        terlambat.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={submit}
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"
                    >
                        <div className="grid gap-2 lg:col-span-2">
                            <Label htmlFor="name">Nama periode</Label>
                            <Input
                                id="name"
                                required
                                maxLength={150}
                                placeholder="Contoh: Triwulan I 2026"
                                value={data.name}
                                aria-invalid={Boolean(errors.name)}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                            />
                            <FieldError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="starts_on">Tanggal mulai</Label>
                            <Input
                                id="starts_on"
                                type="date"
                                required
                                value={data.starts_on}
                                aria-invalid={Boolean(errors.starts_on)}
                                onChange={(event) =>
                                    setData('starts_on', event.target.value)
                                }
                            />
                            <FieldError message={errors.starts_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ends_on">Tanggal selesai</Label>
                            <Input
                                id="ends_on"
                                type="date"
                                required
                                value={data.ends_on}
                                aria-invalid={Boolean(errors.ends_on)}
                                onChange={(event) =>
                                    setData('ends_on', event.target.value)
                                }
                            />
                            <FieldError message={errors.ends_on} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="submission_deadline">
                                Batas pengiriman
                            </Label>
                            <Input
                                id="submission_deadline"
                                type="date"
                                value={data.submission_deadline}
                                aria-invalid={Boolean(
                                    errors.submission_deadline,
                                )}
                                onChange={(event) =>
                                    setData(
                                        'submission_deadline',
                                        event.target.value,
                                    )
                                }
                            />
                            <FieldError message={errors.submission_deadline} />
                        </div>

                        <div className="lg:col-span-5">
                            <Button type="submit" disabled={processing}>
                                Tambah Periode
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Daftar Periode</CardTitle>
                    <CardDescription>
                        Periode tidak dihapus. Periode nonaktif mencegah laporan
                        baru, tetapi laporan yang sudah ada tetap dapat
                        direvisi.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {periods.data.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada periode pelaporan.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Rentang</TableHead>
                                        <TableHead>Batas Pengiriman</TableHead>
                                        <TableHead>Jumlah Laporan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aktif
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {periods.data.map((period) => (
                                        <TableRow key={period.id}>
                                            <TableCell className="font-medium">
                                                {period.name}
                                            </TableCell>
                                            <TableCell className="text-sm whitespace-nowrap">
                                                {formatTanggal(
                                                    period.starts_on,
                                                )}{' '}
                                                –{' '}
                                                {formatTanggal(period.ends_on)}
                                            </TableCell>
                                            <TableCell className="text-sm whitespace-nowrap">
                                                {formatTanggal(
                                                    period.submission_deadline,
                                                )}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {period.reports_count}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        period.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {period.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Switch
                                                    checked={period.is_active}
                                                    aria-label={`Ubah status ${period.name}`}
                                                    onCheckedChange={() =>
                                                        toggle(period)
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}

                    {periods.last_page > 1 ? (
                        <div className="flex flex-wrap gap-1">
                            {periods.links.map((link, index) => (
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
