import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';

export default function EditStudent({ auth, student, classrooms }) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: student.first_name || '',
        last_name: student.last_name || '',
        khmer_name: student.khmer_name || '',
        date_of_birth: student.date_of_birth || '',
        place_of_birth: student.place_of_birth || '',
        gender: student.gender || '',
        student_type: student.student_type || 'regular',
        nationality: student.nationality || 'Cambodian',
        phone: student.phone || '',
        email: student.email || '',
        current_address: student.current_address || '',
        permanent_address: student.permanent_address || '',
        parent_name: student.parent_name || '',
        parent_phone: student.parent_phone || '',
        parent_occupation: student.parent_occupation || '',
        emergency_contact: student.emergency_contact || '',
        emergency_contact_relationship: student.emergency_contact_relationship || '',
        class_id: student.class_id || '',
        shift: student.shift || '',
        registration_date: student.registration_date || '',
        academic_year: student.academic_year || '',
        previous_school: student.previous_school || '',
        status: student.status || 'active',
        notes: student.notes || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/students/${student.id}`, {
            onSuccess: () => {
                toast.success('Student updated successfully');
            },
            onError: () => {
                toast.error('Failed to update student');
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Edit Student" />

            <div className="p-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-white">Edit Student</h1>
                        <p className="text-sm text-white/60 mt-1">Update student information</p>
                    </div>
                    <Link href="/students">
                        <Button variant="outline" className="bg-white/5 border-white/10 text-white">
                            <svg
                                className="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                            Back to Students
                        </Button>
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Personal Information */}
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-white">
                                Personal Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="first_name" className="text-white/80">
                                        First Name <span className="text-red-400">*</span>
                                    </Label>
                                    <Input
                                        id="first_name"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                        required
                                    />
                                    {errors.first_name && (
                                        <p className="text-sm text-red-400">{errors.first_name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="last_name" className="text-white/80">
                                        Last Name <span className="text-red-400">*</span>
                                    </Label>
                                    <Input
                                        id="last_name"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                        required
                                    />
                                    {errors.last_name && (
                                        <p className="text-sm text-red-400">{errors.last_name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="khmer_name" className="text-white/80">
                                        Khmer Name
                                    </Label>
                                    <Input
                                        id="khmer_name"
                                        value={data.khmer_name}
                                        onChange={(e) => setData('khmer_name', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                    {errors.khmer_name && (
                                        <p className="text-sm text-red-400">{errors.khmer_name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="date_of_birth" className="text-white/80">
                                        Date of Birth <span className="text-red-400">*</span>
                                    </Label>
                                    <Input
                                        id="date_of_birth"
                                        type="date"
                                        value={data.date_of_birth}
                                        onChange={(e) => setData('date_of_birth', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                        required
                                    />
                                    {errors.date_of_birth && (
                                        <p className="text-sm text-red-400">{errors.date_of_birth}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="place_of_birth" className="text-white/80">
                                        Place of Birth
                                    </Label>
                                    <Input
                                        id="place_of_birth"
                                        value={data.place_of_birth}
                                        onChange={(e) => setData('place_of_birth', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="gender" className="text-white/80">
                                        Gender <span className="text-red-400">*</span>
                                    </Label>
                                    <select
                                        id="gender"
                                        value={data.gender}
                                        onChange={(e) => setData('gender', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                        required
                                    >
                                        <option value="">Select gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    {errors.gender && (
                                        <p className="text-sm text-red-400">{errors.gender}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="nationality" className="text-white/80">
                                        Nationality
                                    </Label>
                                    <Input
                                        id="nationality"
                                        value={data.nationality}
                                        onChange={(e) => setData('nationality', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="student_type" className="text-white/80">
                                        Student Type
                                    </Label>
                                    <select
                                        id="student_type"
                                        value={data.student_type}
                                        onChange={(e) => setData('student_type', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    >
                                        <option value="regular">Regular</option>
                                        <option value="special">Special</option>
                                    </select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Contact Information */}
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-white">
                                Contact Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="phone" className="text-white/80">
                                        Phone
                                    </Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email" className="text-white/80">
                                        Email
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="current_address" className="text-white/80">
                                        Current Address
                                    </Label>
                                    <Textarea
                                        id="current_address"
                                        value={data.current_address}
                                        onChange={(e) => setData('current_address', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="permanent_address" className="text-white/80">
                                        Permanent Address
                                    </Label>
                                    <Textarea
                                        id="permanent_address"
                                        value={data.permanent_address}
                                        onChange={(e) => setData('permanent_address', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Parent/Guardian Information */}
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-white">
                                Parent/Guardian Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="parent_name" className="text-white/80">
                                        Parent/Guardian Name
                                    </Label>
                                    <Input
                                        id="parent_name"
                                        value={data.parent_name}
                                        onChange={(e) => setData('parent_name', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="parent_phone" className="text-white/80">
                                        Parent Phone
                                    </Label>
                                    <Input
                                        id="parent_phone"
                                        value={data.parent_phone}
                                        onChange={(e) => setData('parent_phone', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="parent_occupation" className="text-white/80">
                                        Parent Occupation
                                    </Label>
                                    <Input
                                        id="parent_occupation"
                                        value={data.parent_occupation}
                                        onChange={(e) => setData('parent_occupation', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="emergency_contact" className="text-white/80">
                                        Emergency Contact
                                    </Label>
                                    <Input
                                        id="emergency_contact"
                                        value={data.emergency_contact}
                                        onChange={(e) => setData('emergency_contact', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="emergency_contact_relationship" className="text-white/80">
                                        Emergency Contact Relationship
                                    </Label>
                                    <Input
                                        id="emergency_contact_relationship"
                                        value={data.emergency_contact_relationship}
                                        onChange={(e) =>
                                            setData('emergency_contact_relationship', e.target.value)
                                        }
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Academic Information */}
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardHeader>
                            <CardTitle className="text-lg font-semibold text-white">
                                Academic Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="class_id" className="text-white/80">
                                        Class
                                    </Label>
                                    <select
                                        id="class_id"
                                        value={data.class_id}
                                        onChange={(e) => setData('class_id', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    >
                                        <option value="">Select class</option>
                                        {classrooms?.map((classroom) => (
                                            <option key={classroom.id} value={classroom.id}>
                                                {classroom.name_en}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="shift" className="text-white/80">
                                        Shift
                                    </Label>
                                    <select
                                        id="shift"
                                        value={data.shift}
                                        onChange={(e) => setData('shift', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    >
                                        <option value="">Select shift</option>
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">Afternoon</option>
                                        <option value="evening">Evening</option>
                                    </select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="academic_year" className="text-white/80">
                                        Academic Year
                                    </Label>
                                    <Input
                                        id="academic_year"
                                        value={data.academic_year}
                                        onChange={(e) => setData('academic_year', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="registration_date" className="text-white/80">
                                        Registration Date
                                    </Label>
                                    <Input
                                        id="registration_date"
                                        type="date"
                                        value={data.registration_date}
                                        onChange={(e) => setData('registration_date', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="previous_school" className="text-white/80">
                                        Previous School
                                    </Label>
                                    <Input
                                        id="previous_school"
                                        value={data.previous_school}
                                        onChange={(e) => setData('previous_school', e.target.value)}
                                        className="bg-[#0a0a0a] border-white/10 text-white"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status" className="text-white/80">
                                        Status
                                    </Label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="graduated">Graduated</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="notes" className="text-white/80">
                                    Notes
                                </Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="bg-[#0a0a0a] border-white/10 text-white"
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Form Actions */}
                    <div className="flex items-center justify-end gap-4">
                        <Link href="/students">
                            <Button
                                type="button"
                                variant="ghost"
                                className="text-white/60 hover:text-white"
                            >
                                Cancel
                            </Button>
                        </Link>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="bg-white text-black hover:bg-white/90"
                        >
                            {processing ? 'Updating...' : 'Update Student'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
