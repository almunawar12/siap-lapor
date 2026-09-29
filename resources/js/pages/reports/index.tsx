import { StatusBadge } from '@/components/status-badge';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { AppLayout } from '@/layouts/app-layout';
import { formatWaktu } from '@/lib/format';
import { paginationLabel } from '@/lib/pagination';
import type { Option, Paginated, ReportListItem } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type Props = {
    reports: Paginated<ReportListItem>;
    filters: {
        q: string;
        status: string;
        period: number | null;
        district: number | null;
    };
    statuses: Option[];
    periods: { id: number; name: string }[];
    districts: { id: number; code: string; name: string }[] | null;
    can_create: boolean;
};

export default function ReportsIndex({
    reports,
    filters,
    statuses,
    periods,
    districts,
    can_create,
}: Props) {
    const [form, setForm] = useState({
        q: filters.q,
        status: filters.status,
        period: filters.period ? String(filters.period) : '',
        district: filters.district ? String(filters.district) : '',
    });

    function apply(event: FormEvent) {
        event.preventDefault();
        router.get('/reports', form, { preserveState: true, replace: true });
    }

    function reset() {
        const cleared = { q: '', status: '', period: '', district: '' };
        setForm(cleared);
        router.get('/reports', cleared, { replace: true });
    }

    return (
        <AppLayout
            title="Laporan Hasil Pengawasan"
            description="Daftar LHP Formulir Model A sesuai cakupan wilayah Anda."
            actions={
                can_create ? (
                    <Button asChild>
                        <Link href="/reports/create">
                            <Plus className="size-4" />
                            Buat LHP
                        </Link>
                    </Button>
                ) : null
            }
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Filter</CardTitle>
                    <CardDescription>
                        Pencarian mencakup nomor LHP dan nama kegiatan.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={apply}
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="q">Cari</Label>
                            <Input
                                id="q"
                                value={form.q}
                                placeholder="Nomor atau nama kegiatan"
                                onChange={(event) =>
                                    setForm({ ...form, q: event.target.value })
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <select
                                id="status"
                                value={form.status}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        status: event.target.value,
                                    })
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">Semua status</option>
                                {statuses.map((status) => (
                                    <option
                                        key={status.value}
                                        value={status.value}
                                    >
                                        {status.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="period">Periode</Label>
                            <select
                                id="period"
                                value={form.period}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        period: event.target.value,
                                    })
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">Semua periode</option>
                                {periods.map((period) => (
                                    <option key={period.id} value={period.id}>
                                        {period.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {districts ? (
                            <div className="grid gap-2">
                                <Label htmlFor="district">Kecamatan</Label>
                                <select
                                    id="district"
                                    value={form.district}
                                    onChange={(event) =>
                                        setForm({
                                            ...form,
                                            district: event.target.value,
                                        })
                                    }
                                    className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    <option value="">Semua kecamatan</option>
                                    {districts.map((district) => (
                                        <option
                                            key={district.id}
                                            value={district.id}
                                        >
                                            {district.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        ) : null}

                        <div className="flex gap-2 sm:col-span-2 lg:col-span-4">
                            <Button type="submit" variant="secondary">
                                Terapkan
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={reset}
                            >
                                Reset
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {reports.total.toLocaleString('id-ID')} laporan
                    </CardTitle>
                    <CardDescription>
                        Satu kecamatan dapat membuat beberapa LHP dalam satu
                        periode.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {reports.data.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada laporan yang sesuai filter ini.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nomor</TableHead>
                                        <TableHead>Kegiatan</TableHead>
                                        <TableHead>Kecamatan</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Diperbarui</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reports.data.map((report) => (
                                        <TableRow key={report.id}>
                                            <TableCell className="font-medium whitespace-nowrap">
                                                {report.report_number ?? '—'}
                                            </TableCell>
                                            <TableCell className="max-w-64 truncate">
                                                {report.activity_name ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                {report.district.name}
                                            </TableCell>
                                            <TableCell>
                                                {report.period.name}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    status={report.status}
                                                    label={report.status_label}
                                                />
                                                {report.version_number ? (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        v{report.version_number}
                                                    </span>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="text-xs whitespace-nowrap text-muted-foreground">
                                                {formatWaktu(report.updated_at)}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={`/reports/${report.id}`}
                                                    >
                                                        Buka
                                                    </Link>
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}

                    {reports.last_page > 1 ? (
                        <div className="flex flex-wrap gap-1">
                            {reports.links.map((link, index) => (
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
