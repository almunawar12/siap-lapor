import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Building2,
    CalendarRange,
    FileText,
    LayoutDashboard,
    UserCog,
    Users,
} from 'lucide-react';
import type { ComponentType } from 'react';

type NavItem = {
    label: string;
    href: string;
    icon: ComponentType<{ className?: string }>;
    group: 'Pelaporan' | 'Administrasi' | 'Akun';
    kabupatenOnly?: boolean;
};

const items: NavItem[] = [
    {
        label: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
        group: 'Pelaporan',
    },
    {
        label: 'Laporan (LHP)',
        href: '/reports',
        icon: FileText,
        group: 'Pelaporan',
    },
    {
        label: 'Notifikasi',
        href: '/notifikasi',
        icon: Bell,
        group: 'Pelaporan',
    },
    {
        label: 'Periode Pelaporan',
        href: '/admin/periods',
        icon: CalendarRange,
        group: 'Administrasi',
        kabupatenOnly: true,
    },
    {
        label: 'Akun Kecamatan',
        href: '/admin/users',
        icon: Users,
        group: 'Administrasi',
        kabupatenOnly: true,
    },
    {
        label: 'Data Kecamatan',
        href: '/admin/districts',
        icon: Building2,
        group: 'Administrasi',
        kabupatenOnly: true,
    },
    {
        label: 'Profil Saya',
        href: '/profil',
        icon: UserCog,
        group: 'Akun',
    },
];

const groups: NavItem['group'][] = ['Pelaporan', 'Administrasi', 'Akun'];

/**
 * Menu disaring sesuai role hanya demi kenyamanan. Kontrol akses sebenarnya
 * ada pada policy di server.
 */
export function AppNav({ onNavigate }: { onNavigate?: () => void }) {
    const page = usePage();
    const user = page.props.auth.user;
    const current = page.url;

    const visible = items.filter(
        (item) => !item.kabupatenOnly || user?.is_kabupaten,
    );

    return (
        <nav aria-label="Navigasi utama" className="space-y-5 px-3 py-4">
            {groups.map((group) => {
                const groupItems = visible.filter(
                    (item) => item.group === group,
                );

                if (groupItems.length === 0) {
                    return null;
                }

                return (
                    <div key={group} className="space-y-1">
                        <p className="px-3 pb-1 text-xs font-semibold text-sidebar-foreground/55">
                            {group}
                        </p>
                        {groupItems.map((item) => {
                            const active =
                                current === item.href ||
                                current.startsWith(`${item.href}/`);

                            return (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={onNavigate}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                                        'focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none',
                                        active
                                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                            : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/70 hover:text-sidebar-accent-foreground',
                                    )}
                                >
                                    <item.icon
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {item.label}
                                </Link>
                            );
                        })}
                    </div>
                );
            })}
        </nav>
    );
}
