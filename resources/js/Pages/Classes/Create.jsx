import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { School, ArrowLeft, Save } from 'lucide-react';
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

export default function CreateClass() {
    const year = new Date().getFullYear();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        grade_level: '',
        section: '',
        academic_year: `${year}-${year + 1}`,
        capacity: 30,
        room_number: '',
        is_active: true,
        description: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/classes', {
            onSuccess: () => toast.success('Class created successfully'),
            onError: () => toast.error('Please fix the errors below'),
        });
    };

    const selectClass = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring';

    return (
        <AuthenticatedLayout>
            <Head title="Add Class" />

            <PageHeader icon={School} title="Add Class" description="Create a new class group">
                <Button variant="outline" onClick={() => router.get('/classes')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back to Classes
                </Button>
            </PageHeader>

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Class Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Class Name" required error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Grade 7-A" /></FIELD>
                        <FIELD label="Grade Level" required error={errors.grade_level}><Input type="number" min="1" max="12" value={data.grade_level} onChange={(e) => setData('grade_level', e.target.value)} /></FIELD>
                        <FIELD label="Section" required error={errors.section}><Input value={data.section} onChange={(e) => setData('section', e.target.value)} placeholder="A" /></FIELD>
                        <FIELD label="Academic Year" required error={errors.academic_year}><Input value={data.academic_year} onChange={(e) => setData('academic_year', e.target.value)} /></FIELD>
                        <FIELD label="Capacity" required error={errors.capacity}><Input type="number" min="1" value={data.capacity} onChange={(e) => setData('capacity', e.target.value)} /></FIELD>
                        <FIELD label="Room Number" error={errors.room_number}><Input value={data.room_number} onChange={(e) => setData('room_number', e.target.value)} /></FIELD>
                        <FIELD label="Active" error={errors.is_active}>
                            <select className={selectClass} value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')}>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </FIELD>
                        <div className="sm:col-span-2">
                            <FIELD label="Description" error={errors.description}><Textarea rows={4} value={data.description} onChange={(e) => setData('description', e.target.value)} /></FIELD>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex items-center justify-end gap-3">
                    <Button type="button" variant="outline" onClick={() => router.get('/classes')}>Cancel</Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Saving…' : 'Create Class'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
