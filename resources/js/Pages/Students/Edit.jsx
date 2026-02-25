import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { Users, ArrowLeft, Save } from 'lucide-react';
import PageHeader from '@/components/PageHeader';

const FIELD = ({ label, required, error, children }) => (
    <div className="space-y-1.5">
        <Label>
            {label}
            {required && <span className="ml-0.5 text-destructive">*</span>}
        </Label>
        {children}
        {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
);

export default function EditStudent({ student, classrooms }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: student.first_name ?? '',
        last_name: student.last_name ?? '',
        khmer_name: student.khmer_name ?? '',
        date_of_birth: student.date_of_birth ?? '',
        place_of_birth: student.place_of_birth ?? '',
        gender: student.gender ?? '',
        student_type: student.student_type ?? 'regular',
        nationality: student.nationality ?? '',
        phone: student.phone ?? '',
        email: student.email ?? '',
        current_address: student.current_address ?? '',
        permanent_address: student.permanent_address ?? '',
        parent_name: student.parent_name ?? '',
        parent_phone: student.parent_phone ?? '',
        parent_occupation: student.parent_occupation ?? '',
        emergency_contact: student.emergency_contact ?? '',
        emergency_contact_relationship: student.emergency_contact_relationship ?? '',
        class_id: student.class_id ?? '',
        shift: student.shift ?? '',
        registration_date: student.registration_date ?? '',
        academic_year: student.academic_year ?? '',
        previous_school: student.previous_school ?? '',
        status: student.status ?? 'active',
        notes: student.notes ?? '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/students/${student.id}`, {
            onSuccess: () => toast.success('Student updated successfully'),
            onError: () => toast.error('Please fix the errors below'),
        });
    };

    const selectClass = "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring";

    return (
        <AuthenticatedLayout>
            <Head title={`Edit – ${student.first_name} ${student.last_name}`} />

            <PageHeader
                icon={Users}
                title={`Edit Student`}
                description={`${student.first_name} ${student.last_name} · ${student.student_code}`}
            >
                <Button variant="outline" onClick={() => router.get(`/students/${student.id}`)}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
            </PageHeader>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Personal Information */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Personal Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="First Name" required error={errors.first_name}>
                            <Input value={data.first_name} onChange={e => setData('first_name', e.target.value)} />
                        </FIELD>
                        <FIELD label="Last Name" required error={errors.last_name}>
                            <Input value={data.last_name} onChange={e => setData('last_name', e.target.value)} />
                        </FIELD>
                        <FIELD label="Khmer Name" error={errors.khmer_name}>
                            <Input value={data.khmer_name} onChange={e => setData('khmer_name', e.target.value)} />
                        </FIELD>
                        <FIELD label="Date of Birth" required error={errors.date_of_birth}>
                            <Input type="date" value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} />
                        </FIELD>
                        <FIELD label="Place of Birth" error={errors.place_of_birth}>
                            <Input value={data.place_of_birth} onChange={e => setData('place_of_birth', e.target.value)} />
                        </FIELD>
                        <FIELD label="Gender" required error={errors.gender}>
                            <select className={selectClass} value={data.gender} onChange={e => setData('gender', e.target.value)} required>
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </FIELD>
                        <FIELD label="Nationality" error={errors.nationality}>
                            <Input value={data.nationality} onChange={e => setData('nationality', e.target.value)} />
                        </FIELD>
                        <FIELD label="Student Type" error={errors.student_type}>
                            <select className={selectClass} value={data.student_type} onChange={e => setData('student_type', e.target.value)}>
                                <option value="regular">Regular</option>
                                <option value="special">Special</option>
                            </select>
                        </FIELD>
                    </CardContent>
                </Card>

                {/* Contact */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Contact Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Phone" error={errors.phone}>
                            <Input value={data.phone} onChange={e => setData('phone', e.target.value)} />
                        </FIELD>
                        <FIELD label="Email" error={errors.email}>
                            <Input type="email" value={data.email} onChange={e => setData('email', e.target.value)} />
                        </FIELD>
                        <FIELD label="Current Address" error={errors.current_address}>
                            <Textarea rows={2} value={data.current_address} onChange={e => setData('current_address', e.target.value)} />
                        </FIELD>
                        <FIELD label="Permanent Address" error={errors.permanent_address}>
                            <Textarea rows={2} value={data.permanent_address} onChange={e => setData('permanent_address', e.target.value)} />
                        </FIELD>
                    </CardContent>
                </Card>

                {/* Parent / Guardian */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Parent / Guardian
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Parent Name" error={errors.parent_name}>
                            <Input value={data.parent_name} onChange={e => setData('parent_name', e.target.value)} />
                        </FIELD>
                        <FIELD label="Parent Phone" error={errors.parent_phone}>
                            <Input value={data.parent_phone} onChange={e => setData('parent_phone', e.target.value)} />
                        </FIELD>
                        <FIELD label="Parent Occupation" error={errors.parent_occupation}>
                            <Input value={data.parent_occupation} onChange={e => setData('parent_occupation', e.target.value)} />
                        </FIELD>
                        <FIELD label="Emergency Contact" error={errors.emergency_contact}>
                            <Input value={data.emergency_contact} onChange={e => setData('emergency_contact', e.target.value)} />
                        </FIELD>
                        <div className="sm:col-span-2">
                            <FIELD label="Emergency Contact Relationship" error={errors.emergency_contact_relationship}>
                                <Input value={data.emergency_contact_relationship} onChange={e => setData('emergency_contact_relationship', e.target.value)} />
                            </FIELD>
                        </div>
                    </CardContent>
                </Card>

                {/* Academic */}
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                            Academic Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Class" error={errors.class_id}>
                            <select className={selectClass} value={data.class_id} onChange={e => setData('class_id', e.target.value)}>
                                <option value="">Select class</option>
                                {classrooms?.map(c => (
                                    <option key={c.id} value={c.id}>{c.name_en}</option>
                                ))}
                            </select>
                        </FIELD>
                        <FIELD label="Shift" error={errors.shift}>
                            <select className={selectClass} value={data.shift} onChange={e => setData('shift', e.target.value)}>
                                <option value="">Select shift</option>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                                <option value="evening">Evening</option>
                            </select>
                        </FIELD>
                        <FIELD label="Registration Date" error={errors.registration_date}>
                            <Input type="date" value={data.registration_date} onChange={e => setData('registration_date', e.target.value)} />
                        </FIELD>
                        <FIELD label="Academic Year" error={errors.academic_year}>
                            <Input value={data.academic_year} onChange={e => setData('academic_year', e.target.value)} />
                        </FIELD>
                        <FIELD label="Previous School" error={errors.previous_school}>
                            <Input value={data.previous_school} onChange={e => setData('previous_school', e.target.value)} />
                        </FIELD>
                        <FIELD label="Status" error={errors.status}>
                            <select className={selectClass} value={data.status} onChange={e => setData('status', e.target.value)}>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="graduated">Graduated</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </FIELD>
                        <div className="sm:col-span-2">
                            <FIELD label="Notes" error={errors.notes}>
                                <Textarea rows={3} value={data.notes} onChange={e => setData('notes', e.target.value)} />
                            </FIELD>
                        </div>
                    </CardContent>
                </Card>

                {/* Actions */}
                <div className="flex items-center justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => router.get(`/students/${student.id}`)}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
