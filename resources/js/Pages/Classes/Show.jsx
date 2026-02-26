import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeadCell, TableRow,
} from 'flowbite-react';
import { toast } from 'sonner';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import ConfirmDialog from '@/components/ConfirmDialog';
import StatusBadge from '@/components/StatusBadge';

function InfoItem({ label, value, className = '' }) {
    return (
        <div>
            <p className="mb-0.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className={`text-sm text-foreground ${className}`}>{value ?? '—'}</p>
        </div>
    );
}

export default function ShowClass({ auth, classroom }) {
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`/classes/${classroom.uuid}`, {
            onSuccess: () => toast.success('Class deleted successfully'),
            onError: () => toast.error('Failed to delete class'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={classroom.name} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button variant="outline" className="rounded-xl" onClick={() => router.get('/classes')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                {auth.user?.is_admin && (
                    <>
                        <Button className="rounded-xl" onClick={() => router.get(`/classes/${classroom.uuid}/edit`)}>
                            <Pencil className="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                        <Button
                            variant="outline"
                            className="rounded-xl text-destructive border-destructive/30 hover:bg-destructive/5"
                            onClick={() => setDeleteDialog(true)}
                        >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </>
                )}
            </div>

            <div className="mb-6">
                <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Class Details</p>
                <h1 className="mt-1 text-xl font-semibold text-foreground">{classroom.name}</h1>
                <p className="text-sm text-muted-foreground">
                    Grade {classroom.grade_level} · Section {classroom.section}
                </p>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Class Information</CardTitle>
                            <Badge
                                variant="outline"
                                className={classroom.is_active
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-slate-100 text-slate-600 border-slate-200'}
                            >
                                {classroom.is_active ? 'Active' : 'Inactive'}
                            </Badge>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Class Name" value={classroom.name} />
                            <InfoItem label="Academic Year" value={classroom.academic_year} />
                            <InfoItem label="Grade Level" value={classroom.grade_level} />
                            <InfoItem label="Section" value={classroom.section} />
                            <InfoItem label="Capacity" value={classroom.capacity} />
                            <InfoItem label="Current Enrollment" value={classroom.students_count ?? classroom.current_enrollment ?? 0} />
                            <InfoItem label="Room Number" value={classroom.room_number} />
                            <InfoItem label="Description" value={classroom.description} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Students</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            {classroom.students?.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <Table hoverable className="text-sm [&_th]:py-3.5 [&_td]:py-3.5">
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell>Student Code</TableHeadCell>
                                                <TableHeadCell>Name</TableHeadCell>
                                                <TableHeadCell>Status</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {classroom.students.map((student) => (
                                                <TableRow key={student.id} className="bg-background transition-colors hover:bg-muted/30">
                                                    <TableCell className="font-mono text-sm font-medium text-primary">{student.student_code}</TableCell>
                                                    <TableCell className="font-medium text-foreground">{student.first_name} {student.last_name}</TableCell>
                                                    <TableCell><StatusBadge status={student.status} /></TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                <p className="px-6 py-5 text-sm text-muted-foreground">No students assigned to this class.</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Total Students</span>
                                <span className="text-lg font-semibold text-foreground">{classroom.students_count ?? classroom.students?.length ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Available Slots</span>
                                <span className="text-lg font-semibold text-foreground">
                                    {Math.max(0, (classroom.capacity ?? 0) - (classroom.students_count ?? classroom.students?.length ?? 0))}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                open={deleteDialog}
                onOpenChange={setDeleteDialog}
                title="Delete Class"
                description={`Are you sure you want to delete ${classroom.name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
