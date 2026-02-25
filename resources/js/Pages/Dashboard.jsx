import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import { useState } from "react";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    AreaChart,
    Area,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from "recharts";
import {
    Users,
    GraduationCap,
    BookOpen,
    DollarSign,
    TrendingUp,
    TrendingDown,
} from "lucide-react";

export default function Dashboard({ auth, stats }) {
    const [activeTab, setActiveTab] = useState("activity");

    // Sample enrollment data - replace with real data from backend
    const enrollmentData = [
        { month: "Jan", students: 420, revenue: 42000 },
        { month: "Feb", students: 445, revenue: 44500 },
        { month: "Mar", students: 480, revenue: 48000 },
        { month: "Apr", students: 510, revenue: 51000 },
        { month: "May", students: 545, revenue: 54500 },
        { month: "Jun", students: 580, revenue: 58000 },
    ];

    const statCards = [
        {
            title: "Total Students",
            value: stats?.total_students || "0",
            change: stats?.student_growth || "+12.5%",
            trend: "up",
            description: "From last month",
            subtitle: `${stats?.active_students || 0} active`,
            icon: Users,
            color: "text-blue-600",
            bgColor: "bg-blue-50",
        },
        {
            title: "Total Teachers",
            value: stats?.total_teachers || "0",
            change: stats?.teacher_growth || "+8%",
            trend: "up",
            description: "Faculty members",
            subtitle: "Staff count",
            icon: GraduationCap,
            color: "text-purple-600",
            bgColor: "bg-purple-50",
        },
        {
            title: "Active Subjects",
            value: stats?.total_subjects || "0",
            change: stats?.subject_growth || "+5%",
            trend: "up",
            description: "Course offerings",
            subtitle: "Available courses",
            icon: BookOpen,
            color: "text-cyan-600",
            bgColor: "bg-cyan-50",
        },
        {
            title: "Monthly Revenue",
            value: `$${stats?.total_revenue || "0"}`,
            change: stats?.revenue_growth || "+15%",
            trend: "up",
            description: "This month",
            subtitle: `${stats?.pending_payments || 0} pending`,
            icon: DollarSign,
            color: "text-green-600",
            bgColor: "bg-green-50",
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />

            <div className="p-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg
                                className="w-6 h-6 text-primary"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-foreground">
                                Dashboard
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                Welcome back, {auth?.user?.name || "Admin"}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm">
                            <svg
                                className="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Export Report
                        </Button>
                        <Button
                            size="sm"
                            className="bg-primary text-primary-foreground hover:bg-primary/90"
                        >
                            <svg
                                className="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            New Student
                        </Button>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((stat, index) => {
                        const Icon = stat.icon;
                        const TrendIcon =
                            stat.trend === "up" ? TrendingUp : TrendingDown;

                        return (
                            <Card
                                key={index}
                                className="hover:shadow-md transition-shadow"
                            >
                                <CardHeader className="pb-2">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-sm font-medium text-muted-foreground">
                                            {stat.title}
                                        </CardTitle>
                                        <div
                                            className={`w-10 h-10 rounded-lg ${stat.bgColor} flex items-center justify-center`}
                                        >
                                            <Icon
                                                className={`w-5 h-5 ${stat.color}`}
                                            />
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-1">
                                    <div className="text-3xl font-bold text-foreground">
                                        {stat.value}
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span
                                            className={`flex items-center text-xs font-medium ${stat.trend === "up" ? "text-green-600" : "text-red-600"}`}
                                        >
                                            <TrendIcon className="w-3 h-3 mr-1" />
                                            {stat.change}
                                        </span>
                                        <p className="text-xs text-muted-foreground">
                                            {stat.description}
                                        </p>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        {stat.subtitle}
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                {/* Chart Section */}
                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle className="text-lg font-semibold">
                                    Student Enrollment Trends
                                </CardTitle>
                                <CardDescription>
                                    Registration and revenue over the last 6
                                    months
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button variant="outline" size="sm">
                                    Last 6 months
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <ResponsiveContainer width="100%" height={300}>
                            <AreaChart data={enrollmentData}>
                                <defs>
                                    <linearGradient
                                        id="colorStudents"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor="rgb(37, 99, 235)"
                                            stopOpacity={0.3}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor="rgb(37, 99, 235)"
                                            stopOpacity={0}
                                        />
                                    </linearGradient>
                                    <linearGradient
                                        id="colorRevenue"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="5%"
                                            stopColor="rgb(124, 58, 237)"
                                            stopOpacity={0.3}
                                        />
                                        <stop
                                            offset="95%"
                                            stopColor="rgb(124, 58, 237)"
                                            stopOpacity={0}
                                        />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid
                                    strokeDasharray="3 3"
                                    stroke="rgb(226, 232, 240)"
                                />
                                <XAxis
                                    dataKey="month"
                                    stroke="rgb(100, 116, 139)"
                                    style={{ fontSize: "12px" }}
                                />
                                <YAxis
                                    stroke="rgb(100, 116, 139)"
                                    style={{ fontSize: "12px" }}
                                />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: "white",
                                        border: "1px solid rgb(226, 232, 240)",
                                        borderRadius: "8px",
                                        boxShadow:
                                            "0 4px 6px -1px rgb(0 0 0 / 0.1)",
                                    }}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="students"
                                    stroke="rgb(37, 99, 235)"
                                    fillOpacity={1}
                                    fill="url(#colorStudents)"
                                    strokeWidth={2}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="revenue"
                                    stroke="rgb(124, 58, 237)"
                                    fillOpacity={1}
                                    fill="url(#colorRevenue)"
                                    strokeWidth={2}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                        <div className="flex items-center justify-center gap-6 mt-4">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded-full bg-blue-600"></div>
                                <span className="text-sm text-muted-foreground">
                                    Students
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 rounded-full bg-purple-600"></div>
                                <span className="text-sm text-muted-foreground">
                                    Revenue ($)
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Tabs Section */}
                <Card>
                    <div className="border-b border-border">
                        <div className="flex items-center justify-between px-6 pt-6">
                            <div className="flex gap-1">
                                <button
                                    onClick={() => setActiveTab("activity")}
                                    className={`px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ${
                                        activeTab === "activity"
                                            ? "bg-primary text-primary-foreground"
                                            : "text-muted-foreground hover:text-foreground hover:bg-muted"
                                    }`}
                                >
                                    Recent Activity
                                </button>
                                <button
                                    onClick={() =>
                                        setActiveTab("registrations")
                                    }
                                    className={`px-4 py-2 text-sm font-medium rounded-t-lg transition-colors flex items-center gap-2 ${
                                        activeTab === "registrations"
                                            ? "bg-primary text-primary-foreground"
                                            : "text-muted-foreground hover:text-foreground hover:bg-muted"
                                    }`}
                                >
                                    New Registrations
                                    <Badge
                                        variant="secondary"
                                        className="text-xs"
                                    >
                                        {stats?.active_students || 0}
                                    </Badge>
                                </button>
                                <button
                                    onClick={() => setActiveTab("payments")}
                                    className={`px-4 py-2 text-sm font-medium rounded-t-lg transition-colors flex items-center gap-2 ${
                                        activeTab === "payments"
                                            ? "bg-primary text-primary-foreground"
                                            : "text-muted-foreground hover:text-foreground hover:bg-muted"
                                    }`}
                                >
                                    Pending Payments
                                    <Badge
                                        variant="secondary"
                                        className="text-xs"
                                    >
                                        {stats?.pending_payments || 0}
                                    </Badge>
                                </button>
                            </div>
                        </div>
                    </div>
                    <CardContent className="p-6">
                        {activeTab === "activity" && (
                            <div className="text-center py-12 text-muted-foreground">
                                <p>Recent activity will be displayed here</p>
                            </div>
                        )}
                        {activeTab === "registrations" && (
                            <div className="text-center py-12 text-muted-foreground">
                                <p>
                                    New student registrations will be displayed
                                    here
                                </p>
                            </div>
                        )}
                        {activeTab === "payments" && (
                            <div className="text-center py-12 text-muted-foreground">
                                <p>
                                    Pending payment records will be displayed
                                    here
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
