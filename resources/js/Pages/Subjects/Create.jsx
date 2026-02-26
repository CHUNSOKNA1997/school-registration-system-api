import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { BookOpen, ArrowLeft, Save } from 'lucide-react';
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

export default function CreateSubject() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        name_khmer: '',
        description: '',
        grade_level: '',
        subject_type: 'core',
        credits: 1,
        hours_per_week: 1,
        fee: 0,
        monthly_fee: 0,
        is_active: true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/subjects', {
            onSuccess: () => toast.success('Subject created successfully'),
            onError: () => toast.error('Please fix the errors below'),
        });
    };

    const selectClass = 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring';

    return (
        <AuthenticatedLayout>
            <Head title="Add Subject" />

            <PageHeader icon={BookOpen} title="Add Subject" description="Create a new subject">
                <Button variant="outline" onClick={() => router.get('/subjects')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back to Subjects
                </Button>
            </PageHeader>

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader className="pb-4">
                        <CardTitle className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Subject Information</CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <FIELD label="Subject Name" required error={errors.name}><Input value={data.name} onChange={(e) => setData('name', e.target.value)} /></FIELD>
                        <FIELD label="Khmer Name" error={errors.name_khmer}><Input value={data.name_khmer} onChange={(e) => setData('name_khmer', e.target.value)} /></FIELD>
                        <FIELD label="Grade Level" required error={errors.grade_level}><Input type="number" min="1" max="12" value={data.grade_level} onChange={(e) => setData('grade_level', e.target.value)} /></FIELD>
                        <FIELD label="Subject Type" required error={errors.subject_type}>
                            <select className={selectClass} value={data.subject_type} onChange={(e) => setData('subject_type', e.target.value)}>
                                <option value="core">Core</option>
                                <option value="elective">Elective</option>
                                <option value="extra">Extra</option>
                            </select>
                        </FIELD>
                        <FIELD label="Credits" error={errors.credits}><Input type="number" min="1" value={data.credits} onChange={(e) => setData('credits', e.target.value)} /></FIELD>
                        <FIELD label="Hours / Week" error={errors.hours_per_week}><Input type="number" min="1" value={data.hours_per_week} onChange={(e) => setData('hours_per_week', e.target.value)} /></FIELD>
                        <FIELD label="One-time Fee" error={errors.fee}><Input type="number" min="0" step="0.01" value={data.fee} onChange={(e) => setData('fee', e.target.value)} /></FIELD>
                        <FIELD label="Monthly Fee" error={errors.monthly_fee}><Input type="number" min="0" step="0.01" value={data.monthly_fee} onChange={(e) => setData('monthly_fee', e.target.value)} /></FIELD>
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
                    <Button type="button" variant="outline" onClick={() => router.get('/subjects')}>Cancel</Button>
                    <Button type="submit" disabled={processing}>
                        <Save className="mr-2 h-4 w-4" />
                        {processing ? 'Saving…' : 'Create Subject'}
                    </Button>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}
