import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import {
    Card, CardContent, CardDescription,
    CardHeader, CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    AreaChart, Area, XAxis, YAxis,
    CartesianGrid, Tooltip, ResponsiveContainer,
} from "recharts";
import {
    Users, GraduationCap, BookOpen, DollarSign,
    TrendingUp, TrendingDown, Plus, UserPlus,
} from "lucide-react";

const CHART_DATA = [
    { month: "Jan", students: 420, revenue: 42000 },
    { month: "Feb", students: 445, revenue: 44500 },
    { month: "Mar", students: 480, revenue: 48000 },
    { month: "Apr", students: 510, revenue: 51000 },
    { month: "May", students: 545, revenue: 54500 },
    { month: "Jun", students: 580, revenue: 58000 },
];

function StatCard({ title, value, change, trend, description, subtitle, icon: Icon, colorClass, bgClass }) {
    const TrendIcon = trend === "up" ? TrendingUp : TrendingDown;
    const trendColor = trend === "up" ? "text-emerald-600" : "text-red-600";

    return (
        <Card className="hover:shadow-md transition-shadow duration-200">
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                    <div className={`flex h-10 w-10 items-center justify-center rounded-lg ${bgClass}`}>
                        <Icon className={`h-5 w-5 ${colorClass}`} />
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <p className="text-3xl font-bold text-foreground">{value}</p>
                <div className="mt-1 flex items-center justify-between">
                    <span className={`flex items-center gap-1 text-xs font-medium ${trendColor}`}>
                        <TrendIcon className="h-3 w-3" />
                        {change}
                    </span>
                    <span className="text-xs text-muted-foreground">{description}</span>
                </div>
                {subtitle && <p className="mt-1 text-xs text-muted-foreground">{subtitle}</p>}
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ auth, stats }) {
    const statCards = [
        {
            title: "Total Students",
            value: stats?.total_students ?? "0",
            change: stats?.student_growth ?? "+12.5%",
            trend: "up",
            description: "vs last month",
            subtitle: `${stats?.active_students ?? 0} active`,
            icon: Users,
            colorClass: "text-blue-600",
            bgClass: "bg-blue-50",
        },
        {
            title: "Total Teachers",
            value: stats?.total_teachers ?? "0",
            change: stats?.teacher_growth ?? "+8%",
            trend: "up",
            description: "faculty members",
            subtitle: "Staff count",
            icon: GraduationCap,
            colorClass: "text-purple-600",
            bgClass: "bg-purple-50",
        },
        {
            title: "Active Subjects",
            value: stats?.total_subjects ?? "0",
            change: stats?.subject_growth ?? "+5%",
            trend: "up",
            description: "course offerings",
            subtitle: "Available courses",
            icon: BookOpen,
            colorClass: "text-cyan-600",
            bgClass: "bg-cyan-50",
        },
        {
            title: "Monthly Revenue",
            value: `$${stats?.total_revenue ?? "0"}`,
            change: stats?.revenue_growth ?? "+15%",
            trend: "up",
            description: "this month",
            subtitle: `${stats?.pending_payments ?? 0} pending`,
            icon: DollarSign,
            colorClass: "text-emerald-600",
            bgClass: "bg-emerald-50",
        },
    ];

    const quickActions = [
        { label: "Add Student",  icon: UserPlus, href: "/students/create" },
        { label: "All Students", icon: Users,    href: "/students" },
        { label: "Classes",      icon: BookOpen, href: "/classes" },
        { label: "Payments",     icon: DollarSign, href: "/payments" },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            {/* Header */}
            <div className="mb-8">
                <h1 className="text-2xl font-semibold text-foreground">Dashboard</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Welcome back, <span className="font-medium">{auth?.user?.name ?? "Admin"}</span>
                </p>
            </div>

            {/* Stat cards */}
            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {statCards.map((card) => (
                    <StatCard key={card.title} {...card} />
                ))}
            </div>

            {/* Chart */}
            <Card className="mb-6">
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle className="text-base font-semibold">Enrollment Trends</CardTitle>
                            <CardDescription className="mt-0.5">
                                Student registrations and revenue — last 6 months
                            </CardDescription>
                        </div>
                        <Button variant="outline" size="sm">Last 6 months</Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={280}>
                        <AreaChart data={CHART_DATA} margin={{ top: 4, right: 4, left: 0, bottom: 0 }}>
                            <defs>
                                <linearGradient id="gStudents" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%"  stopColor="rgb(37,99,235)"   stopOpacity={0.25} />
                                    <stop offset="95%" stopColor="rgb(37,99,235)"   stopOpacity={0} />
                                </linearGradient>
                                <linearGradient id="gRevenue" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%"  stopColor="rgb(124,58,237)"  stopOpacity={0.25} />
                                    <stop offset="95%" stopColor="rgb(124,58,237)"  stopOpacity={0} />
                                </linearGradient>
                            </defs>
                            <CartesianGrid strokeDasharray="3 3" stroke="rgb(226,232,240)" />
                            <XAxis dataKey="month" stroke="rgb(100,116,139)" style={{ fontSize: 12 }} />
                            <YAxis stroke="rgb(100,116,139)" style={{ fontSize: 12 }} />
                            <Tooltip
                                contentStyle={{
                                    backgroundColor: "white",
                                    border: "1px solid rgb(226,232,240)",
                                    borderRadius: 8,
                                    boxShadow: "0 4px 6px -1px rgb(0 0 0/0.07)",
                                    fontSize: 12,
                                }}
                            />
                            <Area type="monotone" dataKey="students" stroke="rgb(37,99,235)"  fill="url(#gStudents)" strokeWidth={2} />
                            <Area type="monotone" dataKey="revenue"  stroke="rgb(124,58,237)" fill="url(#gRevenue)"  strokeWidth={2} />
                        </AreaChart>
                    </ResponsiveContainer>
                    <div className="mt-4 flex items-center justify-center gap-6 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1.5">
                            <span className="inline-block h-2.5 w-2.5 rounded-full bg-blue-600" />Students
                        </span>
                        <span className="flex items-center gap-1.5">
                            <span className="inline-block h-2.5 w-2.5 rounded-full bg-purple-600" />Revenue ($)
                        </span>
                    </div>
                </CardContent>
            </Card>

            {/* Quick Actions */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-base font-semibold">Quick Actions</CardTitle>
                    <CardDescription>Common tasks at a glance</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        {quickActions.map(({ label, icon: Icon, href }) => (
                            <Button
                                key={label}
                                variant="outline"
                                onClick={() => router.get(href)}
                                className="gap-2"
                            >
                                <Icon className="h-4 w-4" />
                                {label}
                            </Button>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
