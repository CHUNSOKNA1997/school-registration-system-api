import { Link, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export default function AuthenticatedLayout({ children }) {
    const { auth } = usePage().props;
    const currentPath = window.location.pathname;

    const navigation = [
        { name: 'Dashboard', href: '/dashboard' },
        { name: 'Students', href: '/students' },
        { name: 'Teachers', href: '/teachers', adminOnly: true },
        { name: 'Subjects', href: '/subjects', adminOnly: true },
        { name: 'Classes', href: '/classes' },
    ];

    const management = [
        { name: 'Payments', href: '/payments' },
        { name: 'Reports', href: '/reports', adminOnly: true },
        { name: 'Users', href: '/users', adminOnly: true },
        { name: 'Settings', href: '/settings' },
    ];

    return (
        <div className="min-h-screen bg-[#0a0a0a] text-white">
            {/* Sidebar */}
            <aside className="fixed inset-y-0 left-0 z-50 w-[280px] bg-[#111111] border-r border-white/10">
                <div className="flex flex-col h-full">
                    {/* Logo */}
                    <div className="flex items-center h-16 px-6 border-b border-white/10">
                        <Link href="/dashboard" className="flex items-center space-x-2 group">
                            <div className="w-6 h-6 rounded-full border-2 border-white/30"></div>
                            <span className="text-base font-medium text-white">School SRS</span>
                        </Link>
                    </div>

                    {/* Navigation */}
                    <div className="flex-1 overflow-y-auto">
                        <div className="px-3 py-4">
                            <p className="px-3 text-xs font-medium text-white/40 uppercase tracking-wider mb-2">Main</p>
                            <nav className="space-y-0.5">
                                {navigation.map((item) => {
                                    if (item.adminOnly && !auth.user?.is_admin) return null;
                                    const isActive = currentPath === item.href;

                                    return (
                                        <Link
                                            key={item.name}
                                            href={item.href}
                                            className={`flex items-center px-3 py-2 text-sm font-normal rounded-lg transition-colors ${
                                                isActive
                                                    ? 'bg-white/10 text-white'
                                                    : 'text-white/60 hover:bg-white/5 hover:text-white/80'
                                            }`}
                                        >
                                            <span>{item.name}</span>
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>

                        <div className="px-3 py-4">
                            <p className="px-3 text-xs font-medium text-white/40 uppercase tracking-wider mb-2">Management</p>
                            <nav className="space-y-0.5">
                                {management.map((item) => {
                                    if (item.adminOnly && !auth.user?.is_admin) return null;
                                    const isActive = currentPath === item.href;

                                    return (
                                        <Link
                                            key={item.name}
                                            href={item.href}
                                            className={`flex items-center px-3 py-2 text-sm font-normal rounded-lg transition-colors ${
                                                isActive
                                                    ? 'bg-white/10 text-white'
                                                    : 'text-white/60 hover:bg-white/5 hover:text-white/80'
                                            }`}
                                        >
                                            <span>{item.name}</span>
                                        </Link>
                                    );
                                })}
                            </nav>
                        </div>
                    </div>

                    {/* User Info */}
                    <div className="p-4 border-t border-white/10">
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="w-full"
                        >
                            <Button variant="ghost" size="sm" className="w-full justify-start text-white/60 hover:text-white hover:bg-white/5">
                                <span className="text-sm">Logout</span>
                            </Button>
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Main Content */}
            <div className="pl-[280px]">
                <main className="min-h-screen bg-[#0a0a0a]">
                    {children}
                </main>
            </div>
        </div>
    );
}
