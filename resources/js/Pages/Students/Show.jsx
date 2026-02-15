import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

export default function ShowStudent({ auth, student }) {
    const getStatusBadge = (status) => {
        const variants = {
            active: 'bg-green-600/20 text-green-400 border-green-600/30',
            inactive: 'bg-gray-600/20 text-gray-400 border-gray-600/30',
            graduated: 'bg-blue-600/20 text-blue-400 border-blue-600/30',
            suspended: 'bg-red-600/20 text-red-400 border-red-600/30',
        };

        return (
            <Badge className={`${variants[status] || variants.active} border`}>
                {status?.charAt(0).toUpperCase() + status?.slice(1)}
            </Badge>
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${student.first_name} ${student.last_name}`} />

            <div className="p-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-semibold text-white">
                                {student.first_name} {student.last_name}
                            </h1>
                            {getStatusBadge(student.status)}
                        </div>
                        <p className="text-sm text-white/60 mt-1">
                            Student Code: {student.student_code}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Link href={`/students/${student.id}/enrollments`}>
                            <Button variant="outline" className="bg-white/5 border-white/10 text-white">
                                Manage Enrollments
                            </Button>
                        </Link>
                        <Link href={`/students/${student.id}/edit`}>
                            <Button variant="outline" className="bg-white/5 border-white/10 text-white">
                                Edit
                            </Button>
                        </Link>
                        <Link href="/students">
                            <Button variant="ghost" className="text-white/60 hover:text-white">
                                Back
                            </Button>
                        </Link>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Info - 2 columns */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Personal Information */}
                        <Card className="bg-[#1a1a1a] border-white/10">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-white">
                                    Personal Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <InfoItem label="Full Name (EN)" value={`${student.first_name} ${student.last_name}`} />
                                    <InfoItem label="Full Name (KH)" value={student.khmer_name} />
                                    <InfoItem label="Date of Birth" value={student.date_of_birth} />
                                    <InfoItem label="Place of Birth" value={student.place_of_birth} />
                                    <InfoItem label="Gender" value={student.gender} className="capitalize" />
                                    <InfoItem label="Nationality" value={student.nationality} />
                                    <InfoItem label="Student Type" value={student.student_type} className="capitalize" />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Contact Information */}
                        <Card className="bg-[#1a1a1a] border-white/10">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-white">
                                    Contact Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <InfoItem label="Phone" value={student.phone} />
                                    <InfoItem label="Email" value={student.email} />
                                    <div className="col-span-2">
                                        <InfoItem label="Current Address" value={student.current_address} />
                                    </div>
                                    <div className="col-span-2">
                                        <InfoItem label="Permanent Address" value={student.permanent_address} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Parent/Guardian Information */}
                        <Card className="bg-[#1a1a1a] border-white/10">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-white">
                                    Parent/Guardian Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 gap-4">
                                    <InfoItem label="Parent Name" value={student.parent_name} />
                                    <InfoItem label="Parent Phone" value={student.parent_phone} />
                                    <InfoItem label="Parent Occupation" value={student.parent_occupation} />
                                    <InfoItem label="Emergency Contact" value={student.emergency_contact} />
                                    <div className="col-span-2">
                                        <InfoItem
                                            label="Emergency Contact Relationship"
                                            value={student.emergency_contact_relationship}
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Current Enrollments */}
                        {student.enrollments && student.enrollments.length > 0 && (
                            <Card className="bg-[#1a1a1a] border-white/10">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-lg font-semibold text-white">
                                            Current Enrollments
                                        </CardTitle>
                                        <Link href={`/students/${student.id}/enrollments`}>
                                            <Button size="sm" variant="ghost" className="text-white/60 hover:text-white">
                                                View All
                                            </Button>
                                        </Link>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Subject</TableHead>
                                                <TableHead>Teacher</TableHead>
                                                <TableHead>Grade</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {student.enrollments.slice(0, 5).map((enrollment) => (
                                                <TableRow key={enrollment.id}>
                                                    <TableCell className="font-medium">
                                                        {enrollment.subject?.name_en}
                                                    </TableCell>
                                                    <TableCell>
                                                        {enrollment.teacher?.first_name}{' '}
                                                        {enrollment.teacher?.last_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {enrollment.grade || 'N/A'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge className="bg-green-600/20 text-green-400 border-green-600/30 border capitalize">
                                                            {enrollment.status}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Sidebar - 1 column */}
                    <div className="space-y-6">
                        {/* Academic Information */}
                        <Card className="bg-[#1a1a1a] border-white/10">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-white">
                                    Academic Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <InfoItem label="Class" value={student.class?.name_en} />
                                <InfoItem label="Shift" value={student.shift} className="capitalize" />
                                <InfoItem label="Academic Year" value={student.academic_year} />
                                <InfoItem label="Registration Date" value={student.registration_date} />
                                <InfoItem label="Previous School" value={student.previous_school} />
                            </CardContent>
                        </Card>

                        {/* Quick Stats */}
                        <Card className="bg-[#1a1a1a] border-white/10">
                            <CardHeader>
                                <CardTitle className="text-lg font-semibold text-white">
                                    Quick Stats
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-white/60">Total Subjects</span>
                                    <span className="text-lg font-semibold text-white">
                                        {student.enrollments?.length || 0}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-white/60">Total Payments</span>
                                    <span className="text-lg font-semibold text-white">
                                        {student.payments?.length || 0}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-white/60">Pending Payments</span>
                                    <span className="text-lg font-semibold text-red-400">
                                        {student.payments?.filter(p => p.status === 'pending').length || 0}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Notes */}
                        {student.notes && (
                            <Card className="bg-[#1a1a1a] border-white/10">
                                <CardHeader>
                                    <CardTitle className="text-lg font-semibold text-white">
                                        Notes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-white/70">{student.notes}</p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function InfoItem({ label, value, className = '' }) {
    return (
        <div>
            <p className="text-xs text-white/40 mb-1">{label}</p>
            <p className={`text-sm text-white/80 ${className}`}>{value || 'N/A'}</p>
        </div>
    );
}
