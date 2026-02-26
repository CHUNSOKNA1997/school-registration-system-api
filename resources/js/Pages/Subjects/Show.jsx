import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { ArrowLeft } from 'lucide-react';
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
        router.delete(`/subjects/${subject.uuid}`, {
            onSuccess: () => toast.success('Subject deleted successfully'),
            onError: () => toast.error('Failed to delete subject'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={subject.name} />

            <div className="mb-4">
                <Button variant="ghost" onClick={() => router.get('/subjects')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Subject Details</div>
                            <div className="grid grid-cols-2 gap-5 px-6 pb-6">
                                <InfoItem label="Name" value={subject.name} />
                                <InfoItem label="Khmer Name" value={subject.name_khmer} />
                                <InfoItem label="Grade Level" value={`Grade ${subject.grade_level}`} />
                                <InfoItem label="Type" value={formatType(subject.subject_type)} />
                                <InfoItem label="Credits" value={subject.credits} />
                                <InfoItem label="Hours / Week" value={subject.hours_per_week} />
                                <InfoItem label="Fee" value={`$${subject.fee ?? 0}`} />
                                <InfoItem label="Monthly Fee" value={`$${subject.monthly_fee ?? 0}`} />
                            </div>
                        </div>
                    </Card>

                    {subject.description && (
                        <Card>
                            <div>
                                <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Description</div>
                                <div className="px-6 pb-6">
                                    <p className="text-sm text-muted-foreground leading-relaxed">{subject.description}</p>
                                </div>
                            </div>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Quick Stats</div>
                            <div className="space-y-3 px-6 pb-6">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Assigned Teachers</span>
                                    <span className="text-lg font-semibold text-foreground">{subject.teachers_count ?? 0}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Enrolled Students</span>
                                    <span className="text-lg font-semibold text-foreground">{subject.students_count ?? 0}</span>
                                </div>
                            </div>
                        </div>
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
