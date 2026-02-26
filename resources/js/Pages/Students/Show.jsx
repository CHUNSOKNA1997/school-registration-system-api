import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeadCell, TableRow,
} from 'flowbite-react';
import { toast } from 'sonner';
import { ArrowLeft, Pencil, Trash2, BookOpen } from 'lucide-react';
import StatusBadge from '@/components/StatusBadge';
import ConfirmDialog from '@/components/ConfirmDialog';

function InfoItem({ label, value, className = '' }) {
    return (
        <div>
            <p className="mb-0.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className={`text-sm text-foreground ${className}`}>{value ?? '—'}</p>
        </div>
    );
}

export default function ShowStudent({ auth, student }) {
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`/students/${student.id}`, {
            onSuccess: () => toast.success('Student deleted'),
            onError: () => toast.error('Failed to delete student'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${student.first_name} ${student.last_name}`} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button variant="outline" className="rounded-xl" onClick={() => router.get('/students')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <Link href={`/students/${student.id}/enrollments`}>
                    <Button variant="outline" className="rounded-xl">
                        <BookOpen className="mr-2 h-4 w-4" />
                        Enrollments
                    </Button>
                </Link>
                <Link href={`/students/${student.id}/edit`}>
                    <Button className="rounded-xl">
                        <Pencil className="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                {auth.user?.is_admin && (
                    <Button
                        variant="outline"
                        className="rounded-xl text-destructive border-destructive/30 hover:bg-destructive/5"
                        onClick={() => setDeleteDialog(true)}
                    >
                        <Trash2 className="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                )}
            </div>

            <div className="mb-6">
                <StatusBadge status={student.status} />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Left (2/3) */}
                <div className="space-y-6 lg:col-span-2">
                    {/* Personal */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Personal Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Full Name (EN)" value={`${student.first_name} ${student.last_name}`} />
                            <InfoItem label="Full Name (KH)" value={student.khmer_name} />
                            <InfoItem label="Date of Birth" value={student.date_of_birth} />
                            <InfoItem label="Place of Birth" value={student.place_of_birth} />
                            <InfoItem label="Gender" value={student.gender} className="capitalize" />
                            <InfoItem label="Nationality" value={student.nationality} />
                            <InfoItem label="Student Type" value={student.student_type} className="capitalize" />
                        </CardContent>
                    </Card>

                    {/* Contact */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Contact Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Phone" value={student.phone} />
                            <InfoItem label="Email" value={student.email} />
                            <div className="col-span-2">
                                <InfoItem label="Current Address" value={student.current_address} />
                            </div>
                            <div className="col-span-2">
                                <InfoItem label="Permanent Address" value={student.permanent_address} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Parent */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Parent / Guardian
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Parent Name" value={student.parent_name} />
                            <InfoItem label="Parent Phone" value={student.parent_phone} />
                            <InfoItem label="Parent Occupation" value={student.parent_occupation} />
                            <InfoItem label="Emergency Contact" value={student.emergency_contact} />
                            <div className="col-span-2">
                                <InfoItem label="Emergency Contact Relationship" value={student.emergency_contact_relationship} />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Enrollments */}
                    {student.enrollments?.length > 0 && (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between pb-3">
                                <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                    Current Enrollments
                                </CardTitle>
                                <Link href={`/students/${student.id}/enrollments`}>
                                    <Button size="sm" variant="ghost" className="text-primary">View All</Button>
                                </Link>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="overflow-x-auto">
                                    <Table hoverable>
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell>Subject</TableHeadCell>
                                                <TableHeadCell>Teacher</TableHeadCell>
                                                <TableHeadCell>Grade</TableHeadCell>
                                                <TableHeadCell>Status</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {student.enrollments.slice(0, 5).map((e) => (
                                                <TableRow key={e.id} className="bg-white">
                                                    <TableCell className="font-medium text-gray-900">{e.subject?.name_en}</TableCell>
                                                    <TableCell className="text-gray-600">{e.teacher?.first_name} {e.teacher?.last_name}</TableCell>
                                                    <TableCell className="text-gray-600">{e.grade ?? '—'}</TableCell>
                                                    <TableCell><StatusBadge status={e.status} /></TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>

                {/* Right sidebar (1/3) */}
                <div className="space-y-6">
                    {/* Academic */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Academic Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InfoItem label="Class" value={student.class?.name_en} />
                            <InfoItem label="Shift" value={student.shift} className="capitalize" />
                            <InfoItem label="Academic Year" value={student.academic_year} />
                            <InfoItem label="Registration Date" value={student.registration_date} />
                            <InfoItem label="Previous School" value={student.previous_school} />
                        </CardContent>
                    </Card>

                    {/* Quick Stats */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                Quick Stats
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {[
                                { label: 'Total Subjects',   value: student.enrollments?.length ?? 0 },
                                { label: 'Total Payments',   value: student.payments?.length ?? 0 },
                                { label: 'Pending Payments', value: student.payments?.filter(p => p.status === 'pending').length ?? 0, danger: true },
                            ].map(({ label, value, danger }) => (
                                <div key={label} className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">{label}</span>
                                    <span className={`text-lg font-semibold ${danger && value > 0 ? 'text-destructive' : 'text-foreground'}`}>
                                        {value}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    {/* Notes */}
                    {student.notes && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                                    Notes
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground leading-relaxed">{student.notes}</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            <ConfirmDialog
                open={deleteDialog}
                onOpenChange={setDeleteDialog}
                title="Delete Student"
                description={`Are you sure you want to delete ${student.first_name} ${student.last_name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
