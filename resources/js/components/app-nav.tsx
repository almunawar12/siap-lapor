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
    kabupatenOnly?: boolean;
};

const items: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Laporan (LHP)', href: '/reports', icon: FileText },
    { label: 'Notifikasi', href: '/notifikasi', icon: Bell },
    {
        label: 'Periode Pelaporan',
        href: '/admin/periods',
        icon: CalendarRange,
        kabupatenOnly: true,
    },
    {
        label: 'Akun Kecamatan',
        href: '/admin/users',
        icon: Users,
        kabupatenOnly: true,
    },
    {
        label: 'Data Kecamatan',
        href: '/admin/districts',
        icon: Building2,
        kabupatenOnly: true,
    },
    { label: 'Profil Saya', href: '/profil', icon: UserCog },
];

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
        <nav aria-label="Navigasi utama" className="grid gap-1 px-3 py-2">
            {visible.map((item) => {
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
                            'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            active
                                ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                                : 'text-muted-foreground hover:bg-sidebar-accent/60 hover:text-sidebar-accent-foreground',
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
        </nav>
    );
}
