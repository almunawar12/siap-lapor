import { AppNav } from '@/components/app-nav';
import { Brand } from '@/components/brand';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { Toaster } from '@/components/ui/sonner';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bell, LogOut, Menu, UserCog } from 'lucide-react';
import {
    useEffect,
    useState,
    type PropsWithChildren,
    type ReactNode,
} from 'react';
import { toast } from 'sonner';

type AppLayoutProps = PropsWithChildren<{
    title: string;
    description?: string;
    actions?: ReactNode;
}>;

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

export function AppLayout({
    title,
    description,
    actions,
    children,
}: AppLayoutProps) {
    const { app, auth, flash, unread_notifications } = usePage().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const user = auth.user;

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash.success, flash.error]);

    const institution = app.institution_name
        ? app.regency_name
            ? `${app.institution_name} · ${app.regency_name}`
            : app.institution_name
        : null;

    return (
        <div className="min-h-svh bg-background">
            <Head title={title} />

            <aside className="hidden border-r bg-sidebar md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
                <div className="px-4 py-5">
                    <Brand name={app.name} tagline={app.tagline} />
                </div>
                <Separator />
                <div className="flex-1 overflow-y-auto">
                    <AppNav />
                </div>
                {institution ? (
                    <p className="px-4 py-3 text-xs text-muted-foreground">
                        {institution}
                    </p>
                ) : null}
            </aside>

            <div className="md:pl-64">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b bg-background/95 px-4 backdrop-blur sm:px-6">
                    <Sheet open={mobileOpen} onOpenChange={setMobileOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="md:hidden"
                                aria-label="Buka menu navigasi"
                            >
                                <Menu className="size-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="w-72 p-0">
                            <SheetTitle className="sr-only">
                                Navigasi {app.name}
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                {app.tagline}
                            </SheetDescription>
                            <div className="px-4 py-5">
                                <Brand name={app.name} tagline={app.tagline} />
                            </div>
                            <Separator />
                            <AppNav onNavigate={() => setMobileOpen(false)} />
                        </SheetContent>
                    </Sheet>

                    <div className="min-w-0 flex-1">
                        <h1 className="truncate text-base font-semibold sm:text-lg">
                            {title}
                        </h1>
                        {description ? (
                            <p className="truncate text-xs text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>

                    {user ? (
                        <Button
                            asChild
                            variant="ghost"
                            size="icon"
                            className="relative"
                            aria-label={
                                unread_notifications > 0
                                    ? `Notifikasi, ${unread_notifications} belum dibaca`
                                    : 'Notifikasi'
                            }
                        >
                            <Link href="/notifikasi">
                                <Bell className="size-5" />
                                {unread_notifications > 0 ? (
                                    <span className="absolute top-1 right-1 flex size-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground">
                                        {unread_notifications > 9
                                            ? '9+'
                                            : unread_notifications}
                                    </span>
                                ) : null}
                            </Link>
                        </Button>
                    ) : null}

                    {user ? (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="ghost"
                                    className="h-auto gap-2 px-2 py-1.5"
                                    aria-label="Menu akun"
                                >
                                    <Avatar className="size-8">
                                        <AvatarFallback className="text-xs">
                                            {initials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="hidden text-left sm:block">
                                        <span className="block max-w-40 truncate text-sm font-medium">
                                            {user.name}
                                        </span>
                                        <span className="block text-xs text-muted-foreground">
                                            {user.role_label}
                                        </span>
                                    </span>
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-60">
                                <DropdownMenuLabel className="font-normal">
                                    <span className="block text-sm font-medium">
                                        {user.name}
                                    </span>
                                    <span className="block truncate text-xs text-muted-foreground">
                                        {user.email}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {user.district
                                            ? `${user.role_label} · ${user.district.name}`
                                            : user.role_label}
                                    </span>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/profil">
                                        <UserCog className="size-4" />
                                        Profil Saya
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem
                                    onSelect={() => router.post('/logout')}
                                >
                                    <LogOut className="size-4" />
                                    Keluar
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    ) : null}
                </header>

                <main className="mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6">
                    {actions ? (
                        <div className="flex flex-wrap items-center gap-2">
                            {actions}
                        </div>
                    ) : null}
                    {children}
                </main>
            </div>

            <Toaster position="top-right" richColors />
        </div>
    );
}
