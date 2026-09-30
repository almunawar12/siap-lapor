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
    const unread = page.props.unread_notifications;

    const visible = items.filter(
        (item) => !item.kabupatenOnly || user?.is_kabupaten,
    );

    return (
        <nav aria-label="Navigasi utama" className="space-y-6 px-3 py-5">
            {groups.map((group) => {
                const groupItems = visible.filter(
                    (item) => item.group === group,
                );

                if (groupItems.length === 0) {
                    return null;
                }

                return (
                    <div key={group} className="space-y-1">
                        <p className="px-3 pb-1.5 text-xs font-medium text-sidebar-foreground/50">
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
                                        'group relative flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                                        'focus-visible:ring-2 focus-visible:ring-sidebar-ring focus-visible:outline-none',
                                        active
                                            ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                            : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
                                    )}
                                >
                                    {active ? (
                                        <span
                                            className="absolute inset-y-2 left-0 w-1 rounded-r-full bg-sidebar-primary"
                                            aria-hidden="true"
                                        />
                                    ) : null}
                                    <item.icon
                                        className={cn(
                                            'size-4 shrink-0',
                                            active
                                                ? 'text-sidebar-primary'
                                                : 'text-sidebar-foreground/60 group-hover:text-sidebar-accent-foreground',
                                        )}
                                        aria-hidden="true"
                                    />
                                    <span className="flex-1 truncate">
                                        {item.label}
                                    </span>
                                    {item.href === '/notifikasi' &&
                                    unread > 0 ? (
                                        <span className="min-w-5 rounded-full bg-sidebar-primary px-1.5 text-center text-xs leading-5 font-semibold text-sidebar-primary-foreground tabular-nums">
                                            <span className="sr-only">
                                                belum dibaca:{' '}
                                            </span>
                                            {unread > 99 ? '99+' : unread}
                                        </span>
                                    ) : null}
                                </Link>
                            );
                        })}
                    </div>
                );
            })}
        </nav>
    );
}
