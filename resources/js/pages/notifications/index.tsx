import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { AppLayout } from '@/layouts/app-layout';
import { formatWaktu } from '@/lib/format';
import { paginationLabel } from '@/lib/pagination';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { BellOff, CheckCheck } from 'lucide-react';

type Row = {
    id: string;
    event: string | null;
    title: string;
    body: string;
    report_id: number | null;
    report_number: string | null;
    district_name: string | null;
    status_label: string | null;
    read_at: string | null;
    created_at: string | null;
};

export default function NotificationsIndex({
    notifications,
    unread_count,
}: {
    notifications: Paginated<Row>;
    unread_count: number;
}) {
    return (
        <AppLayout
            title="Notifikasi"
            description="Pemberitahuan dalam aplikasi. Tidak ada email atau pesan otomatis."
            actions={
                unread_count > 0 ? (
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={() =>
                            router.patch(
                                '/notifikasi/baca-semua',
                                {},
                                {
                                    preserveScroll: true,
                                },
                            )
                        }
                    >
                        <CheckCheck className="size-4" />
                        Tandai Semua Dibaca
                    </Button>
                ) : null
            }
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">
                        {unread_count > 0
                            ? `${unread_count} belum dibaca`
                            : 'Semua sudah dibaca'}
                    </CardTitle>
                    <CardDescription>
                        Notifikasi hanya berisi ringkasan; buka laporan untuk
                        melihat detailnya.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {notifications.data.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed px-6 py-10 text-center">
                            <BellOff
                                className="size-8 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <p className="text-sm text-muted-foreground">
                                Belum ada notifikasi.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y rounded-md border">
                            {notifications.data.map((item) => (
                                <li
                                    key={item.id}
                                    className={cn(
                                        'flex flex-wrap items-start gap-3 p-3',
                                        item.read_at === null && 'bg-muted/40',
                                    )}
                                >
                                    <div className="min-w-0 flex-1 space-y-1">
                                        <p className="text-sm font-medium">
                                            {item.title}
                                            {item.read_at === null ? (
                                                <Badge className="ml-2">
                                                    Baru
                                                </Badge>
                                            ) : null}
                                        </p>
                                        <p className="text-sm">{item.body}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {[
                                                item.district_name,
                                                item.report_number,
                                                item.status_label,
                                                formatWaktu(item.created_at),
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </div>

                                    <div className="flex gap-2">
                                        {item.report_id ? (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link
                                                    href={`/reports/${item.report_id}`}
                                                >
                                                    Buka Laporan
                                                </Link>
                                            </Button>
                                        ) : null}
                                        {item.read_at === null ? (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.patch(
                                                        `/notifikasi/${item.id}`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Tandai Dibaca
                                            </Button>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {notifications.last_page > 1 ? (
                        <div className="flex flex-wrap gap-1">
                            {notifications.links.map((link, index) => (
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
