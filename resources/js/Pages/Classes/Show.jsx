import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeadCell, TableRow,
} from 'flowbite-react';
import { toast } from 'sonner';
import { ArrowLeft } from 'lucide-react';
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

            <div className="mb-4">
                <Button variant="ghost" onClick={() => router.get('/classes')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <div>
                            <div className="flex items-center justify-between px-6 pt-6 pb-2">
                                <span className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Class Information</span>
                                <Badge
                                    variant="outline"
                                    className={classroom.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border-slate-200'}
                                >
                                    {classroom.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                            </div>
                            <div className="grid grid-cols-2 gap-5 px-6 pb-6">
                                <InfoItem label="Class Name" value={classroom.name} />
                                <InfoItem label="Academic Year" value={classroom.academic_year} />
                                <InfoItem label="Grade Level" value={classroom.grade_level} />
                                <InfoItem label="Section" value={classroom.section} />
                                <InfoItem label="Capacity" value={classroom.capacity} />
                                <InfoItem label="Current Enrollment" value={classroom.students_count ?? classroom.current_enrollment ?? 0} />
                                <InfoItem label="Room Number" value={classroom.room_number} />
                                <InfoItem label="Description" value={classroom.description} />
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Students</div>
                            <div>
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
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Summary</div>
                            <div className="space-y-3 px-6 pb-6">
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
                            </div>
                        </div>
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
