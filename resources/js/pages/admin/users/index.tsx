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
import type { DistrictRef, Paginated } from '@/types';
import { Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';

type Row = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    must_change_password: boolean;
    district: DistrictRef | null;
};

type Props = {
    users: Paginated<Row>;
    filters: { q: string };
};

export default function UsersIndex({ users, filters }: Props) {
    const [query, setQuery] = useState(filters.q);

    function search(event: FormEvent) {
        event.preventDefault();
        router.get(
            '/admin/users',
            { q: query },
            { preserveState: true, replace: true },
        );
    }

    return (
        <AppLayout
            title="Akun Kecamatan"
            description="Buat dan kelola akun Admin Kecamatan."
            actions={
                <Button asChild>
                    <Link href="/admin/users/create">
                        <Plus className="size-4" />
                        Tambah Akun
                    </Link>
                </Button>
            }
        >
            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Daftar Akun</CardTitle>
                    <CardDescription>
                        Akun tidak dihapus agar atribusi pada arsip tetap utuh.
                        Gunakan tombol nonaktifkan bila akun tidak lagi dipakai.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <form
                        onSubmit={search}
                        className="flex flex-wrap items-end gap-2"
                    >
                        <div className="grid min-w-52 flex-1 gap-2">
                            <Label htmlFor="q">Cari nama atau email</Label>
                            <Input
                                id="q"
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Contoh: budi"
                            />
                        </div>
                        <Button type="submit" variant="secondary">
                            Cari
                        </Button>
                    </form>

                    {users.data.length === 0 ? (
                        <p className="rounded-lg border border-dashed px-6 py-10 text-center text-sm text-muted-foreground">
                            Belum ada akun Admin Kecamatan.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Kecamatan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Aksi
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.data.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell className="font-medium">
                                                {user.name}
                                            </TableCell>
                                            <TableCell>{user.email}</TableCell>
                                            <TableCell>
                                                {user.district
                                                    ? `${user.district.name} (${user.district.code})`
                                                    : '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={
                                                        user.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {user.is_active
                                                        ? 'Aktif'
                                                        : 'Nonaktif'}
                                                </Badge>
                                                {user.must_change_password ? (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        kata sandi sementara
                                                    </span>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="space-x-2 text-right whitespace-nowrap">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={`/admin/users/${user.id}/edit`}
                                                    >
                                                        Ubah
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant={
                                                        user.is_active
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                    size="sm"
                                                    onClick={() =>
                                                        router.patch(
                                                            `/admin/users/${user.id}/status`,
                                                            {},
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    {user.is_active
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'}
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}

                    {users.last_page > 1 ? (
                        <div className="flex flex-wrap gap-1">
                            {users.links.map((link, index) => (
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
