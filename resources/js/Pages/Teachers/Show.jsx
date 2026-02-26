import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import ConfirmDialog from '@/components/ConfirmDialog';

function InfoItem({ label, value, className = '' }) {
    return (
        <div>
            <p className="mb-0.5 text-[11px] font-medium uppercase tracking-wide text-muted-foreground">{label}</p>
            <p className={`text-sm text-foreground ${className}`}>{value ?? '—'}</p>
        </div>
    );
}

const formatType = (type) => (type || '—').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

export default function ShowTeacher({ auth, teacher }) {
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`/teachers/${teacher.id}`, {
            onSuccess: () => toast.success('Teacher deleted successfully'),
            onError: () => toast.error('Failed to delete teacher'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${teacher.first_name} ${teacher.last_name}`} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button variant="outline" className="rounded-xl" onClick={() => router.get('/teachers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                {auth.user?.is_admin && (
                    <>
                        <Button className="rounded-xl" onClick={() => router.get(`/teachers/${teacher.id}/edit`)}>
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

            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p className="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">Teacher Profile</p>
                    <h1 className="mt-1 text-xl font-semibold text-foreground">
                        {teacher.first_name} {teacher.last_name}
                    </h1>
                    <p className="text-sm text-muted-foreground">Code: {teacher.teacher_code}</p>
                </div>
                <Badge
                    variant="outline"
                    className={teacher.is_active
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-slate-100 text-slate-600 border-slate-200'}
                >
                    {teacher.is_active ? 'Active' : 'Inactive'}
                </Badge>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Personal Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="First Name" value={teacher.first_name} />
                            <InfoItem label="Last Name" value={teacher.last_name} />
                            <InfoItem label="Khmer Name" value={teacher.khmer_name} />
                            <InfoItem label="Gender" value={teacher.gender} className="capitalize" />
                            <InfoItem label="Date of Birth" value={teacher.date_of_birth} />
                            <InfoItem label="Nationality" value={teacher.nationality} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Contact Information</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Phone" value={teacher.phone} />
                            <InfoItem label="Email" value={teacher.email} />
                            <InfoItem label="Emergency Contact" value={teacher.emergency_contact} />
                            <InfoItem label="Current Address" value={teacher.current_address} />
                            <div className="col-span-2">
                                <InfoItem label="Permanent Address" value={teacher.permanent_address} />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Employment</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InfoItem label="Employment Type" value={formatType(teacher.employment_type)} />
                            <InfoItem label="Hire Date" value={teacher.hire_date} />
                            <InfoItem label="Salary" value={teacher.salary ? `$${teacher.salary}` : '—'} />
                            <InfoItem label="Qualification" value={teacher.qualification} />
                            <InfoItem label="Specialization" value={teacher.specialization} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Quick Stats</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Assigned Subjects</span>
                                <span className="text-lg font-semibold text-foreground">{teacher.subjects_count ?? 0}</span>
                            </div>
                        </CardContent>
                    </Card>

                    {teacher.notes && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground leading-relaxed">{teacher.notes}</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            <ConfirmDialog
                open={deleteDialog}
                onOpenChange={setDeleteDialog}
                title="Delete Teacher"
                description={`Are you sure you want to delete ${teacher.first_name} ${teacher.last_name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
