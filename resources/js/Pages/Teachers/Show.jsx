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

const formatType = (type) => (type || '—').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

export default function ShowTeacher({ auth, teacher }) {
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const handleDelete = () => {
        setDeleting(true);
        router.delete(`/teachers/${teacher.uuid}`, {
            onSuccess: () => toast.success('Teacher deleted successfully'),
            onError: () => toast.error('Failed to delete teacher'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${teacher.first_name} ${teacher.last_name}`} />

            <div className="mb-4">
                <Button variant="ghost" onClick={() => router.get('/teachers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Personal Information</div>
                            <div className="grid grid-cols-2 gap-5 px-6 pb-6">
                                <InfoItem label="First Name" value={teacher.first_name} />
                                <InfoItem label="Last Name" value={teacher.last_name} />
                                <InfoItem label="Khmer Name" value={teacher.khmer_name} />
                                <InfoItem label="Gender" value={teacher.gender} className="capitalize" />
                                <InfoItem label="Date of Birth" value={teacher.date_of_birth} />
                                <InfoItem label="Nationality" value={teacher.nationality} />
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Contact Information</div>
                            <div className="grid grid-cols-2 gap-5 px-6 pb-6">
                                <InfoItem label="Phone" value={teacher.phone} />
                                <InfoItem label="Email" value={teacher.email} />
                                <InfoItem label="Emergency Contact" value={teacher.emergency_contact} />
                                <InfoItem label="Current Address" value={teacher.current_address} />
                                <div className="col-span-2">
                                    <InfoItem label="Permanent Address" value={teacher.permanent_address} />
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Employment</div>
                            <div className="space-y-4 px-6 pb-6">
                                <InfoItem label="Employment Type" value={formatType(teacher.employment_type)} />
                                <InfoItem label="Hire Date" value={teacher.hire_date} />
                                <InfoItem label="Salary" value={teacher.salary ? `$${teacher.salary}` : '—'} />
                                <InfoItem label="Qualification" value={teacher.qualification} />
                                <InfoItem label="Specialization" value={teacher.specialization} />
                            </div>
                        </div>
                    </Card>

                    <Card>
                        <div>
                            <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Quick Stats</div>
                            <div className="px-6 pb-6">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Assigned Subjects</span>
                                    <span className="text-lg font-semibold text-foreground">{teacher.subjects_count ?? 0}</span>
                                </div>
                            </div>
                        </div>
                    </Card>

                    {teacher.notes && (
                        <Card>
                            <div>
                                <div className="px-6 pt-6 pb-2 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Notes</div>
                                <div className="px-6 pb-6">
                                    <p className="text-sm text-muted-foreground leading-relaxed">{teacher.notes}</p>
                                </div>
                            </div>
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
