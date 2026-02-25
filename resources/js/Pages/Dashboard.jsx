import * as React from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import {
    Card, CardContent, CardDescription,
    CardHeader, CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart";
import {
    AreaChart, Area, XAxis, CartesianGrid,
} from "recharts";
import {
    Users, GraduationCap, BookOpen, DollarSign,
    TrendingUp, TrendingDown,
} from "lucide-react";

const CHART_DATA = [
    { date: "2024-01-01", students: 420, revenue: 42000 },
    { date: "2024-02-01", students: 445, revenue: 44500 },
    { date: "2024-03-01", students: 480, revenue: 48000 },
    { date: "2024-04-01", students: 510, revenue: 51000 },
    { date: "2024-05-01", students: 545, revenue: 54500 },
    { date: "2024-06-01", students: 580, revenue: 58000 },
];

const chartConfig = {
    students: {
        label: "Students",
        color: "var(--chart-1)",
    },
    revenue: {
        label: "Revenue ($)",
        color: "var(--chart-5)",
    },
};

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
    const [timeRange, setTimeRange] = React.useState("6m");

    const filteredData = React.useMemo(() => {
        if (timeRange === "3m") return CHART_DATA.slice(-3);
        if (timeRange === "1m") return CHART_DATA.slice(-1);
        return CHART_DATA;
    }, [timeRange]);

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
            colorClass: "text-emerald-600",
            bgClass: "bg-emerald-50",
        },
        {
            title: "Active Subjects",
            value: stats?.total_subjects ?? "0",
            change: stats?.subject_growth ?? "+5%",
            trend: "up",
            description: "course offerings",
            subtitle: "Available courses",
            icon: BookOpen,
            colorClass: "text-amber-600",
            bgClass: "bg-amber-50",
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
            <Card className="mb-6 pt-0">
                <CardHeader className="flex items-center gap-2 space-y-0 border-b py-5 sm:flex-row">
                    <div className="grid flex-1 gap-1">
                        <CardTitle className="text-base font-semibold">Enrollment Trends</CardTitle>
                        <CardDescription>
                            Student registrations and revenue over time
                        </CardDescription>
                    </div>

                    <Select value={timeRange} onValueChange={setTimeRange}>
                        <SelectTrigger
                            className="hidden w-[170px] rounded-lg sm:ml-auto sm:flex"
                            aria-label="Select time range"
                        >
                            <SelectValue placeholder="Last 6 months" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl">
                            <SelectItem value="6m" className="rounded-lg">Last 6 months</SelectItem>
                            <SelectItem value="3m" className="rounded-lg">Last 3 months</SelectItem>
                            <SelectItem value="1m" className="rounded-lg">Last month</SelectItem>
                        </SelectContent>
                    </Select>
                </CardHeader>

                <CardContent className="px-2 pt-4 sm:px-6 sm:pt-6">
                    <ChartContainer config={chartConfig} className="aspect-auto h-[280px] w-full">
                        <AreaChart data={filteredData}>
                            <defs>
                                <linearGradient id="fillStudents" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="var(--color-students)" stopOpacity={0.7} />
                                    <stop offset="95%" stopColor="var(--color-students)" stopOpacity={0.08} />
                                </linearGradient>
                                <linearGradient id="fillRevenue" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="5%" stopColor="var(--color-revenue)" stopOpacity={0.7} />
                                    <stop offset="95%" stopColor="var(--color-revenue)" stopOpacity={0.08} />
                                </linearGradient>
                            </defs>

                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="date"
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
                                minTickGap={32}
                                tickFormatter={(value) =>
                                    new Date(value).toLocaleDateString("en-US", {
                                        month: "short",
                                        year: "2-digit",
                                    })
                                }
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        indicator="dot"
                                        labelFormatter={(value) =>
                                            new Date(value).toLocaleDateString("en-US", {
                                                month: "short",
                                                year: "numeric",
                                            })
                                        }
                                        formatter={(value, name) =>
                                            name === "revenue"
                                                ? `$${Number(value).toLocaleString()}`
                                                : Number(value).toLocaleString()
                                        }
                                    />
                                }
                            />
                            <Area
                                dataKey="students"
                                type="natural"
                                fill="url(#fillStudents)"
                                stroke="var(--color-students)"
                                strokeWidth={2}
                            />
                            <Area
                                dataKey="revenue"
                                type="natural"
                                fill="url(#fillRevenue)"
                                stroke="var(--color-revenue)"
                                strokeWidth={2}
                            />
                            <ChartLegend content={<ChartLegendContent />} />
                        </AreaChart>
                    </ChartContainer>
                </CardContent>
            </Card>

        </AuthenticatedLayout>
    );
}
