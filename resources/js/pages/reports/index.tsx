import { OptionSelect } from '@/components/option-select';
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
import {
    ArrowRight,
    FileSearch,
    Plus,
    Search,
    SlidersHorizontal,
} from 'lucide-react';
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

    const activeFilterCount = [
        filters.q,
        filters.status,
        filters.period,
        filters.district,
    ].filter(Boolean).length;

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
            description="Cari, periksa, dan kelola LHP Formulir Model A sesuai cakupan wilayah Anda."
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
                    <div className="flex items-start gap-3">
                        <span className="flex size-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <SlidersHorizontal
                                className="size-4"
                                aria-hidden="true"
                            />
                        </span>
                        <div>
                            <CardTitle className="text-base">
                                Cari dan saring laporan
                            </CardTitle>
                            <CardDescription className="mt-1">
                                Pencarian mencakup nomor LHP dan nama kegiatan.
                                {activeFilterCount > 0
                                    ? ` ${activeFilterCount} filter sedang aktif.`
                                    : ''}
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <form
                        onSubmit={apply}
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div className="grid gap-2 sm:col-span-2 lg:col-span-1">
                            <Label htmlFor="q">Nomor atau kegiatan</Label>
                            <Input
                                id="q"
                                value={form.q}
                                placeholder="Contoh: 001/LHP atau coklit"
                                onChange={(event) =>
                                    setForm({ ...form, q: event.target.value })
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <OptionSelect
                                id="status"
                                value={form.status}
                                onValueChange={(value) =>
                                    setForm({ ...form, status: value })
                                }
                                tall
                                options={[
                                    { value: '', label: 'Semua status' },
                                    ...statuses,
                                ]}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="period">Periode</Label>
                            <OptionSelect
                                id="period"
                                value={form.period}
                                onValueChange={(value) =>
                                    setForm({ ...form, period: value })
                                }
                                tall
                                options={[
                                    { value: '', label: 'Semua periode' },
                                    ...periods.map((period) => ({
                                        value: period.id,
                                        label: period.name,
                                    })),
                                ]}
                            />
                        </div>

                        {districts ? (
                            <div className="grid gap-2">
                                <Label htmlFor="district">Kecamatan</Label>
                                <OptionSelect
                                    id="district"
                                    value={form.district}
                                    onValueChange={(value) =>
                                        setForm({ ...form, district: value })
                                    }
                                    tall
                                    options={[
                                        { value: '', label: 'Semua kecamatan' },
                                        ...districts.map((district) => ({
                                            value: district.id,
                                            label: district.name,
                                        })),
                                    ]}
                                />
                            </div>
                        ) : null}

                        <div className="flex flex-col gap-2 sm:col-span-2 sm:flex-row lg:col-span-4">
                            <Button type="submit" variant="secondary">
                                <Search className="size-4" />
                                Tampilkan hasil
                            </Button>
                            {activeFilterCount > 0 ? (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={reset}
                                >
                                    Hapus semua filter
                                </Button>
                            ) : null}
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {reports.total.toLocaleString('id-ID')} laporan
                        ditemukan
                    </CardTitle>
                    <CardDescription>
                        Satu kecamatan dapat membuat beberapa LHP dalam satu
                        periode.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {reports.data.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-md border border-dashed px-6 py-10 text-center">
                            <FileSearch
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <div>
                                <p className="text-sm font-medium">
                                    Tidak ada laporan yang cocok
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Ubah kata pencarian atau hapus filter yang
                                    sedang aktif.
                                </p>
                            </div>
                            {activeFilterCount > 0 ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={reset}
                                >
                                    Hapus filter
                                </Button>
                            ) : null}
                        </div>
                    ) : (
                        <>
                            <ul className="space-y-3 md:hidden">
                                {reports.data.map((report) => (
                                    <li
                                        key={report.id}
                                        className="rounded-md border bg-background p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold break-words">
                                                    {report.report_number ??
                                                        '-'}
                                                </p>
                                                <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                                    {report.activity_name ??
                                                        'Kegiatan belum diisi'}
                                                </p>
                                            </div>
                                            <StatusBadge
                                                status={report.status}
                                                label={report.status_label}
                                            />
                                        </div>
                                        <dl className="mt-4 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                                            <div>
                                                <dt className="text-muted-foreground">
                                                    Kecamatan
                                                </dt>
                                                <dd className="mt-0.5 font-medium">
                                                    {report.district.name}
                                                </dd>
                                            </div>
                                            <div>
                                                <dt className="text-muted-foreground">
                                                    Periode
                                                </dt>
                                                <dd className="mt-0.5 font-medium">
                                                    {report.period.name}
                                                </dd>
                                            </div>
                                            <div className="col-span-2">
                                                <dt className="text-muted-foreground">
                                                    Terakhir diperbarui
                                                </dt>
                                                <dd className="mt-0.5 font-medium">
                                                    {formatWaktu(
                                                        report.updated_at,
                                                    )}
                                                </dd>
                                            </div>
                                        </dl>
                                        <Button
                                            asChild
                                            variant="outline"
                                            size="sm"
                                            className="mt-4 w-full"
                                        >
                                            <Link
                                                href={`/reports/${report.id}`}
                                            >
                                                Buka laporan
                                                <ArrowRight className="size-4" />
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>

                            <div className="hidden overflow-x-auto md:block">
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
                                                    {report.report_number ??
                                                        '-'}
                                                </TableCell>
                                                <TableCell className="max-w-64 truncate">
                                                    {report.activity_name ??
                                                        '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {report.district.name}
                                                </TableCell>
                                                <TableCell>
                                                    {report.period.name}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex items-center gap-2">
                                                        <StatusBadge
                                                            status={
                                                                report.status
                                                            }
                                                            label={
                                                                report.status_label
                                                            }
                                                        />
                                                        {report.version_number ? (
                                                            <span className="text-xs text-muted-foreground">
                                                                v
                                                                {
                                                                    report.version_number
                                                                }
                                                            </span>
                                                        ) : null}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="text-xs whitespace-nowrap text-muted-foreground">
                                                    {formatWaktu(
                                                        report.updated_at,
                                                    )}
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
                        </>
                    )}

                    {reports.last_page > 1 ? (
                        <nav
                            aria-label="Halaman daftar laporan"
                            className="flex flex-wrap gap-1"
                        >
                            {reports.links.map((link, index) => (
                                <Button
                                    key={index}
                                    size="sm"
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    disabled={link.url === null}
                                    aria-current={
                                        link.active ? 'page' : undefined
                                    }
                                    onClick={() =>
                                        link.url && router.get(link.url)
                                    }
                                >
                                    {paginationLabel(link.label)}
                                </Button>
                            ))}
                        </nav>
                    ) : null}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
