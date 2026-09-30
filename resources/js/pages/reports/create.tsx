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

            <Card className="max-w-2xl">
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
                    <form onSubmit={submit} className="grid gap-5">
                        <div className="rounded-md border bg-muted/50 p-4">
                            <Label>Kecamatan asal laporan</Label>
                            <p className="mt-1 text-sm font-medium">
                                {district
                                    ? `${district.name} (${district.code})`
                                    : '-'}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Wilayah ditetapkan otomatis dari akun Anda dan
                                tidak dapat diubah pada formulir.
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="reporting_period_id">Periode</Label>
                            <OptionSelect
                                id="reporting_period_id"
                                aria-required
                                value={data.reporting_period_id}
                                aria-invalid={Boolean(
                                    errors.reporting_period_id,
                                )}
                                onValueChange={(value) =>
                                    setData('reporting_period_id', value)
                                }
                                placeholder="Pilih periode"
                                tall
                                options={periods.map((period) => ({
                                    value: period.id,
                                    label: period.submission_deadline
                                        ? `${period.name}, batas ${formatTanggal(period.submission_deadline)}`
                                        : period.name,
                                }))}
                            />
                            <FieldError message={errors.reporting_period_id} />
                        </div>

                        <div className="flex flex-col-reverse gap-2 sm:flex-row">
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
