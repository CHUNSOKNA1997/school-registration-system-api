# Comprehensive Dashboard Implementation Prompt

## PROJECT CONTEXT

You are building a **fully functional, production-ready Dashboard** for a School Registration System built with:
- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: React 19 + Inertia.js (SSR framework)
- **Styling**: Tailwind CSS + Shadcn UI (New York style, dark theme)
- **Icons**: Lucide React
- **Database**: SQLite (dev) / PostgreSQL (production)
- **Authentication**: Laravel Sanctum (session-based)

---

## SYSTEM ARCHITECTURE

### Core Models & Relationships
```
User (Staff/Admin)
├── role: 'admin' | 'staff'
└── is_admin: boolean

Student
├── student_code (auto-generated, e.g., STU-2024-0001)
├── status: active | inactive | graduated | suspended
├── student_type: regular | scholarship | transfer
├── shift: morning | afternoon | evening
├── belongsTo: Classroom
├── hasMany: StudentSubject (enrollments)
└── hasMany: Payment

Teacher
├── teacher_code (auto-generated)
├── hasMany: TeacherSubject
└── hasMany: Subject (through TeacherSubject)

Subject
├── subject_code
├── credit_hours
├── hasMany: StudentSubject
└── hasMany: TeacherSubject

Classroom
├── name_en, name_km
├── grade_level (1-12)
├── academic_year (e.g., 2024-2025)
└── hasMany: Student

Payment
├── payment_code (auto-generated)
├── payment_type: tuition | registration | examination | uniform | transport | book | other
├── payment_method: cash | bank_transfer | khqr | card | payway
├── status: pending | paid | partial | overdue | cancelled
├── amount, paid_amount, balance
├── belongsTo: Student
└── timestamps (payment_date, due_date, paid_at)

StudentSubject (Enrollments)
├── student_id, subject_id, teacher_id
├── status: enrolled | completed | dropped | failed
├── grade (0-100)
├── academic_year, semester
└── timestamps
```

### Existing Routes
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Students routes already implemented
    Route::get('students', [StudentController::class, 'index']);
    Route::get('students/create', [StudentController::class, 'create']);
    Route::post('students', [StudentController::class, 'store']);
    Route::get('students/{student}', [StudentController::class, 'show']);
    
    Route::middleware('admin')->group(function () {
        Route::get('students/{student}/edit', [StudentController::class, 'edit']);
        Route::put('students/{student}', [StudentController::class, 'update']);
        Route::delete('students/{student}', [StudentController::class, 'destroy']);
    });
    
    // Enrollments
    Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments']);
    Route::post('students/{student}/enrollments', [StudentSubjectController::class, 'store']);
    Route::put('students/{student}/enrollments/{enrollment}', [StudentSubjectController::class, 'update']);
    Route::delete('students/{student}/enrollments/{enrollment}', [StudentSubjectController::class, 'destroy']);
});
```

### Existing Frontend Pages
- ✅ `resources/js/Pages/Login.jsx` - Authentication
- ✅ `resources/js/Pages/Dashboard.jsx` - Basic dashboard (needs enhancement)
- ✅ `resources/js/Pages/Students/Index.jsx` - Student list (modernized)
- ✅ `resources/js/Pages/Students/Create.jsx` - Add student form
- ✅ `resources/js/Pages/Students/Show.jsx` - Student details
- ✅ `resources/js/Pages/Students/Edit.jsx` - Edit student
- ✅ `resources/js/Pages/Students/Enrollments.jsx` - Enrollment management

### Existing Controllers
- ✅ `app/Http/Controllers/Web/DashboardController.php` - Returns basic stats
- ✅ `app/Http/Controllers/Web/StudentController.php` - Full CRUD
- ✅ `app/Http/Controllers/Web/StudentSubjectController.php` - Enrollment management

---

## DASHBOARD REQUIREMENTS

### 1. METRICS & STATISTICS (Top Section)

Create 4 primary stat cards with **real-time data**:

#### Card 1: Students Overview
- **Total Students**: `Student::count()`
- **Active Students**: `Student::where('status', 'active')->count()`
- **New This Month**: Students registered in current month
- **Status Breakdown**: Active/Inactive/Graduated/Suspended percentages
- **Trend**: Comparison with previous month (% change)
- **Icon**: Users icon with gradient background

#### Card 2: Financial Overview
- **Total Revenue**: `Payment::where('status', 'paid')->sum('paid_amount')`
- **Pending Payments**: `Payment::where('status', 'pending')->sum('amount')`
- **Overdue Payments**: `Payment::where('status', 'overdue')->count()`
- **Revenue This Month**: Payments received in current month
- **Trend**: Revenue growth vs previous month
- **Icon**: DollarSign icon with green gradient

#### Card 3: Academic Overview
- **Total Subjects**: `Subject::count()`
- **Total Teachers**: `Teacher::count()`
- **Total Classes**: `Classroom::count()`
- **Total Enrollments**: `StudentSubject::where('status', 'enrolled')->count()`
- **Average Grade**: `StudentSubject::whereNotNull('grade')->avg('grade')`
- **Icon**: GraduationCap icon with blue gradient

#### Card 4: Enrollment Status
- **Active Enrollments**: `StudentSubject::where('status', 'enrolled')->count()`
- **Completed**: `StudentSubject::where('status', 'completed')->count()`
- **Dropped**: `StudentSubject::where('status', 'dropped')->count()`
- **Completion Rate**: (Completed / Total) × 100
- **Icon**: BookOpen icon with purple gradient

### 2. CHARTS & VISUALIZATIONS (Middle Section)

#### Chart 1: Student Enrollment Trends (Line/Area Chart)
- **X-axis**: Last 12 months
- **Y-axis**: Number of students enrolled
- **Data**: Monthly enrollment counts grouped by status
- **Lines**:
  - Total enrollments (blue)
  - Active students (green)
  - New registrations (purple)
- **Interaction**: Hover to see exact numbers
- **Time filters**: Last 7 days, 30 days, 3 months, 6 months, 1 year

#### Chart 2: Revenue Analytics (Bar Chart)
- **X-axis**: Last 12 months
- **Y-axis**: Payment amounts
- **Bars**:
  - Total revenue (green gradient)
  - Pending payments (yellow gradient)
  - Overdue (red gradient)
- **Data**: `Payment::selectRaw('MONTH(payment_date) as month, SUM(amount) as total')`
- **Show**: Revenue by payment type breakdown

#### Chart 3: Student Status Distribution (Donut Chart)
- **Segments**:
  - Active (green) - percentage
  - Inactive (gray) - percentage
  - Graduated (blue) - percentage
  - Suspended (red) - percentage
- **Center**: Total student count
- **Legend**: Show counts and percentages

#### Chart 4: Grade Distribution (Histogram)
- **X-axis**: Grade ranges (0-49, 50-59, 60-69, 70-79, 80-89, 90-100)
- **Y-axis**: Number of students
- **Colors**: Red to green gradient
- **Data**: `StudentSubject::whereNotNull('grade')->get()`
- **Show**: Average grade line overlay

### 3. QUICK ACTIONS PANEL (Top Right)
Create a dropdown with common actions:
- **Add New Student** → `/students/create`
- **Add Payment** → `/payments/create` (future)
- **View Reports** → `/reports` (future)
- **Manage Classes** → `/classrooms` (future)
- **View Teachers** → `/teachers` (future)
- **Export Data** → Trigger export dialog

### 4. RECENT ACTIVITY FEED (Left Column)
Show last 10 activities:
- New student registrations
- Recent payments received
- New enrollments
- Grade updates
- Status changes

**Format**:
```
[Icon] [Description] [Time ago]
👤 John Doe registered as STU-2024-0042  |  2 hours ago
💰 Payment received from Jane Smith ($500)  |  3 hours ago
📚 Math enrollment added for John Doe  |  5 hours ago
```

**Data Source**:
- Query latest records from Students, Payments, StudentSubjects
- Order by `created_at DESC`
- Limit 10

### 5. UPCOMING TASKS / ALERTS (Right Column)

#### Overdue Payments Alert
- Count of students with overdue payments
- Total overdue amount
- List top 5 students with largest overdue
- **Action Button**: "View All Overdue"

#### Pending Enrollments Alert
- Students without any subject enrollments
- Count and list
- **Action Button**: "Enroll Students"

#### Incomplete Student Profiles
- Students missing critical fields (phone, email, parent info)
- Count and list
- **Action Button**: "Complete Profiles"

### 6. CLASS PERFORMANCE TABLE (Bottom Section)
Show performance by classroom:

| Class | Total Students | Avg Grade | Enrollments | Status |
|-------|---------------|-----------|-------------|--------|
| Grade 10A | 35 | 78.5 | 245 | 🟢 Active |
| Grade 11B | 28 | 82.3 | 196 | 🟢 Active |
| Grade 12C | 30 | 75.2 | 210 | 🟡 Some issues |

**Data Query**:
```php
Classroom::withCount('students')
    ->with(['students' => function($q) {
        $q->withAvg('enrollments as avg_grade', 'grade');
    }])
    ->get();
```

### 7. TOP PERFORMERS SECTION
Show top 10 students by average grade:
- Student name + photo
- Student code
- Average grade (color-coded)
- Class
- Total subjects enrolled

### 8. PAYMENT STATUS OVERVIEW
Mini cards showing:
- **Paid**: Green gradient
- **Partial**: Yellow gradient
- **Pending**: Orange gradient
- **Overdue**: Red gradient

Each with count and total amount.

---

## TECHNICAL IMPLEMENTATION

### Step 1: Backend Controller Enhancement

**File**: `app/Http/Controllers/Web/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\StudentSubject;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $previousMonth = Carbon::now()->subMonth()->month;
        
        // 1. Student Statistics
        $totalStudents = Student::count();
        $activeStudents = Student::where('status', 'active')->count();
        $newStudentsThisMonth = Student::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        $newStudentsPrevMonth = Student::whereMonth('created_at', $previousMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        
        $studentGrowth = $newStudentsPrevMonth > 0 
            ? (($newStudentsThisMonth - $newStudentsPrevMonth) / $newStudentsPrevMonth) * 100
            : 0;
        
        $studentsByStatus = Student::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // 2. Financial Statistics
        $totalRevenue = Payment::where('status', 'paid')->sum('paid_amount');
        $revenueThisMonth = Payment::where('status', 'paid')
            ->whereMonth('paid_at', $currentMonth)
            ->whereYear('paid_at', $currentYear)
            ->sum('paid_amount');
        $revenuePrevMonth = Payment::where('status', 'paid')
            ->whereMonth('paid_at', $previousMonth)
            ->whereYear('paid_at', $currentYear)
            ->sum('paid_amount');
        
        $revenueGrowth = $revenuePrevMonth > 0
            ? (($revenueThisMonth - $revenuePrevMonth) / $revenuePrevMonth) * 100
            : 0;
        
        $pendingPayments = Payment::where('status', 'pending')->sum('amount');
        $overduePayments = Payment::where('status', 'overdue')->count();
        $overdueAmount = Payment::where('status', 'overdue')->sum('amount');
        
        // 3. Academic Statistics
        $totalSubjects = Subject::count();
        $totalTeachers = Teacher::count();
        $totalClasses = Classroom::count();
        $activeEnrollments = StudentSubject::where('status', 'enrolled')->count();
        $completedEnrollments = StudentSubject::where('status', 'completed')->count();
        $averageGrade = StudentSubject::whereNotNull('grade')->avg('grade') ?? 0;
        
        // 4. Enrollment Statistics
        $enrollmentsByStatus = StudentSubject::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $completionRate = ($enrollmentsByStatus['enrolled'] ?? 0) + ($enrollmentsByStatus['completed'] ?? 0) > 0
            ? (($enrollmentsByStatus['completed'] ?? 0) / 
               (($enrollmentsByStatus['enrolled'] ?? 0) + ($enrollmentsByStatus['completed'] ?? 0))) * 100
            : 0;
        
        // 5. Monthly Enrollment Trends (Last 12 months)
        $enrollmentTrends = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return [
                'month' => $date->format('M Y'),
                'total' => StudentSubject::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'active' => Student::where('status', 'active')
                    ->whereYear('created_at', '<=', $date->year)
                    ->whereMonth('created_at', '<=', $date->month)
                    ->count(),
                'new' => Student::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        });
        
        // 6. Revenue Trends (Last 12 months)
        $revenueTrends = collect(range(11, 0))->map(function ($monthsAgo) {
            $date = Carbon::now()->subMonths($monthsAgo);
            return [
                'month' => $date->format('M Y'),
                'revenue' => Payment::where('status', 'paid')
                    ->whereYear('paid_at', $date->year)
                    ->whereMonth('paid_at', $date->month)
                    ->sum('paid_amount'),
                'pending' => Payment::where('status', 'pending')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount'),
                'overdue' => Payment::where('status', 'overdue')
                    ->whereYear('due_date', $date->year)
                    ->whereMonth('due_date', $date->month)
                    ->sum('amount'),
            ];
        });
        
        // 7. Grade Distribution
        $gradeDistribution = [
            '0-49' => StudentSubject::whereBetween('grade', [0, 49])->count(),
            '50-59' => StudentSubject::whereBetween('grade', [50, 59])->count(),
            '60-69' => StudentSubject::whereBetween('grade', [60, 69])->count(),
            '70-79' => StudentSubject::whereBetween('grade', [70, 79])->count(),
            '80-89' => StudentSubject::whereBetween('grade', [80, 89])->count(),
            '90-100' => StudentSubject::whereBetween('grade', [90, 100])->count(),
        ];
        
        // 8. Recent Activity
        $recentStudents = Student::with('class')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($s) => [
                'type' => 'student_registered',
                'description' => "{$s->first_name} {$s->last_name} registered as {$s->student_code}",
                'time' => $s->created_at->diffForHumans(),
                'icon' => 'user',
            ]);
        
        $recentPayments = Payment::with('student')
            ->where('status', 'paid')
            ->latest('paid_at')
            ->take(5)
            ->get()
            ->map(fn($p) => [
                'type' => 'payment_received',
                'description' => "Payment received from {$p->student->first_name} {$p->student->last_name} (\${$p->paid_amount})",
                'time' => $p->paid_at->diffForHumans(),
                'icon' => 'dollar',
            ]);
        
        $recentEnrollments = StudentSubject::with(['student', 'subject'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($e) => [
                'type' => 'enrollment_added',
                'description' => "{$e->subject->name_en} enrollment added for {$e->student->first_name} {$e->student->last_name}",
                'time' => $e->created_at->diffForHumans(),
                'icon' => 'book',
            ]);
        
        $recentActivity = $recentStudents
            ->concat($recentPayments)
            ->concat($recentEnrollments)
            ->sortByDesc('time')
            ->take(10)
            ->values();
        
        // 9. Alerts & Tasks
        $overduePaymentStudents = Payment::with('student')
            ->where('status', 'overdue')
            ->orderBy('amount', 'desc')
            ->take(5)
            ->get();
        
        $studentsWithoutEnrollments = Student::where('status', 'active')
            ->doesntHave('enrollments')
            ->take(5)
            ->get();
        
        $incompleteProfiles = Student::where(function($q) {
                $q->whereNull('phone')
                  ->orWhereNull('email')
                  ->orWhereNull('parent_phone');
            })
            ->where('status', 'active')
            ->take(5)
            ->get();
        
        // 10. Class Performance
        $classPerformance = Classroom::withCount('students')
            ->with(['students' => function($q) {
                $q->with(['enrollments' => function($q2) {
                    $q2->whereNotNull('grade');
                }]);
            }])
            ->get()
            ->map(function($class) {
                $allGrades = $class->students->flatMap(fn($s) => $s->enrollments->pluck('grade'));
                $avgGrade = $allGrades->avg() ?? 0;
                $totalEnrollments = $class->students->sum(fn($s) => $s->enrollments->count());
                
                return [
                    'id' => $class->id,
                    'name' => $class->name_en,
                    'students_count' => $class->students_count,
                    'avg_grade' => round($avgGrade, 1),
                    'enrollments' => $totalEnrollments,
                    'status' => $avgGrade >= 75 ? 'good' : ($avgGrade >= 60 ? 'fair' : 'needs_improvement'),
                ];
            });
        
        // 11. Top Performers
        $topPerformers = Student::with('class')
            ->whereHas('enrollments', function($q) {
                $q->whereNotNull('grade');
            })
            ->get()
            ->map(function($student) {
                $avgGrade = $student->enrollments->avg('grade');
                return [
                    'id' => $student->id,
                    'name' => "{$student->first_name} {$student->last_name}",
                    'student_code' => $student->student_code,
                    'class' => $student->class->name_en ?? 'N/A',
                    'avg_grade' => round($avgGrade, 1),
                    'total_subjects' => $student->enrollments->count(),
                    'photo' => $student->photo,
                ];
            })
            ->sortByDesc('avg_grade')
            ->take(10)
            ->values();
        
        // 12. Payment Status Overview
        $paymentStatusOverview = [
            'paid' => [
                'count' => Payment::where('status', 'paid')->count(),
                'amount' => Payment::where('status', 'paid')->sum('paid_amount'),
            ],
            'partial' => [
                'count' => Payment::where('status', 'partial')->count(),
                'amount' => Payment::where('status', 'partial')->sum('paid_amount'),
            ],
            'pending' => [
                'count' => Payment::where('status', 'pending')->count(),
                'amount' => Payment::where('status', 'pending')->sum('amount'),
            ],
            'overdue' => [
                'count' => Payment::where('status', 'overdue')->count(),
                'amount' => Payment::where('status', 'overdue')->sum('amount'),
            ],
        ];
        
        return Inertia::render('Dashboard', [
            'stats' => [
                // Student Stats
                'total_students' => $totalStudents,
                'active_students' => $activeStudents,
                'new_students_this_month' => $newStudentsThisMonth,
                'student_growth' => round($studentGrowth, 1),
                'students_by_status' => $studentsByStatus,
                
                // Financial Stats
                'total_revenue' => $totalRevenue,
                'revenue_this_month' => $revenueThisMonth,
                'revenue_growth' => round($revenueGrowth, 1),
                'pending_payments' => $pendingPayments,
                'overdue_payments_count' => $overduePayments,
                'overdue_amount' => $overdueAmount,
                
                // Academic Stats
                'total_subjects' => $totalSubjects,
                'total_teachers' => $totalTeachers,
                'total_classes' => $totalClasses,
                'active_enrollments' => $activeEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'average_grade' => round($averageGrade, 1),
                
                // Enrollment Stats
                'enrollments_by_status' => $enrollmentsByStatus,
                'completion_rate' => round($completionRate, 1),
            ],
            'charts' => [
                'enrollment_trends' => $enrollmentTrends,
                'revenue_trends' => $revenueTrends,
                'grade_distribution' => $gradeDistribution,
            ],
            'recent_activity' => $recentActivity,
            'alerts' => [
                'overdue_payments' => $overduePaymentStudents,
                'students_without_enrollments' => $studentsWithoutEnrollments,
                'incomplete_profiles' => $incompleteProfiles,
            ],
            'class_performance' => $classPerformance,
            'top_performers' => $topPerformers,
            'payment_status_overview' => $paymentStatusOverview,
        ]);
    }
}
```

---

### Step 2: Frontend Dashboard Component

**File**: `resources/js/Pages/Dashboard.jsx`

**Requirements**:
1. **Import necessary components**:
   - Shadcn: Card, Button, Badge, Tabs
   - Lucide icons: Users, DollarSign, GraduationCap, BookOpen, TrendingUp, TrendingDown, etc.
   - Recharts library for charts (install: `npm install recharts`)

2. **Layout Structure**:
```jsx
<AuthenticatedLayout>
  <Head title="Dashboard" />
  
  <div className="min-h-screen bg-gradient-to-br from-[#0a0a0a] via-[#111111] to-[#0a0a0a]">
    <div className="p-8 space-y-8">
      {/* Header with Quick Actions */}
      <Header />
      
      {/* Stats Grid (4 cards) */}
      <StatsGrid stats={stats} />
      
      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <EnrollmentTrendsChart data={charts.enrollment_trends} />
        <RevenueAnalyticsChart data={charts.revenue_trends} />
        <StudentStatusDistribution data={stats.students_by_status} />
        <GradeDistributionChart data={charts.grade_distribution} />
      </div>
      
      {/* Activity & Alerts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <RecentActivityFeed activities={recent_activity} />
        </div>
        <div>
          <AlertsPanel alerts={alerts} />
        </div>
      </div>
      
      {/* Class Performance Table */}
      <ClassPerformanceTable classes={class_performance} />
      
      {/* Top Performers & Payment Status */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <TopPerformers students={top_performers} />
        <PaymentStatusOverview data={payment_status_overview} />
      </div>
    </div>
  </div>
</AuthenticatedLayout>
```

3. **Design System** (use exact same modern style as Students Index):
   - Glass-morphism cards: `bg-white/5 border-white/10 backdrop-blur-xl`
   - Gradient headers: `bg-gradient-to-r from-white/5`
   - Hover effects: `hover:bg-white/[0.07] transition-all duration-300`
   - Gradient buttons: `bg-gradient-to-r from-blue-600 to-purple-600`
   - Status badges with gradients
   - Smooth animations

4. **Chart Library Setup**:
```jsx
import {
  LineChart, Line, AreaChart, Area, BarChart, Bar,
  PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid,
  Tooltip, Legend, ResponsiveContainer
} from 'recharts';
```

5. **Component Breakdown**:

```jsx
// Stats Card Component
const StatCard = ({ title, value, change, trend, subtitle, icon: Icon, gradient }) => (
  <Card className="bg-white/5 border-white/10 backdrop-blur-xl hover:bg-white/[0.07] transition-all duration-300">
    <CardContent className="p-6">
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <p className="text-sm text-white/60">{title}</p>
          <div className="flex items-baseline gap-2">
            <h3 className="text-3xl font-bold text-white">{value}</h3>
            <Badge className={`${trend === 'up' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}`}>
              {trend === 'up' ? <TrendingUp className="w-3 h-3" /> : <TrendingDown className="w-3 h-3" />}
              {change}%
            </Badge>
          </div>
          <p className="text-xs text-white/40">{subtitle}</p>
        </div>
        <div className={`p-3 rounded-xl ${gradient} backdrop-blur-sm`}>
          <Icon className="w-6 h-6" />
        </div>
      </div>
    </CardContent>
  </Card>
);

// Enrollment Trends Chart
const EnrollmentTrendsChart = ({ data }) => (
  <Card className="bg-white/5 border-white/10 backdrop-blur-xl">
    <CardHeader>
      <CardTitle className="text-white flex items-center gap-2">
        <TrendingUp className="w-5 h-5 text-blue-400" />
        Enrollment Trends
      </CardTitle>
    </CardHeader>
    <CardContent>
      <ResponsiveContainer width="100%" height={300}>
        <AreaChart data={data}>
          <defs>
            <linearGradient id="colorTotal" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3}/>
              <stop offset="95%" stopColor="#3b82f6" stopOpacity={0}/>
            </linearGradient>
            <linearGradient id="colorActive" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#10b981" stopOpacity={0.3}/>
              <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
            </linearGradient>
          </defs>
          <CartesianGrid strokeDasharray="3 3" stroke="rgba(255,255,255,0.1)" />
          <XAxis dataKey="month" stroke="rgba(255,255,255,0.4)" />
          <YAxis stroke="rgba(255,255,255,0.4)" />
          <Tooltip 
            contentStyle={{ 
              backgroundColor: 'rgba(26, 26, 26, 0.95)', 
              border: '1px solid rgba(255,255,255,0.1)',
              borderRadius: '8px'
            }}
          />
          <Legend />
          <Area type="monotone" dataKey="total" stroke="#3b82f6" fillOpacity={1} fill="url(#colorTotal)" />
          <Area type="monotone" dataKey="active" stroke="#10b981" fillOpacity={1} fill="url(#colorActive)" />
          <Area type="monotone" dataKey="new" stroke="#8b5cf6" fillOpacity={1} fill="url(#colorNew)" />
        </AreaChart>
      </ResponsiveContainer>
    </CardContent>
  </Card>
);

// Recent Activity Feed
const RecentActivityFeed = ({ activities }) => (
  <Card className="bg-white/5 border-white/10 backdrop-blur-xl">
    <CardHeader>
      <CardTitle className="text-white">Recent Activity</CardTitle>
    </CardHeader>
    <CardContent>
      <div className="space-y-4">
        {activities.map((activity, index) => (
          <div key={index} className="flex items-start gap-3 p-3 rounded-lg hover:bg-white/5 transition-all">
            <div className="p-2 rounded-lg bg-gradient-to-br from-blue-500/20 to-purple-500/20">
              {getActivityIcon(activity.icon)}
            </div>
            <div className="flex-1">
              <p className="text-sm text-white">{activity.description}</p>
              <p className="text-xs text-white/40 mt-1">{activity.time}</p>
            </div>
          </div>
        ))}
      </div>
    </CardContent>
  </Card>
);

// Alerts Panel
const AlertsPanel = ({ alerts }) => (
  <div className="space-y-4">
    {/* Overdue Payments Alert */}
    <Card className="bg-gradient-to-br from-red-500/10 to-rose-500/10 border-red-500/20 backdrop-blur-xl">
      <CardHeader>
        <CardTitle className="text-red-400 text-sm flex items-center gap-2">
          <AlertTriangle className="w-4 h-4" />
          Overdue Payments
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-2">
          <p className="text-2xl font-bold text-white">{alerts.overdue_payments.length}</p>
          <p className="text-xs text-white/60">Students with overdue payments</p>
          <Button className="w-full mt-4 bg-red-500/20 hover:bg-red-500/30 text-red-300">
            View All Overdue
          </Button>
        </div>
      </CardContent>
    </Card>
    
    {/* Similar cards for other alerts */}
  </div>
);

// Class Performance Table
const ClassPerformanceTable = ({ classes }) => (
  <Card className="bg-white/5 border-white/10 backdrop-blur-xl">
    <CardHeader>
      <CardTitle className="text-white">Class Performance</CardTitle>
    </CardHeader>
    <CardContent>
      <Table>
        <TableHeader>
          <TableRow className="border-white/10">
            <TableHead className="text-white/70">Class</TableHead>
            <TableHead className="text-white/70">Students</TableHead>
            <TableHead className="text-white/70">Avg Grade</TableHead>
            <TableHead className="text-white/70">Enrollments</TableHead>
            <TableHead className="text-white/70">Status</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {classes.map((cls) => (
            <TableRow key={cls.id} className="border-white/10 hover:bg-white/5">
              <TableCell className="font-medium text-white">{cls.name}</TableCell>
              <TableCell className="text-white/80">{cls.students_count}</TableCell>
              <TableCell>
                <Badge className={getGradeBadgeClass(cls.avg_grade)}>
                  {cls.avg_grade}
                </Badge>
              </TableCell>
              <TableCell className="text-white/80">{cls.enrollments}</TableCell>
              <TableCell>
                <Badge className={getStatusBadgeClass(cls.status)}>
                  {cls.status}
                </Badge>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </CardContent>
  </Card>
);
```

---

### Step 3: Install Dependencies

```bash
# Install Recharts for charts
npm install recharts

# Ensure Shadcn components are installed
npx shadcn@latest add tabs
npx shadcn@latest add tooltip
npx shadcn@latest add separator
npx shadcn@latest add progress
```

---

### Step 4: Testing & Validation

1. **Test Backend**:
```bash
php artisan tinker
>>> app(App\Http\Controllers\Web\DashboardController::class)->index()
```

2. **Build Frontend**:
```bash
npm run build
```

3. **Manual Testing Checklist**:
   - [ ] All 4 stat cards display correct data
   - [ ] Charts render without errors
   - [ ] Recent activity feed shows latest records
   - [ ] Alerts panel displays warnings
   - [ ] Class performance table loads
   - [ ] Top performers list shows correct rankings
   - [ ] Payment status cards show accurate totals
   - [ ] Responsive design works on mobile/tablet/desktop
   - [ ] Hover effects and animations are smooth
   - [ ] No console errors

---

## DESIGN SPECIFICATIONS

### Color Palette
```css
--background-dark: #0a0a0a
--background-darker: #111111
--card-glass: rgba(255, 255, 255, 0.05)
--border-subtle: rgba(255, 255, 255, 0.1)
--text-primary: #ffffff
--text-secondary: rgba(255, 255, 255, 0.6)
--text-tertiary: rgba(255, 255, 255, 0.4)
```

### Gradients
```css
--gradient-primary: linear-gradient(to right, #3b82f6, #8b5cf6)
--gradient-success: linear-gradient(to right, #10b981, #059669)
--gradient-warning: linear-gradient(to right, #f59e0b, #d97706)
--gradient-danger: linear-gradient(to right, #ef4444, #dc2626)
```

### Typography
```css
--font-size-xs: 0.75rem
--font-size-sm: 0.875rem
--font-size-base: 1rem
--font-size-lg: 1.125rem
--font-size-xl: 1.25rem
--font-size-2xl: 1.5rem
--font-size-3xl: 1.875rem
```

### Spacing
```css
--spacing-sm: 0.5rem
--spacing-md: 1rem
--spacing-lg: 1.5rem
--spacing-xl: 2rem
--spacing-2xl: 2.5rem
```

---

## EXPECTED OUTPUT

After implementation, the dashboard should:
1. **Load in < 2 seconds** with all data
2. **Display real-time metrics** from database
3. **Render all 4 charts** with smooth animations
4. **Show recent activity** with live updates
5. **Highlight critical alerts** with color coding
6. **Be fully responsive** across devices
7. **Match the modern design** of Students Index page
8. **Have zero console errors** or warnings
9. **Support role-based visibility** (admin vs staff)
10. **Allow quick navigation** to related pages

---

## ADDITIONAL FEATURES (OPTIONAL ENHANCEMENTS)

1. **Real-time Updates**: Use Laravel Echo + Pusher for live data
2. **Export Reports**: PDF/CSV export for stats
3. **Date Range Filters**: Custom date ranges for charts
4. **Comparison Mode**: Compare current vs previous periods
5. **Dark/Light Mode Toggle**: User preference
6. **Widget Customization**: Drag-and-drop dashboard widgets
7. **Notifications Center**: Dropdown for all alerts
8. **Search Bar**: Global search across entities
9. **Favorites/Shortcuts**: Pin frequently used actions
10. **Keyboard Shortcuts**: Power user navigation

---

## SUCCESS CRITERIA

- [ ] All statistics display accurate data from database
- [ ] All 4 charts render correctly with real data
- [ ] Recent activity feed shows last 10 activities
- [ ] Alerts panel highlights critical issues
- [ ] Class performance table is sortable
- [ ] Top performers list is accurate
- [ ] Payment status cards show correct totals
- [ ] Design matches modern Students Index style
- [ ] Responsive on mobile, tablet, desktop
- [ ] No errors in browser console or Laravel logs
- [ ] Page loads in under 2 seconds
- [ ] All links navigate correctly
- [ ] Hover effects and animations work smoothly

---

## DELIVERABLES

1. **Updated `app/Http/Controllers/Web/DashboardController.php`** with all data queries
2. **Fully redesigned `resources/js/Pages/Dashboard.jsx`** with modern UI
3. **No new routes required** (uses existing `/dashboard`)
4. **Documentation** of all metrics and calculations
5. **Test results** showing all features working

---

## TIMELINE (For Planning)

- **Backend Controller**: Implement all data queries and calculations
- **Frontend Components**: Build stat cards, charts, tables, alerts
- **Chart Integration**: Install Recharts and implement all visualizations
- **Styling**: Apply glass-morphism and gradient design system
- **Testing**: Verify all data is accurate and UI is responsive
- **Polish**: Fine-tune animations, hover effects, loading states

---

This prompt provides complete context for building a production-ready, fully functional dashboard that matches your School Registration System's architecture and design language.
