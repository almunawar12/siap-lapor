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
import { Label } from '@/components/ui/label';
import { AppLayout } from '@/layouts/app-layout';
import { formatTanggal } from '@/lib/format';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Info } from 'lucide-react';
import type { FormEvent } from 'react';

type Period = {
    id: number;
    name: string;
    submission_deadline: string | null;
};

export default function ReportCreate({ periods }: { periods: Period[] }) {
    const district = usePage().props.auth.user?.district;
    const { data, setData, post, processing, errors } = useForm({
        reporting_period_id: periods.length === 1 ? String(periods[0].id) : '',
    });

    function submit(event: FormEvent) {
        event.preventDefault();
        post('/reports');
    }

    return (
        <AppLayout
            title="Buat LHP Baru"
            description="Kecamatan diisi otomatis dari akun Anda."
        >
            {periods.length === 0 ? (
                <Alert>
                    <Info className="size-4" />
                    <AlertTitle>Belum ada periode aktif</AlertTitle>
                    <AlertDescription>
                        Admin Kabupaten belum membuka periode pelaporan yang
                        aktif. Laporan baru belum dapat dibuat.
                    </AlertDescription>
                </Alert>
            ) : null}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        Periode Pelaporan
                    </CardTitle>
                    <CardDescription>
                        Draf akan dibuat sebagai versi 1 dan dapat disimpan
                        bertahap sebelum dikirim.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="grid max-w-md gap-4">
                        <div className="grid gap-2">
                            <Label>Kecamatan</Label>
                            <p className="text-sm text-muted-foreground">
                                {district
                                    ? `${district.name} (${district.code})`
                                    : '—'}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="reporting_period_id">Periode</Label>
                            <select
                                id="reporting_period_id"
                                required
                                value={data.reporting_period_id}
                                aria-invalid={Boolean(
                                    errors.reporting_period_id,
                                )}
                                onChange={(event) =>
                                    setData(
                                        'reporting_period_id',
                                        event.target.value,
                                    )
                                }
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <option value="">Pilih periode</option>
                                {periods.map((period) => (
                                    <option key={period.id} value={period.id}>
                                        {period.name}
                                        {period.submission_deadline
                                            ? ` — batas ${formatTanggal(period.submission_deadline)}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <FieldError message={errors.reporting_period_id} />
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="submit"
                                disabled={processing || periods.length === 0}
                            >
                                Buat Draf
                            </Button>
                            <Button asChild variant="outline" type="button">
                                <Link href="/reports">Batal</Link>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
