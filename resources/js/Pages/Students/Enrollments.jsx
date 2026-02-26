import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
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

export default function StudentEnrollments({ auth, student, enrollments, subjects, teachers }) {
    const [addDialog, setAddDialog] = useState(false);
    const [editDialog, setEditDialog] = useState({ open: false, enrollment: null });
    const [deleteDialog, setDeleteDialog] = useState({ open: false, enrollment: null });

    const { data: addData, setData: setAddData, post, processing: addProcessing, errors: addErrors, reset: resetAdd } = useForm({
        subject_id: '',
        teacher_id: '',
    });

    const { data: editData, setData: setEditData, put, processing: editProcessing, errors: editErrors } = useForm({
        grade: '',
        status: '',
        remarks: '',
    });

    const handleAddEnrollment = (e) => {
        e.preventDefault();
        post(`/students/${student.uuid}/enrollments`, {
            onSuccess: () => {
                toast.success('Enrollment added successfully');
                setAddDialog(false);
                resetAdd();
            },
            onError: () => {
                toast.error('Failed to add enrollment');
            },
        });
    };

    const handleUpdateEnrollment = (e) => {
        e.preventDefault();
        if (!editDialog.enrollment) return;

        put(`/students/${student.uuid}/enrollments/${editDialog.enrollment.id}`, {
            onSuccess: () => {
                toast.success('Enrollment updated successfully');
                setEditDialog({ open: false, enrollment: null });
            },
            onError: () => {
                toast.error('Failed to update enrollment');
            },
        });
    };

    const handleDeleteEnrollment = () => {
        if (!deleteDialog.enrollment) return;

        router.delete(`/students/${student.uuid}/enrollments/${deleteDialog.enrollment.id}`, {
            onSuccess: () => {
                toast.success('Enrollment removed successfully');
                setDeleteDialog({ open: false, enrollment: null });
            },
            onError: () => {
                toast.error('Failed to remove enrollment');
            },
        });
    };

    const openEditDialog = (enrollment) => {
        setEditData({
            grade: enrollment.grade || '',
            status: enrollment.status || 'active',
            remarks: enrollment.remarks || '',
        });
        setEditDialog({ open: true, enrollment });
    };

    const getStatusBadge = (status) => {
        const variants = {
            active: 'bg-green-600/20 text-green-400 border-green-600/30',
            completed: 'bg-blue-600/20 text-blue-400 border-blue-600/30',
            dropped: 'bg-red-600/20 text-red-400 border-red-600/30',
            pending: 'bg-yellow-600/20 text-yellow-400 border-yellow-600/30',
        };

        return (
            <Badge className={`${variants[status] || variants.active} border capitalize`}>
                {status}
            </Badge>
        );
    };

    const getGradeColor = (grade) => {
        if (!grade) return 'text-white/40';
        const numGrade = parseFloat(grade);
        if (numGrade >= 90) return 'text-green-400';
        if (numGrade >= 80) return 'text-blue-400';
        if (numGrade >= 70) return 'text-yellow-400';
        if (numGrade >= 60) return 'text-orange-400';
        return 'text-red-400';
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${student.first_name} ${student.last_name} - Enrollments`} />

            <div className="p-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-white">
                            Student Enrollments
                        </h1>
                        <p className="text-sm text-white/60 mt-1">
                            {student.first_name} {student.last_name} ({student.student_code})
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            onClick={() => setAddDialog(true)}
                            className="bg-white text-black hover:bg-white/90"
                        >
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            Add Enrollment
                        </Button>
                        <Link href={`/students/${student.uuid}/transcript`}>
                            <Button variant="outline" className="bg-white/5 border-white/10 text-white">
                                View Transcript
                            </Button>
                        </Link>
                        <Link href={`/students/${student.uuid}`}>
                            <Button variant="ghost" className="text-white/60 hover:text-white">
                                Back to Student
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-3xl font-bold text-white">
                                    {enrollments?.length || 0}
                                </p>
                                <p className="text-sm text-white/60 mt-1">Total Subjects</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-3xl font-bold text-green-400">
                                    {enrollments?.filter(e => e.status === 'active').length || 0}
                                </p>
                                <p className="text-sm text-white/60 mt-1">Active</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-3xl font-bold text-blue-400">
                                    {enrollments?.filter(e => e.status === 'completed').length || 0}
                                </p>
                                <p className="text-sm text-white/60 mt-1">Completed</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="bg-[#1a1a1a] border-white/10">
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <p className="text-3xl font-bold text-white">
                                    {enrollments?.filter(e => e.grade).length > 0
                                        ? (enrollments.filter(e => e.grade).reduce((acc, e) => acc + parseFloat(e.grade), 0) /
                                          enrollments.filter(e => e.grade).length).toFixed(2)
                                        : 'N/A'}
                                </p>
                                <p className="text-sm text-white/60 mt-1">Average Grade</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Enrollments Table */}
                <Card className="bg-[#1a1a1a] border-white/10">
                    <CardHeader>
                        <CardTitle className="text-lg font-semibold text-white">
                            Current Enrollments
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {enrollments?.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Subject Code</TableHead>
                                        <TableHead>Subject Name</TableHead>
                                        <TableHead>Teacher</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Remarks</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {enrollments.map((enrollment) => (
                                        <TableRow key={enrollment.id}>
                                            <TableCell className="font-medium">
                                                {enrollment.subject?.subject_code}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium text-white">
                                                        {enrollment.subject?.name_en}
                                                    </p>
                                                    {enrollment.subject?.name_kh && (
                                                        <p className="text-sm text-white/60">
                                                            {enrollment.subject?.name_kh}
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {enrollment.teacher?.first_name}{' '}
                                                {enrollment.teacher?.last_name}
                                            </TableCell>
                                            <TableCell>
                                                <span className={`font-semibold ${getGradeColor(enrollment.grade)}`}>
                                                    {enrollment.grade || 'N/A'}
                                                </span>
                                            </TableCell>
                                            <TableCell>{getStatusBadge(enrollment.status)}</TableCell>
                                            <TableCell className="max-w-xs truncate">
                                                {enrollment.remarks || '-'}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => openEditDialog(enrollment)}
                                                        className="text-white/60 hover:text-white hover:bg-white/5"
                                                    >
                                                        Edit Grade
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setDeleteDialog({ open: true, enrollment })
                                                        }
                                                        className="text-red-400 hover:text-red-300 hover:bg-red-600/10"
                                                    >
                                                        Remove
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="text-center py-12">
                                <svg
                                    className="mx-auto h-12 w-12 text-white/40"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-white">
                                    No enrollments yet
                                </h3>
                                <p className="mt-1 text-sm text-white/60">
                                    Get started by adding a subject enrollment for this student.
                                </p>
                                <div className="mt-6">
                                    <Button
                                        onClick={() => setAddDialog(true)}
                                        className="bg-white text-black hover:bg-white/90"
                                    >
                                        Add Enrollment
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Add Enrollment Dialog */}
            <Dialog open={addDialog} onOpenChange={setAddDialog}>
                <DialogContent>
                    <form onSubmit={handleAddEnrollment}>
                        <DialogHeader>
                            <DialogTitle>Add Enrollment</DialogTitle>
                            <DialogDescription>
                                Enroll {student.first_name} {student.last_name} in a new subject
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="subject_id" className="text-white/80">
                                    Subject <span className="text-red-400">*</span>
                                </Label>
                                <select
                                    id="subject_id"
                                    value={addData.subject_id}
                                    onChange={(e) => setAddData('subject_id', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    required
                                >
                                    <option value="">Select subject</option>
                                    {subjects?.map((subject) => (
                                        <option key={subject.id} value={subject.id}>
                                            {subject.subject_code} - {subject.name_en}
                                        </option>
                                    ))}
                                </select>
                                {addErrors.subject_id && (
                                    <p className="text-sm text-red-400">{addErrors.subject_id}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="teacher_id" className="text-white/80">
                                    Teacher <span className="text-red-400">*</span>
                                </Label>
                                <select
                                    id="teacher_id"
                                    value={addData.teacher_id}
                                    onChange={(e) => setAddData('teacher_id', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                    required
                                >
                                    <option value="">Select teacher</option>
                                    {teachers?.map((teacher) => (
                                        <option key={teacher.id} value={teacher.id}>
                                            {teacher.first_name} {teacher.last_name}
                                        </option>
                                    ))}
                                </select>
                                {addErrors.teacher_id && (
                                    <p className="text-sm text-red-400">{addErrors.teacher_id}</p>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setAddDialog(false)}
                                className="text-white/60 hover:text-white hover:bg-white/5"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={addProcessing}
                                className="bg-white text-black hover:bg-white/90"
                            >
                                {addProcessing ? 'Adding...' : 'Add Enrollment'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Grade Dialog */}
            <Dialog
                open={editDialog.open}
                onOpenChange={(open) => setEditDialog({ open, enrollment: null })}
            >
                <DialogContent>
                    <form onSubmit={handleUpdateEnrollment}>
                        <DialogHeader>
                            <DialogTitle>Edit Enrollment</DialogTitle>
                            <DialogDescription>
                                Update grade and status for {editDialog.enrollment?.subject?.name_en}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="grade" className="text-white/80">
                                    Grade
                                </Label>
                                <Input
                                    id="grade"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={editData.grade}
                                    onChange={(e) => setEditData('grade', e.target.value)}
                                    className="bg-[#0a0a0a] border-white/10 text-white"
                                    placeholder="Enter grade (0-100)"
                                />
                                {editErrors.grade && (
                                    <p className="text-sm text-red-400">{editErrors.grade}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="status" className="text-white/80">
                                    Status
                                </Label>
                                <select
                                    id="status"
                                    value={editData.status}
                                    onChange={(e) => setEditData('status', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-white/10 bg-[#0a0a0a] px-3 py-2 text-sm text-white"
                                >
                                    <option value="active">Active</option>
                                    <option value="completed">Completed</option>
                                    <option value="dropped">Dropped</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="remarks" className="text-white/80">
                                    Remarks
                                </Label>
                                <Textarea
                                    id="remarks"
                                    value={editData.remarks}
                                    onChange={(e) => setEditData('remarks', e.target.value)}
                                    className="bg-[#0a0a0a] border-white/10 text-white"
                                    placeholder="Add any notes or comments..."
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => setEditDialog({ open: false, enrollment: null })}
                                className="text-white/60 hover:text-white hover:bg-white/5"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={editProcessing}
                                className="bg-white text-black hover:bg-white/90"
                            >
                                {editProcessing ? 'Updating...' : 'Update Enrollment'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={deleteDialog.open}
                onOpenChange={(open) => setDeleteDialog({ open, enrollment: null })}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Remove Enrollment</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove{' '}
                            <span className="font-semibold text-white">
                                {deleteDialog.enrollment?.subject?.name_en}
                            </span>{' '}
                            from {student.first_name}'s enrollments? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="ghost"
                            onClick={() => setDeleteDialog({ open: false, enrollment: null })}
                            className="text-white/60 hover:text-white hover:bg-white/5"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleDeleteEnrollment}
                            className="bg-red-600 hover:bg-red-700 text-white"
                        >
                            Remove
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
