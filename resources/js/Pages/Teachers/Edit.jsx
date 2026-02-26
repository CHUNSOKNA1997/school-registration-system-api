import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { GraduationCap, ArrowLeft, Save } from 'lucide-react';
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

export default function EditTeacher({ teacher }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: teacher.first_name ?? '',
        last_name: teacher.last_name ?? '',
        khmer_name: teacher.khmer_name ?? '',
        gender: teacher.gender ?? '',
        date_of_birth: teacher.date_of_birth ?? '',
        phone: teacher.phone ?? '',
        email: teacher.email ?? '',
        nationality: teacher.nationality ?? 'Cambodian',
        employment_type: teacher.employment_type ?? 'full_time',
        hire_date: teacher.hire_date ?? '',
        salary: teacher.salary ?? '',
        qualification: teacher.qualification ?? '',
        specialization: teacher.specialization ?? '',
        emergency_contact: teacher.emergency_contact ?? '',
        current_address: teacher.current_address ?? '',
        permanent_address: teacher.permanent_address ?? '',
        is_active: teacher.is_active ?? true,
        notes: teacher.notes ?? '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/teachers/${teacher.uuid}`, {
            onSuccess: () => toast.success('Teacher updated successfully'),
            onError: () => toast.error('Please fix the errors below'),
        });
    };

    const selectClass = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring';

    return (
        <AuthenticatedLayout>
            <Head title={`Edit – ${teacher.first_name} ${teacher.last_name}`} />

            <div className="mb-4">
                <Button variant="ghost" onClick={() => router.get('/teachers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Basic Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="First Name" required error={errors.first_name}><Input value={data.first_name} onChange={(e) => setData('first_name', e.target.value)} /></FIELD>
                        <FIELD label="Last Name" required error={errors.last_name}><Input value={data.last_name} onChange={(e) => setData('last_name', e.target.value)} /></FIELD>
                        <FIELD label="Khmer Name" error={errors.khmer_name}><Input value={data.khmer_name} onChange={(e) => setData('khmer_name', e.target.value)} /></FIELD>
                        <FIELD label="Gender" required error={errors.gender}>
                            <select className={selectClass} value={data.gender} onChange={(e) => setData('gender', e.target.value)}>
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </FIELD>
                        <FIELD label="Date of Birth" error={errors.date_of_birth}><Input type="date" value={data.date_of_birth} onChange={(e) => setData('date_of_birth', e.target.value)} /></FIELD>
                        <FIELD label="Phone" required error={errors.phone}><Input value={data.phone} onChange={(e) => setData('phone', e.target.value)} /></FIELD>
                        <FIELD label="Email" error={errors.email}><Input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} /></FIELD>
                        <FIELD label="Nationality" error={errors.nationality}><Input value={data.nationality} onChange={(e) => setData('nationality', e.target.value)} /></FIELD>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Employment Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Employment Type" required error={errors.employment_type}>
                            <select className={selectClass} value={data.employment_type} onChange={(e) => setData('employment_type', e.target.value)}>
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                                <option value="contract">Contract</option>
                            </select>
                        </FIELD>
                        <FIELD label="Hire Date" error={errors.hire_date}><Input type="date" value={data.hire_date} onChange={(e) => setData('hire_date', e.target.value)} /></FIELD>
                        <FIELD label="Salary" error={errors.salary}><Input type="number" min="0" step="0.01" value={data.salary} onChange={(e) => setData('salary', e.target.value)} /></FIELD>
                        <FIELD label="Qualification" error={errors.qualification}><Input value={data.qualification} onChange={(e) => setData('qualification', e.target.value)} /></FIELD>
                        <FIELD label="Specialization" error={errors.specialization}><Input value={data.specialization} onChange={(e) => setData('specialization', e.target.value)} /></FIELD>
                        <FIELD label="Emergency Contact" error={errors.emergency_contact}><Input value={data.emergency_contact} onChange={(e) => setData('emergency_contact', e.target.value)} /></FIELD>
                        <FIELD label="Active" error={errors.is_active}>
                            <select className={selectClass} value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')}>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </FIELD>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Address & Notes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Current Address" error={errors.current_address}><Textarea rows={2} value={data.current_address} onChange={(e) => setData('current_address', e.target.value)} /></FIELD>
                        <FIELD label="Permanent Address" error={errors.permanent_address}><Textarea rows={2} value={data.permanent_address} onChange={(e) => setData('permanent_address', e.target.value)} /></FIELD>
                        <div className="sm:col-span-2">
                            <FIELD label="Notes" error={errors.notes}><Textarea rows={3} value={data.notes} onChange={(e) => setData('notes', e.target.value)} /></FIELD>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => router.get(`/teachers/${teacher.id}`)}>Cancel</Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
