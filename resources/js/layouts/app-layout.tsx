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
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Bell, LogOut, Menu, UserCog, X } from 'lucide-react';
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
        <div className="min-h-dvh bg-background">
            <Head title={title} />

            <a
                href="#main-content"
                className="fixed top-3 left-3 z-50 -translate-y-20 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-transform focus:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                Lewati ke konten utama
            </a>

            <aside className="hidden border-r border-sidebar-border bg-sidebar md:fixed md:inset-y-0 md:flex md:w-72 md:flex-col">
                <div className="px-5 py-5">
                    <Brand name={app.name} tagline={app.tagline} inverse />
                </div>
                <Separator />
                <div className="flex-1 overflow-y-auto">
                    <AppNav />
                </div>
                {institution ? (
                    <p className="border-t border-sidebar-border px-5 py-4 text-xs leading-relaxed text-sidebar-foreground/65">
                        {institution}
                    </p>
                ) : null}
            </aside>

            <div className="md:pl-72">
                <header className="sticky top-0 z-30 flex h-16 items-center gap-3 border-b bg-card/95 px-4 backdrop-blur-sm sm:px-6 lg:px-8">
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
                        <SheetContent
                            side="left"
                            showCloseButton={false}
                            className="h-dvh w-[min(20rem,calc(100vw-3rem))] gap-0 overflow-hidden border-sidebar-border bg-sidebar p-0 text-sidebar-foreground sm:max-w-80"
                        >
                            <SheetTitle className="sr-only">
                                Navigasi {app.name}
                            </SheetTitle>
                            <SheetDescription className="sr-only">
                                {app.tagline}
                            </SheetDescription>
                            <div className="relative min-h-20 border-b border-sidebar-border px-5 py-5 pr-16">
                                <Brand
                                    name={app.name}
                                    tagline={app.tagline}
                                    inverse
                                />
                                <SheetClose asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="absolute top-3 right-3 text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                        aria-label="Tutup menu navigasi"
                                    >
                                        <X className="size-5" />
                                    </Button>
                                </SheetClose>
                            </div>
                            <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                                <AppNav
                                    onNavigate={() => setMobileOpen(false)}
                                />
                            </div>
                            <div className="border-t border-sidebar-border px-5 py-4 text-xs leading-relaxed text-sidebar-foreground/70">
                                <p className="truncate font-medium text-sidebar-foreground">
                                    {user?.name}
                                </p>
                                <p className="truncate">
                                    {user?.district?.name ?? user?.role_label}
                                </p>
                            </div>
                        </SheetContent>
                    </Sheet>

                    <div className="min-w-0 flex-1 md:hidden">
                        <p className="truncate text-sm font-semibold">
                            {title}
                        </p>
                    </div>

                    <p className="hidden min-w-0 flex-1 truncate text-sm text-muted-foreground md:block">
                        {user?.district?.name ?? user?.role_label}
                    </p>

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

                <main
                    id="main-content"
                    className="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8"
                >
                    <div className="mb-6 flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div className="min-w-0 space-y-1">
                            <h1 className="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">
                                {title}
                            </h1>
                            {description ? (
                                <p className="max-w-3xl text-sm leading-relaxed text-muted-foreground sm:text-base">
                                    {description}
                                </p>
                            ) : null}
                        </div>
                        {actions ? (
                            <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end max-sm:[&_[data-slot=button]]:flex-1">
                                {actions}
                            </div>
                        ) : null}
                    </div>

                    <div className="space-y-6">{children}</div>
                </main>
            </div>

            <Toaster position="top-right" richColors />
        </div>
    );
}
