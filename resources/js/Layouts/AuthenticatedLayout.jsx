import { Link, usePage, router } from "@inertiajs/react";
import { useState } from "react";
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
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInset,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarProvider,
    SidebarTrigger,
} from "@/components/ui/sidebar";
import { cn } from "@/lib/utils";
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
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isActive}
                className={cn(
                    "h-10 rounded-xl px-3 text-sm font-medium transition-all duration-150",
                    "text-sidebar-foreground/75 hover:bg-white/[0.08] hover:text-sidebar-foreground",
                    "data-[active=true]:bg-white/[0.14] data-[active=true]:text-sidebar-foreground data-[active=true]:shadow-none"
                )}
            >
                <Link href={item.href}>
                    <Icon className="h-5 w-5 shrink-0" />
                    <span>{item.name}</span>
                    {isActive && (
                        <ChevronRight className="ml-auto h-4 w-4 text-sidebar-foreground/70" />
                    )}
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
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

            <SidebarProvider defaultOpen>
                <Sidebar className="shadow-lg" variant="sidebar">
                    <SidebarHeader className="border-b border-sidebar-border px-5 py-0">
                        <div className="flex h-16 items-center gap-3">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/12 backdrop-blur-sm">
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
                    </SidebarHeader>

                    <SidebarContent className="px-3 py-5">
                        <SidebarGroup className="p-0">
                            <SidebarGroupLabel className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/50">
                                Main Menu
                            </SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu className="gap-1">
                                    {filterNav(NAV_ITEMS).map((item) => (
                                        <NavLink key={item.name} item={item} isActive={isActive(item.href)} />
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>

                        <SidebarGroup className="mt-3 p-0">
                            <SidebarGroupLabel className="mb-1.5 px-3 text-[10px] font-semibold uppercase tracking-widest text-sidebar-foreground/50">
                                Management
                            </SidebarGroupLabel>
                            <SidebarGroupContent>
                                <SidebarMenu className="gap-1">
                                    {filterNav(MANAGEMENT_ITEMS).map((item) => (
                                        <NavLink key={item.name} item={item} isActive={isActive(item.href)} />
                                    ))}
                                </SidebarMenu>
                            </SidebarGroupContent>
                        </SidebarGroup>
                    </SidebarContent>

                    <SidebarFooter className="border-t border-sidebar-border p-3">
                        <div className="mb-2 flex items-center gap-3 rounded-lg px-3 py-2.5">
                            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 text-xs font-bold text-white">
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

                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    onClick={() => setShowLogoutDialog(true)}
                                    className="h-9 justify-start text-sidebar-foreground/70 hover:bg-white/[0.08] hover:text-sidebar-foreground"
                                >
                                    <LogOut className="h-4 w-4" />
                                    <span>Logout</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarFooter>
                </Sidebar>

                <SidebarInset className="min-h-screen">
                    <header className="sticky top-0 z-20 flex h-14 items-center border-b bg-background px-4 md:hidden">
                        <SidebarTrigger className="-ml-1" />
                        <span className="ml-2 text-sm font-semibold text-foreground">
                            School SRS
                        </span>
                    </header>
                    <main className="flex-1 p-8">
                        {children}
                    </main>
                </SidebarInset>
            </SidebarProvider>

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
