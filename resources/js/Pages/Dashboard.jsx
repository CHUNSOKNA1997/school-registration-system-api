import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

export default function Dashboard({ stats }) {
    const statCards = [
        {
            title: 'Total Students',
            value: stats?.total_students || '0',
            change: '+12.5%',
            trend: 'up',
            description: 'Enrollment increasing',
            subtitle: `${stats?.active_students || 0} active students`
        },
        {
            title: 'Total Teachers',
            value: stats?.total_teachers || '0',
            change: '+8%',
            trend: 'up',
            description: 'Faculty growth',
            subtitle: 'Staff members'
        },
        {
            title: 'Total Subjects',
            value: stats?.total_subjects || '0',
            change: '+5%',
            trend: 'up',
            description: 'Course offerings',
            subtitle: 'Available courses'
        },
        {
            title: 'Total Revenue',
            value: `$${stats?.total_revenue || '0'}`,
            change: '+15%',
            trend: 'up',
            description: 'Revenue growth',
            subtitle: `${stats?.pending_payments || 0} pending payments`
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="p-8 pt-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-white">Dashboard</h1>
                    </div>
                    <Button variant="outline" size="sm" className="bg-white text-black hover:bg-white/90 border-0">
                        <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="1.5" />
                        </svg>
                        <span className="ml-2">Quick Actions</span>
                    </Button>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((stat, index) => (
                        <Card key={index} className="bg-[#1a1a1a] border-white/10">
                            <CardHeader className="pb-2">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-sm font-normal text-white/60">{stat.title}</CardTitle>
                                    <span className={`flex items-center text-xs font-medium ${stat.trend === 'up' ? 'text-green-500' : 'text-red-500'}`}>
                                        {stat.trend === 'up' ? (
                                            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                        ) : (
                                            <svg className="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                        )}
                                        {stat.change}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-1">
                                <div className="text-3xl font-semibold text-white">{stat.value}</div>
                                <div className="flex items-center text-sm space-y-0.5">
                                    <p className="text-white/60 flex items-center">
                                        {stat.description}
                                        <svg className="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                    </p>
                                </div>
                                <p className="text-xs text-white/40">{stat.subtitle}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Chart Section */}
                <Card className="bg-[#1a1a1a] border-white/10">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-lg font-semibold text-white">Student Enrollment Trends</CardTitle>
                                <CardDescription className="text-white/40 text-sm">Registration and payment trends over time</CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="ghost" size="sm" className="text-white/60 hover:text-white hover:bg-white/5 text-xs">
                                    Last 3 months
                                </Button>
                                <Button variant="ghost" size="sm" className="text-white/60 hover:text-white hover:bg-white/5 text-xs">
                                    Last 30 days
                                </Button>
                                <Button variant="ghost" size="sm" className="text-white/60 hover:text-white hover:bg-white/5 text-xs">
                                    Last 7 days
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="h-[300px] flex items-end justify-between gap-2 px-4">
                            {/* Simulated area chart with gradients */}
                            <svg className="w-full h-full" viewBox="0 0 1200 300" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="areaGradient1" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style={{stopColor: '#666', stopOpacity: 0.5}} />
                                        <stop offset="100%" style={{stopColor: '#666', stopOpacity: 0}} />
                                    </linearGradient>
                                    <linearGradient id="areaGradient2" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style={{stopColor: '#999', stopOpacity: 0.3}} />
                                        <stop offset="100%" style={{stopColor: '#999', stopOpacity: 0}} />
                                    </linearGradient>
                                    <linearGradient id="areaGradient3" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" style={{stopColor: '#444', stopOpacity: 0.4}} />
                                        <stop offset="100%" style={{stopColor: '#444', stopOpacity: 0}} />
                                    </linearGradient>
                                </defs>

                                {/* Background area 1 */}
                                <path d="M 0 200 Q 200 180 400 150 T 800 120 T 1200 100 L 1200 300 L 0 300 Z" fill="url(#areaGradient1)" />

                                {/* Middle area 2 */}
                                <path d="M 0 220 Q 200 200 400 180 T 800 160 T 1200 140 L 1200 300 L 0 300 Z" fill="url(#areaGradient2)" />

                                {/* Front area 3 */}
                                <path d="M 0 240 Q 200 230 400 210 T 800 190 T 1200 170 L 1200 300 L 0 300 Z" fill="url(#areaGradient3)" />

                                {/* Lines */}
                                <path d="M 0 200 Q 200 180 400 150 T 800 120 T 1200 100" stroke="#666" strokeWidth="1.5" fill="none" />
                                <path d="M 0 220 Q 200 200 400 180 T 800 160 T 1200 140" stroke="#999" strokeWidth="1.5" fill="none" />
                                <path d="M 0 240 Q 200 230 400 210 T 800 190 T 1200 170" stroke="#444" strokeWidth="1.5" fill="none" />
                            </svg>
                        </div>

                        {/* Date labels */}
                        <div className="flex justify-between px-4 mt-4 text-xs text-white/40">
                            <span>Jun 24</span>
                            <span>Jun 25</span>
                            <span>Jun 26</span>
                            <span>Jun 27</span>
                            <span>Jun 28</span>
                            <span>Jun 29</span>
                            <span>Jun 30</span>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs Section */}
                <div className="flex gap-2 border-b border-white/10">
                    <button className="px-4 py-2 text-sm font-medium text-white border-b-2 border-white">
                        Recent Activity
                    </button>
                    <button className="px-4 py-2 text-sm font-medium text-white/60 hover:text-white">
                        New Registrations
                        <Badge variant="secondary" className="ml-2 bg-white/10 text-white/60 text-xs">{stats?.active_students || 0}</Badge>
                    </button>
                    <button className="px-4 py-2 text-sm font-medium text-white/60 hover:text-white">
                        Pending Payments
                        <Badge variant="secondary" className="ml-2 bg-white/10 text-white/60 text-xs">{stats?.pending_payments || 0}</Badge>
                    </button>
                    <button className="px-4 py-2 text-sm font-medium text-white/60 hover:text-white">
                        Class Schedule
                    </button>
                    <div className="ml-auto flex gap-2">
                        <Button variant="outline" size="sm" className="bg-transparent border-white/20 text-white/60 hover:bg-white/5 hover:text-white text-xs">
                            Export Report
                        </Button>
                        <Button variant="outline" size="sm" className="bg-transparent border-white/20 text-white/60 hover:bg-white/5 hover:text-white text-xs">
                            + New Student
                        </Button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
