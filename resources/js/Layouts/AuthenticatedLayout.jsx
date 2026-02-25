import { Link, usePage, router } from "@inertiajs/react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Toaster } from "@/components/ui/sonner";
import { Separator } from "@/components/ui/separator";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    LayoutDashboard,
    Users,
    GraduationCap,
    BookOpen,
    School,
    DollarSign,
    FileText,
    UsersRound,
    Settings,
    LogOut,
    ChevronRight,
} from "lucide-react";

const NAV_ITEMS = [
    { name: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
    { name: "Students",  href: "/students",  icon: Users },
    { name: "Teachers",  href: "/teachers",  icon: GraduationCap, adminOnly: true },
    { name: "Subjects",  href: "/subjects",  icon: BookOpen,       adminOnly: true },
    { name: "Classes",   href: "/classes",   icon: School },
];

const MANAGEMENT_ITEMS = [
    { name: "Payments", href: "/payments", icon: DollarSign },
    { name: "Reports",  href: "/reports",  icon: FileText,   adminOnly: true },
    { name: "Users",    href: "/users",    icon: UsersRound, adminOnly: true },
    { name: "Settings", href: "/settings", icon: Settings },
];

function NavLink({ item, isActive }) {
    const Icon = item.icon;
    return (
        <Link
            href={item.href}
            className={`group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150 ${
                isActive
                    ? "bg-white text-primary shadow-sm"
                    : "text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground"
            }`}
        >
            <Icon className="h-4 w-4 shrink-0" />
            <span>{item.name}</span>
            {isActive && (
                <ChevronRight className="ml-auto h-3 w-3 text-primary/50" />
            )}
        </Link>
    );
}

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props;
    const currentPath = window.location.pathname;
    const [showLogoutDialog, setShowLogoutDialog] = useState(false);

    const isActive = (href) =>
        currentPath === href || currentPath.startsWith(href + "/");

    const filterNav = (items) =>
        items.filter((item) => !item.adminOnly || auth.user?.is_admin);

    const initials = (auth.user?.name ?? "?")
        .split(" ")
        .map((w) => w[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();

    return (
        <>
            <Toaster position="top-right" richColors />

            <div className="flex min-h-screen bg-background">
                {/* ── Sidebar ── */}
                <aside className="fixed left-0 top-0 z-40 flex h-screen w-64 flex-col bg-sidebar shadow-lg">
                    {/* Logo */}
                    <div className="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border px-5">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 backdrop-blur-sm">
                            <School className="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <p className="text-sm font-bold leading-none text-sidebar-foreground">
                                School SRS
                            </p>
                            <p className="mt-0.5 text-[11px] text-sidebar-foreground/60">
                                Registration System
                            </p>
                        </div>
                    </div>

                    {/* Nav */}
                    <nav className="flex-1 overflow-y-auto px-3 py-5 space-y-5">
                        <div>
                            <p className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/50">
                                Main Menu
                            </p>
                            <ul className="space-y-0.5">
                                {filterNav(NAV_ITEMS).map((item) => (
                                    <li key={item.name}>
                                        <NavLink item={item} isActive={isActive(item.href)} />
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div>
                            <p className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/50">
                                Management
                            </p>
                            <ul className="space-y-0.5">
                                {filterNav(MANAGEMENT_ITEMS).map((item) => (
                                    <li key={item.name}>
                                        <NavLink item={item} isActive={isActive(item.href)} />
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </nav>

                    {/* User footer */}
                    <div className="shrink-0 border-t border-sidebar-border p-3">
                        <div className="mb-2 flex items-center gap-3 rounded-lg px-3 py-2.5">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold text-white">
                                {initials}
                            </div>
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium text-sidebar-foreground">
                                    {auth.user?.name ?? "Admin"}
                                </p>
                                <p className="text-[11px] text-sidebar-foreground/60">
                                    {auth.user?.is_admin ? "Administrator" : "Staff"}
                                </p>
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => setShowLogoutDialog(true)}
                            className="w-full justify-start text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground"
                        >
                            <LogOut className="mr-2 h-4 w-4" />
                            Logout
                        </Button>
                    </div>
                </aside>

                {/* ── Main Content ── */}
                <div className="flex flex-1 flex-col pl-64">
                    <main className="flex-1 p-8">{children}</main>
                </div>
            </div>

            {/* Logout confirm */}
            <AlertDialog open={showLogoutDialog} onOpenChange={setShowLogoutDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Logout</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to logout? You will need to sign in again to access your account.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => router.post("/logout")}
                            className="bg-destructive text-white hover:bg-destructive/90"
                        >
                            Logout
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
