<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function index(Request $request)
    {
        $currentYear = $request->get('academic_year', date('Y') . '-' . (date('Y') + 1));

        $stats = [
            'students' => $this->getStudentStats($currentYear),
            'teachers' => $this->getTeacherStats(),
            'subjects' => $this->getSubjectStats(),
            'classrooms' => $this->getClassroomStats($currentYear),
            'payments' => $this->getPaymentStats($currentYear),
            'enrollments' => $this->getEnrollmentStats($currentYear),
            'recent_registrations' => $this->getRecentRegistrations(),
        ];

        return response()->jsonSuccess($stats);
    }

    /**
     * Get student statistics
     */
    protected function getStudentStats($academicYear)
    {
        return [
            'total' => Student::count(),
            'active' => Student::where('status', 'active')->count(),
            'inactive' => Student::where('status', 'inactive')->count(),
            'suspended' => Student::where('status', 'suspended')->count(),
            'graduated' => Student::where('status', 'graduated')->count(),
            'transferred' => Student::where('status', 'transferred')->count(),
            'current_year' => Student::where('academic_year', $academicYear)->count(),
            'by_shift' => Student::select('shift', DB::raw('count(*) as count'))
                ->where('status', 'active')
                ->groupBy('shift')
                ->pluck('count', 'shift'),
            'by_gender' => Student::select('gender', DB::raw('count(*) as count'))
                ->where('status', 'active')
                ->groupBy('gender')
                ->pluck('count', 'gender'),
            'by_type' => Student::select('student_type', DB::raw('count(*) as count'))
                ->where('status', 'active')
                ->groupBy('student_type')
                ->pluck('count', 'student_type'),
        ];
    }

    /**
     * Get teacher statistics
     */
    protected function getTeacherStats()
    {
        return [
            'total' => Teacher::count(),
            'active' => Teacher::where('is_active', true)->count(),
            'inactive' => Teacher::where('is_active', false)->count(),
            'by_gender' => Teacher::select('gender', DB::raw('count(*) as count'))
                ->where('is_active', true)
                ->groupBy('gender')
                ->pluck('count', 'gender'),
            'by_employment_type' => Teacher::select('employment_type', DB::raw('count(*) as count'))
                ->where('is_active', true)
                ->groupBy('employment_type')
                ->pluck('count', 'employment_type'),
        ];
    }

    /**
     * Get subject statistics
     */
    protected function getSubjectStats()
    {
        return [
            'total' => Subject::count(),
            'active' => Subject::where('is_active', true)->count(),
            'by_grade' => Subject::select('grade_level', DB::raw('count(*) as count'))
                ->where('is_active', true)
                ->groupBy('grade_level')
                ->orderBy('grade_level')
                ->pluck('count', 'grade_level'),
        ];
    }

    /**
     * Get classroom statistics
     */
    protected function getClassroomStats($academicYear)
    {
        return [
            'total' => Classroom::where('academic_year', $academicYear)->count(),
            'active' => Classroom::where('academic_year', $academicYear)
                ->where('is_active', true)
                ->count(),
            'total_capacity' => Classroom::where('academic_year', $academicYear)
                ->sum('capacity'),
            'total_enrollment' => Classroom::where('academic_year', $academicYear)
                ->sum('current_enrollment'),
            'by_grade' => Classroom::select('grade_level', DB::raw('count(*) as count'))
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->groupBy('grade_level')
                ->orderBy('grade_level')
                ->pluck('count', 'grade_level'),
        ];
    }

    /**
     * Get payment statistics
     */
    protected function getPaymentStats($academicYear)
    {
        $payments = Payment::where('academic_year', $academicYear);

        return [
            'total_count' => (clone $payments)->count(),
            'total_amount' => (clone $payments)->sum('amount'),
            'paid_amount' => (clone $payments)->sum('paid_amount'),
            'balance' => (clone $payments)->sum('balance'),
            'by_status' => Payment::select('status', DB::raw('count(*) as count'))
                ->where('academic_year', $academicYear)
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_type' => Payment::select('payment_type', DB::raw('count(*) as count'))
                ->where('academic_year', $academicYear)
                ->groupBy('payment_type')
                ->pluck('count', 'payment_type'),
            'by_method' => Payment::select('payment_method', DB::raw('count(*) as count'))
                ->where('academic_year', $academicYear)
                ->groupBy('payment_method')
                ->pluck('count', 'payment_method'),
        ];
    }

    /**
     * Get enrollment statistics
     */
    protected function getEnrollmentStats($academicYear)
    {
        return [
            'total' => StudentSubject::where('academic_year', $academicYear)->count(),
            'active' => StudentSubject::where('academic_year', $academicYear)
                ->where('status', 'active')
                ->count(),
            'completed' => StudentSubject::where('academic_year', $academicYear)
                ->where('status', 'completed')
                ->count(),
            'dropped' => StudentSubject::where('academic_year', $academicYear)
                ->where('status', 'dropped')
                ->count(),
            'average_score' => StudentSubject::where('academic_year', $academicYear)
                ->whereNotNull('score')
                ->avg('score'),
        ];
    }

    /**
     * Get recent student registrations
     */
    protected function getRecentRegistrations($limit = 10)
    {
        return Student::with(['class', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'uuid' => $student->uuid,
                    'student_code' => $student->student_code,
                    'full_name' => $student->full_name,
                    'class' => $student->class?->name,
                    'registration_date' => $student->registration_date->format('Y-m-d'),
                    'created_at' => $student->created_at->format('Y-m-d H:i:s'),
                    'created_by' => $student->creator?->name,
                ];
            });
    }

    /**
     * Get monthly registration trends
     */
    public function registrationTrends(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $trends = Student::select(
            DB::raw('MONTH(registration_date) as month'),
            DB::raw('count(*) as count')
        )
            ->whereYear('registration_date', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('count', 'month');

        // Fill in missing months with 0
        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyTrends[$i] = $trends->get($i, 0);
        }

        return response()->jsonSuccess([
            'year' => $year,
            'trends' => $monthlyTrends,
        ]);
    }

    /**
     * Get payment collection trends
     */
    public function paymentTrends(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $trends = Payment::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('count(*) as count'),
            DB::raw('sum(paid_amount) as total')
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $trend = $trends->firstWhere('month', $i);
            $monthlyTrends[$i] = [
                'count' => $trend ? $trend->count : 0,
                'total' => $trend ? (float) $trend->total : 0,
            ];
        }

        return response()->jsonSuccess([
            'year' => $year,
            'trends' => $monthlyTrends,
        ]);
    }

    /**
     * Get top performing students
     */
    public function topStudents(Request $request)
    {
        $academicYear = $request->get('academic_year', date('Y') . '-' . (date('Y') + 1));
        $limit = $request->get('limit', 10);

        $topStudents = Student::select('students.*')
            ->join('student_subjects', 'students.id', '=', 'student_subjects.student_id')
            ->where('student_subjects.academic_year', $academicYear)
            ->whereNotNull('student_subjects.score')
            ->groupBy('students.id')
            ->orderByRaw('AVG(student_subjects.score) DESC')
            ->limit($limit)
            ->with(['class'])
            ->get()
            ->map(function ($student) use ($academicYear) {
                $avgScore = StudentSubject::where('student_id', $student->id)
                    ->where('academic_year', $academicYear)
                    ->whereNotNull('score')
                    ->avg('score');

                return [
                    'id' => $student->id,
                    'uuid' => $student->uuid,
                    'student_code' => $student->student_code,
                    'full_name' => $student->full_name,
                    'class' => $student->class?->name,
                    'average_score' => round($avgScore, 2),
                ];
            });

        return response()->jsonSuccess($topStudents);
    }
}
