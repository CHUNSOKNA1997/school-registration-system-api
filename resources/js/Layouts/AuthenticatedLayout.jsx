import { Link, usePage, router } from "@inertiajs/react";
import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Toaster } from "@/components/ui/sonner";
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
} from "lucide-react";

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props;
    const currentPath = window.location.pathname;
    const [showLogoutDialog, setShowLogoutDialog] = useState(false);

    const navigation = [
        { name: "Dashboard", href: "/dashboard", icon: LayoutDashboard },
        { name: "Students", href: "/students", icon: Users },
        {
            name: "Teachers",
            href: "/teachers",
            icon: GraduationCap,
            adminOnly: true,
        },
        {
            name: "Subjects",
            href: "/subjects",
            icon: BookOpen,
            adminOnly: true,
        },
        { name: "Classes", href: "/classes", icon: School },
    ];

    const management = [
        { name: "Payments", href: "/payments", icon: DollarSign },
        { name: "Reports", href: "/reports", icon: FileText, adminOnly: true },
        { name: "Users", href: "/users", icon: UsersRound, adminOnly: true },
        { name: "Settings", href: "/settings", icon: Settings },
    ];

    const handleLogout = () => {
        router.post("/logout");
    };

    return (
        <>
            <Toaster position="top-right" />
            <div className="min-h-screen bg-background">
                {/* Sidebar */}
                <aside className="fixed left-0 top-0 z-40 h-screen w-[280px] bg-sidebar shadow-lg">
                    <div className="flex h-full flex-col">
                        {/* Logo */}
                        <div className="flex h-16 items-center gap-3 border-b border-sidebar-border px-6">
                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-white shadow-sm">
                                <School className="h-6 w-6 text-primary" />
                            </div>
                            <div className="flex flex-col">
                                <span className="text-base font-bold text-sidebar-foreground">
                                    School SRS
                                </span>
                                <span className="text-xs text-sidebar-foreground/60">
                                    Registration System
                                </span>
                            </div>
                        </div>

                        {/* Navigation */}
                        <div className="flex-1 overflow-y-auto px-4 py-6">
                            {/* Main Menu */}
                            <div className="mb-6">
                                <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/60">
                                    Main Menu
                                </p>
                                <nav className="space-y-1">
                                    {navigation.map((item) => {
                                        const isActive =
                                            currentPath === item.href;
                                        const Icon = item.icon;

                                        // Skip admin-only items for non-admin users
                                        if (
                                            item.adminOnly &&
                                            !auth.user?.is_admin
                                        ) {
                                            return null;
                                        }

                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all ${
                                                    isActive
                                                        ? "bg-white text-primary shadow-sm"
                                                        : "text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground"
                                                }`}
                                            >
                                                <Icon className="h-5 w-5" />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </nav>
                            </div>

                            {/* Management */}
                            <div>
                                <p className="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/60">
                                    Management
                                </p>
                                <nav className="space-y-1">
                                    {management.map((item) => {
                                        const isActive =
                                            currentPath === item.href;
                                        const Icon = item.icon;

                                        // Skip admin-only items for non-admin users
                                        if (
                                            item.adminOnly &&
                                            !auth.user?.is_admin
                                        ) {
                                            return null;
                                        }

                                        return (
                                            <Link
                                                key={item.name}
                                                href={item.href}
                                                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all ${
                                                    isActive
                                                        ? "bg-white text-primary shadow-sm"
                                                        : "text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-foreground"
                                                }`}
                                            >
                                                <Icon className="h-5 w-5" />
                                                <span>{item.name}</span>
                                            </Link>
                                        );
                                    })}
                                </nav>
                            </div>
                        </div>

                        {/* User Profile & Logout */}
                        <div className="border-t border-sidebar-border p-4">
                            <div className="mb-3 flex items-center gap-3 rounded-lg bg-sidebar-accent/30 p-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-white text-primary font-semibold shadow-sm">
                                    {auth.user?.name?.charAt(0) || "A"}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-medium text-sidebar-foreground truncate">
                                        {auth.user?.name || "Admin"}
                                    </p>
                                    <p className="text-xs text-sidebar-foreground/60">
                                        {auth.user?.is_admin
                                            ? "Administrator"
                                            : "Staff"}
                                    </p>
                                </div>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShowLogoutDialog(true)}
                                className="w-full justify-start text-sidebar-foreground/80 hover:text-sidebar-foreground hover:bg-sidebar-accent"
                            >
                                <LogOut className="w-4 h-4 mr-2" />
                                <span className="text-sm">Logout</span>
                            </Button>
                        </div>
                    </div>
                </aside>

                {/* Main Content */}
                <div className="pl-[280px]">
                    <main className="min-h-screen">{children}</main>
                </div>
            </div>

            {/* Logout Confirmation Dialog */}
            <AlertDialog
                open={showLogoutDialog}
                onOpenChange={setShowLogoutDialog}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Logout</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to logout? You will need to
                            sign in again to access your account.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleLogout}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Logout
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
