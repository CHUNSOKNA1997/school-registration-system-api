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

const formatType = (type) => (type || '—').replace(/\b\w/g, c => c.toUpperCase());

export default function ShowSubject({ auth, subject }) {
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`/subjects/${subject.id}`, {
            onSuccess: () => toast.success('Subject deleted successfully'),
            onError: () => toast.error('Failed to delete subject'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={subject.name} />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <Button variant="outline" className="rounded-xl" onClick={() => router.get('/subjects')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                {auth.user?.is_admin && (
                    <>
                        <Button className="rounded-xl" onClick={() => router.get(`/subjects/${subject.id}/edit`)}>
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
                <Badge
                    variant="outline"
                    className={subject.is_active
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : 'bg-slate-100 text-slate-600 border-slate-200'}
                >
                    {subject.is_active ? 'Active' : 'Inactive'}
                </Badge>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Subject Details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-2 gap-5">
                            <InfoItem label="Name" value={subject.name} />
                            <InfoItem label="Khmer Name" value={subject.name_khmer} />
                            <InfoItem label="Grade Level" value={`Grade ${subject.grade_level}`} />
                            <InfoItem label="Type" value={formatType(subject.subject_type)} />
                            <InfoItem label="Credits" value={subject.credits} />
                            <InfoItem label="Hours / Week" value={subject.hours_per_week} />
                            <InfoItem label="Fee" value={`$${subject.fee ?? 0}`} />
                            <InfoItem label="Monthly Fee" value={`$${subject.monthly_fee ?? 0}`} />
                        </CardContent>
                    </Card>

                    {subject.description && (
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Description</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground leading-relaxed">{subject.description}</p>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Quick Stats</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Assigned Teachers</span>
                                <span className="text-lg font-semibold text-foreground">{subject.teachers_count ?? 0}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm text-muted-foreground">Enrolled Students</span>
                                <span className="text-lg font-semibold text-foreground">{subject.students_count ?? 0}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                open={deleteDialog}
                onOpenChange={setDeleteDialog}
                title="Delete Subject"
                description={`Are you sure you want to delete ${subject.name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
