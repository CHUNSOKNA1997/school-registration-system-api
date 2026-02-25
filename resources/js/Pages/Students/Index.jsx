import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { toast } from 'sonner';
import { Users, Plus, Search, X, Eye, Edit, Trash2, GraduationCap } from 'lucide-react';

export default function StudentsIndex({ auth, students, filters, classrooms }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [deleteDialog, setDeleteDialog] = useState({ open: false, student: null });
    const [createDialog, setCreateDialog] = useState(false);
    const [editDialog, setEditDialog] = useState({ open: false, student: null });

    // Create form
    const createForm = useForm({
        first_name: '',
        last_name: '',
        khmer_name: '',
        date_of_birth: '',
        place_of_birth: '',
        gender: '',
        student_type: 'regular',
        nationality: 'Cambodian',
        phone: '',
        email: '',
        current_address: '',
        permanent_address: '',
        parent_name: '',
        parent_phone: '',
        parent_occupation: '',
        emergency_contact: '',
        emergency_contact_relationship: '',
        class_id: '',
        shift: '',
        registration_date: new Date().toISOString().split('T')[0],
        academic_year: new Date().getFullYear().toString(),
        previous_school: '',
        status: 'active',
        notes: '',
    });

    // Edit form
    const editForm = useForm({
        first_name: '',
        last_name: '',
        khmer_name: '',
        date_of_birth: '',
        place_of_birth: '',
        gender: '',
        student_type: '',
        nationality: '',
        phone: '',
        email: '',
        current_address: '',
        permanent_address: '',
        parent_name: '',
        parent_phone: '',
        parent_occupation: '',
        emergency_contact: '',
        emergency_contact_relationship: '',
        class_id: '',
        shift: '',
        registration_date: '',
        academic_year: '',
        previous_school: '',
        status: '',
        notes: '',
    });

    // Load edit form data when student is selected
    useEffect(() => {
        if (editDialog.student) {
            editForm.setData({
                first_name: editDialog.student.first_name || '',
                last_name: editDialog.student.last_name || '',
                khmer_name: editDialog.student.khmer_name || '',
                date_of_birth: editDialog.student.date_of_birth || '',
                place_of_birth: editDialog.student.place_of_birth || '',
                gender: editDialog.student.gender || '',
                student_type: editDialog.student.student_type || '',
                nationality: editDialog.student.nationality || '',
                phone: editDialog.student.phone || '',
                email: editDialog.student.email || '',
                current_address: editDialog.student.current_address || '',
                permanent_address: editDialog.student.permanent_address || '',
                parent_name: editDialog.student.parent_name || '',
                parent_phone: editDialog.student.parent_phone || '',
                parent_occupation: editDialog.student.parent_occupation || '',
                emergency_contact: editDialog.student.emergency_contact || '',
                emergency_contact_relationship: editDialog.student.emergency_contact_relationship || '',
                class_id: editDialog.student.class_id || '',
                shift: editDialog.student.shift || '',
                registration_date: editDialog.student.registration_date || '',
                academic_year: editDialog.student.academic_year || '',
                previous_school: editDialog.student.previous_school || '',
                status: editDialog.student.status || '',
                notes: editDialog.student.notes || '',
            });
        }
    }, [editDialog.student]);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/students', { search }, { preserveState: true });
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/students', {
            onSuccess: () => {
                toast.success('Student created successfully');
                setCreateDialog(false);
                createForm.reset();
            },
            onError: () => {
                toast.error('Failed to create student');
            },
        });
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        editForm.put(`/students/${editDialog.student.id}`, {
            onSuccess: () => {
                toast.success('Student updated successfully');
                setEditDialog({ open: false, student: null });
            },
            onError: () => {
                toast.error('Failed to update student');
            },
        });
    };

    const handleDelete = () => {
        if (!deleteDialog.student) return;

        router.delete(`/students/${deleteDialog.student.id}`, {
            onSuccess: () => {
                toast.success('Student deleted successfully');
                setDeleteDialog({ open: false, student: null });
            },
            onError: () => {
                toast.error('Failed to delete student');
            },
        });
    };

    const getStatusBadge = (status) => {
        const variants = {
            active: 'bg-gradient-to-r from-emerald-500/20 to-green-500/20 text-emerald-400 border-emerald-500/30',
            inactive: 'bg-gradient-to-r from-gray-500/20 to-slate-500/20 text-gray-400 border-gray-500/30',
            graduated: 'bg-gradient-to-r from-blue-500/20 to-cyan-500/20 text-blue-400 border-blue-500/30',
            suspended: 'bg-gradient-to-r from-red-500/20 to-rose-500/20 text-red-400 border-red-500/30',
        };

        return (
            <Badge className={`${variants[status] || variants.active} border backdrop-blur-sm transition-all duration-300 hover:scale-105`}>
                {status?.charAt(0).toUpperCase() + status?.slice(1)}
            </Badge>
        );
    };

    // Student Form Component (reusable for Create & Edit)
    const StudentForm = ({ form, isEdit = false }) => (
        <div className="space-y-6 max-h-[70vh] overflow-y-auto px-1">
            {/* Personal Information */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold text-white/80 flex items-center gap-2">
                    <Users className="w-4 h-4" />
                    Personal Information
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="first_name" className="text-white/80">
                            First Name <span className="text-red-400">*</span>
                        </Label>
                        <Input
                            id="first_name"
                            value={form.data.first_name}
                            onChange={(e) => form.setData('first_name', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            required
                        />
                        {form.errors.first_name && (
                            <p className="text-sm text-red-400">{form.errors.first_name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="last_name" className="text-white/80">
                            Last Name <span className="text-red-400">*</span>
                        </Label>
                        <Input
                            id="last_name"
                            value={form.data.last_name}
                            onChange={(e) => form.setData('last_name', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            required
                        />
                        {form.errors.last_name && (
                            <p className="text-sm text-red-400">{form.errors.last_name}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="khmer_name" className="text-white/80">
                            Khmer Name
                        </Label>
                        <Input
                            id="khmer_name"
                            value={form.data.khmer_name}
                            onChange={(e) => form.setData('khmer_name', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="date_of_birth" className="text-white/80">
                            Date of Birth <span className="text-red-400">*</span>
                        </Label>
                        <Input
                            id="date_of_birth"
                            type="date"
                            value={form.data.date_of_birth}
                            onChange={(e) => form.setData('date_of_birth', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            required
                        />
                        {form.errors.date_of_birth && (
                            <p className="text-sm text-red-400">{form.errors.date_of_birth}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="place_of_birth" className="text-white/80">
                            Place of Birth
                        </Label>
                        <Input
                            id="place_of_birth"
                            value={form.data.place_of_birth}
                            onChange={(e) => form.setData('place_of_birth', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="gender" className="text-white/80">
                            Gender <span className="text-red-400">*</span>
                        </Label>
                        <select
                            id="gender"
                            value={form.data.gender}
                            onChange={(e) => form.setData('gender', e.target.value)}
                            className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                            required
                        >
                            <option value="">Select gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        {form.errors.gender && (
                            <p className="text-sm text-red-400">{form.errors.gender}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="nationality" className="text-white/80">
                            Nationality
                        </Label>
                        <Input
                            id="nationality"
                            value={form.data.nationality}
                            onChange={(e) => form.setData('nationality', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="student_type" className="text-white/80">
                            Student Type
                        </Label>
                        <select
                            id="student_type"
                            value={form.data.student_type}
                            onChange={(e) => form.setData('student_type', e.target.value)}
                            className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                        >
                            <option value="regular">Regular</option>
                            <option value="special">Special</option>
                        </select>
                    </div>
                </div>
            </div>

            {/* Contact Information */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold text-white/80">Contact Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="phone" className="text-white/80">
                            Phone
                        </Label>
                        <Input
                            id="phone"
                            value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)}
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
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="current_address" className="text-white/80">
                            Current Address
                        </Label>
                        <Textarea
                            id="current_address"
                            value={form.data.current_address}
                            onChange={(e) => form.setData('current_address', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            rows={2}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="permanent_address" className="text-white/80">
                            Permanent Address
                        </Label>
                        <Textarea
                            id="permanent_address"
                            value={form.data.permanent_address}
                            onChange={(e) => form.setData('permanent_address', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            rows={2}
                        />
                    </div>
                </div>
            </div>

            {/* Parent/Guardian Information */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold text-white/80">Parent/Guardian Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="parent_name" className="text-white/80">
                            Parent/Guardian Name
                        </Label>
                        <Input
                            id="parent_name"
                            value={form.data.parent_name}
                            onChange={(e) => form.setData('parent_name', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="parent_phone" className="text-white/80">
                            Parent Phone
                        </Label>
                        <Input
                            id="parent_phone"
                            value={form.data.parent_phone}
                            onChange={(e) => form.setData('parent_phone', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="parent_occupation" className="text-white/80">
                            Parent Occupation
                        </Label>
                        <Input
                            id="parent_occupation"
                            value={form.data.parent_occupation}
                            onChange={(e) => form.setData('parent_occupation', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="emergency_contact" className="text-white/80">
                            Emergency Contact
                        </Label>
                        <Input
                            id="emergency_contact"
                            value={form.data.emergency_contact}
                            onChange={(e) => form.setData('emergency_contact', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="emergency_contact_relationship" className="text-white/80">
                            Emergency Contact Relationship
                        </Label>
                        <Input
                            id="emergency_contact_relationship"
                            value={form.data.emergency_contact_relationship}
                            onChange={(e) => form.setData('emergency_contact_relationship', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>
                </div>
            </div>

            {/* Academic Information */}
            <div className="space-y-4">
                <h3 className="text-sm font-semibold text-white/80">Academic Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label htmlFor="class_id" className="text-white/80">
                            Class
                        </Label>
                        <select
                            id="class_id"
                            value={form.data.class_id}
                            onChange={(e) => form.setData('class_id', e.target.value)}
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
                            value={form.data.shift}
                            onChange={(e) => form.setData('shift', e.target.value)}
                            className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                        >
                            <option value="">Select shift</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="evening">Evening</option>
                        </select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="registration_date" className="text-white/80">
                            Registration Date
                        </Label>
                        <Input
                            id="registration_date"
                            type="date"
                            value={form.data.registration_date}
                            onChange={(e) => form.setData('registration_date', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="academic_year" className="text-white/80">
                            Academic Year
                        </Label>
                        <Input
                            id="academic_year"
                            value={form.data.academic_year}
                            onChange={(e) => form.setData('academic_year', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="previous_school" className="text-white/80">
                            Previous School
                        </Label>
                        <Input
                            id="previous_school"
                            value={form.data.previous_school}
                            onChange={(e) => form.setData('previous_school', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status" className="text-white/80">
                            Status
                        </Label>
                        <select
                            id="status"
                            value={form.data.status}
                            onChange={(e) => form.setData('status', e.target.value)}
                            className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                        >
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="graduated">Graduated</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>

                    <div className="space-y-2 md:col-span-2">
                        <Label htmlFor="notes" className="text-white/80">
                            Notes
                        </Label>
                        <Textarea
                            id="notes"
                            value={form.data.notes}
                            onChange={(e) => form.setData('notes', e.target.value)}
                            className="bg-[#0a0a0a] border-white/10 text-white"
                            rows={3}
                        />
                    </div>
                </div>
            </div>
        </div>
    );

    return (
        <AuthenticatedLayout>
            <Head title="Students" />

            <div className="min-h-screen bg-gradient-to-br from-[#0a0a0a] via-[#111111] to-[#0a0a0a]">
                <div className="p-8 space-y-8">
                    {/* Header with gradient */}
                    <div className="relative">
                        <div className="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-pink-500/10 blur-3xl" />
                        <div className="relative flex items-center justify-between">
                            <div className="space-y-2">
                                <div className="flex items-center gap-3">
                                    <div className="p-3 rounded-xl bg-gradient-to-br from-blue-500/20 to-purple-500/20 backdrop-blur-sm border border-white/10">
                                        <Users className="w-6 h-6 text-blue-400" />
                                    </div>
                                    <div>
                                        <h1 className="text-3xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent">
                                            Students
                                        </h1>
                                        <p className="text-sm text-white/50 mt-1">
                                            Manage student records and enrollments
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <Button 
                                onClick={() => setCreateDialog(true)}
                                className="group relative bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white border-0 shadow-lg shadow-blue-500/25 transition-all duration-300 hover:scale-105 hover:shadow-xl hover:shadow-blue-500/40"
                            >
                                <Plus className="w-4 h-4 mr-2 transition-transform group-hover:rotate-90 duration-300" />
                                Add Student
                            </Button>
                        </div>
                    </div>

                    {/* Search Card with glass effect */}
                    <Card className="bg-white/5 border-white/10 backdrop-blur-xl shadow-2xl transition-all duration-300 hover:bg-white/[0.07] hover:border-white/20">
                        <CardContent className="p-6">
                            <form onSubmit={handleSearch} className="flex gap-3">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
                                    <Input
                                        placeholder="Search by name, student code, or email..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10 bg-white/5 border-white/10 text-white placeholder:text-white/40 focus:bg-white/10 focus:border-blue-500/50 transition-all duration-300"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    className="bg-white/10 hover:bg-white/20 border-white/10 text-white transition-all duration-300"
                                >
                                    <Search className="w-4 h-4" />
                                </Button>
                                {search && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => {
                                            setSearch('');
                                            router.get('/students');
                                        }}
                                        className="text-white/60 hover:text-white hover:bg-white/10 transition-all duration-300"
                                    >
                                        <X className="w-4 h-4" />
                                    </Button>
                                )}
                            </form>
                        </CardContent>
                    </Card>

                    {/* Students Table */}
                    <Card className="bg-white/5 border-white/10 backdrop-blur-xl shadow-2xl overflow-hidden">
                        <CardHeader className="border-b border-white/10 bg-gradient-to-r from-white/5 to-transparent">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-xl font-semibold text-white flex items-center gap-2">
                                    <GraduationCap className="w-5 h-5 text-blue-400" />
                                    All Students
                                    <Badge className="ml-2 bg-blue-500/20 text-blue-300 border-blue-500/30">
                                        {students?.data?.length || 0}
                                    </Badge>
                                </CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0">
                            {students?.data?.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow className="border-white/10 hover:bg-white/5">
                                                <TableHead className="text-white/70 font-semibold">Student Code</TableHead>
                                                <TableHead className="text-white/70 font-semibold">Name</TableHead>
                                                <TableHead className="text-white/70 font-semibold">Gender</TableHead>
                                                <TableHead className="text-white/70 font-semibold">Class</TableHead>
                                                <TableHead className="text-white/70 font-semibold">Phone</TableHead>
                                                <TableHead className="text-white/70 font-semibold">Status</TableHead>
                                                <TableHead className="text-white/70 font-semibold text-right">Actions</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {students.data.map((student) => (
                                                <TableRow 
                                                    key={student.id}
                                                    className="border-white/10 hover:bg-white/5 transition-all duration-200 group"
                                                >
                                                    <TableCell className="font-mono text-blue-400 font-medium">
                                                        {student.student_code}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="space-y-1">
                                                            <p className="font-medium text-white group-hover:text-blue-300 transition-colors">
                                                                {student.first_name} {student.last_name}
                                                            </p>
                                                            {student.khmer_name && (
                                                                <p className="text-sm text-white/50">
                                                                    {student.khmer_name}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="capitalize text-white/80">{student.gender}</span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="text-white/80">
                                                            {student.class?.name_en || 'N/A'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell>
                                                        <span className="text-white/60">{student.phone || 'N/A'}</span>
                                                    </TableCell>
                                                    <TableCell>{getStatusBadge(student.status)}</TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => router.get(`/students/${student.id}`)}
                                                                className="text-white/60 hover:text-blue-400 hover:bg-blue-500/10 transition-all duration-200"
                                                            >
                                                                <Eye className="w-4 h-4" />
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => setEditDialog({ open: true, student })}
                                                                className="text-white/60 hover:text-purple-400 hover:bg-purple-500/10 transition-all duration-200"
                                                            >
                                                                <Edit className="w-4 h-4" />
                                                            </Button>
                                                            {auth.user?.is_admin && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        setDeleteDialog({
                                                                            open: true,
                                                                            student,
                                                                        })
                                                                    }
                                                                    className="text-white/60 hover:text-red-400 hover:bg-red-500/10 transition-all duration-200"
                                                                >
                                                                    <Trash2 className="w-4 h-4" />
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            ) : (
                                <div className="text-center py-16">
                                    <div className="inline-flex p-6 rounded-2xl bg-gradient-to-br from-blue-500/10 to-purple-500/10 mb-4">
                                        <Users className="w-16 h-16 text-white/30" />
                                    </div>
                                    <h3 className="mt-4 text-lg font-medium text-white">No students found</h3>
                                    <p className="mt-2 text-sm text-white/50 max-w-sm mx-auto">
                                        Get started by adding a new student to the system.
                                    </p>
                                    <div className="mt-8">
                                        <Button 
                                            onClick={() => setCreateDialog(true)}
                                            className="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white shadow-lg shadow-blue-500/25 transition-all duration-300 hover:scale-105"
                                        >
                                            <Plus className="w-4 h-4 mr-2" />
                                            Add Student
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Pagination */}
                    {students?.links && students.links.length > 3 && (
                        <div className="flex items-center justify-center gap-2">
                            {students.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => link.url && router.get(link.url)}
                                    disabled={!link.url}
                                    className={
                                        link.active
                                            ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white border-0 shadow-lg shadow-blue-500/25'
                                            : 'bg-white/5 border-white/10 text-white/60 hover:bg-white/10 hover:text-white transition-all duration-200'
                                    }
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Create Student Dialog */}
            <Dialog open={createDialog} onOpenChange={setCreateDialog}>
                <DialogContent className="bg-[#1a1a1a] border-white/10 backdrop-blur-xl max-w-4xl">
                    <DialogHeader>
                        <DialogTitle className="text-xl text-white flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-blue-500/20">
                                <Plus className="w-5 h-5 text-blue-400" />
                            </div>
                            Create New Student
                        </DialogTitle>
                        <DialogDescription className="text-white/60">
                            Add a new student to the system. Fill in the required information below.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleCreateSubmit}>
                        <StudentForm form={createForm} />
                        <DialogFooter className="mt-6">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setCreateDialog(false)}
                                className="text-white/60 hover:text-white hover:bg-white/5 transition-all duration-200"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={createForm.processing}
                                className="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white shadow-lg shadow-blue-500/25 transition-all duration-300 hover:scale-105"
                            >
                                {createForm.processing ? 'Creating...' : 'Create Student'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Student Dialog */}
            <Dialog open={editDialog.open} onOpenChange={(open) => setEditDialog({ open, student: null })}>
                <DialogContent className="bg-[#1a1a1a] border-white/10 backdrop-blur-xl max-w-4xl">
                    <DialogHeader>
                        <DialogTitle className="text-xl text-white flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-purple-500/20">
                                <Edit className="w-5 h-5 text-purple-400" />
                            </div>
                            Edit Student
                        </DialogTitle>
                        <DialogDescription className="text-white/60">
                            Update student information for {editDialog.student?.first_name} {editDialog.student?.last_name}
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={handleEditSubmit}>
                        <StudentForm form={editForm} isEdit={true} />
                        <DialogFooter className="mt-6">
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setEditDialog({ open: false, student: null })}
                                className="text-white/60 hover:text-white hover:bg-white/5 transition-all duration-200"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editForm.processing}
                                className="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white shadow-lg shadow-purple-500/25 transition-all duration-300 hover:scale-105"
                            >
                                {editForm.processing ? 'Updating...' : 'Update Student'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={deleteDialog.open}
                onOpenChange={(open) => setDeleteDialog({ open, student: null })}
            >
                <DialogContent className="bg-[#1a1a1a] border-white/10 backdrop-blur-xl">
                    <DialogHeader>
                        <DialogTitle className="text-xl text-white flex items-center gap-2">
                            <div className="p-2 rounded-lg bg-red-500/20">
                                <Trash2 className="w-5 h-5 text-red-400" />
                            </div>
                            Delete Student
                        </DialogTitle>
                        <DialogDescription className="text-white/60">
                            Are you sure you want to delete{' '}
                            <span className="font-semibold text-white">
                                {deleteDialog.student?.first_name}{' '}
                                {deleteDialog.student?.last_name}
                            </span>
                            ? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setDeleteDialog({ open: false, student: null })}
                            className="text-white/60 hover:text-white hover:bg-white/5 transition-all duration-200"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleDelete}
                            className="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white shadow-lg shadow-red-500/25 transition-all duration-300 hover:scale-105"
                        >
                            <Trash2 className="w-4 h-4 mr-2" />
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
