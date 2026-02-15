import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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

export default function StudentsIndex({ auth, students, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [deleteDialog, setDeleteDialog] = useState({ open: false, student: null });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/students', { search }, { preserveState: true });
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
            active: 'bg-green-600/20 text-green-400 border-green-600/30',
            inactive: 'bg-gray-600/20 text-gray-400 border-gray-600/30',
            graduated: 'bg-blue-600/20 text-blue-400 border-blue-600/30',
            suspended: 'bg-red-600/20 text-red-400 border-red-600/30',
        };

        return (
            <Badge className={`${variants[status] || variants.active} border`}>
                {status?.charAt(0).toUpperCase() + status?.slice(1)}
            </Badge>
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Students" />

            <div className="p-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-white">Students</h1>
                        <p className="text-sm text-white/60 mt-1">
                            Manage student records and enrollments
                        </p>
                    </div>
                    <Link href="/students/create">
                        <Button className="bg-white text-black hover:bg-white/90">
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
                            Add Student
                        </Button>
                    </Link>
                </div>

                {/* Search and Filters */}
                <Card className="bg-[#1a1a1a] border-white/10">
                    <CardContent className="p-4">
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <Input
                                placeholder="Search by name, student code, or email..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="flex-1 bg-[#0a0a0a] border-white/10 text-white"
                            />
                            <Button
                                type="submit"
                                variant="outline"
                                className="bg-white/5 border-white/10 text-white hover:bg-white/10"
                            >
                                Search
                            </Button>
                            {search && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() => {
                                        setSearch('');
                                        router.get('/students');
                                    }}
                                    className="text-white/60 hover:text-white"
                                >
                                    Clear
                                </Button>
                            )}
                        </form>
                    </CardContent>
                </Card>

                {/* Students Table */}
                <Card className="bg-[#1a1a1a] border-white/10">
                    <CardHeader>
                        <CardTitle className="text-lg font-semibold text-white">
                            All Students ({students?.data?.length || 0})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {students?.data?.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Student Code</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Gender</TableHead>
                                        <TableHead>Class</TableHead>
                                        <TableHead>Phone</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.data.map((student) => (
                                        <TableRow key={student.id}>
                                            <TableCell className="font-medium">
                                                {student.student_code}
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <p className="font-medium text-white">
                                                        {student.first_name} {student.last_name}
                                                    </p>
                                                    {student.khmer_name && (
                                                        <p className="text-sm text-white/60">
                                                            {student.khmer_name}
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell className="capitalize">
                                                {student.gender}
                                            </TableCell>
                                            <TableCell>
                                                {student.class?.name_en || 'N/A'}
                                            </TableCell>
                                            <TableCell>{student.phone || 'N/A'}</TableCell>
                                            <TableCell>{getStatusBadge(student.status)}</TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link href={`/students/${student.id}`}>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-white/60 hover:text-white hover:bg-white/5"
                                                        >
                                                            View
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/students/${student.id}/edit`}>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-white/60 hover:text-white hover:bg-white/5"
                                                        >
                                                            Edit
                                                        </Button>
                                                    </Link>
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
                                                            className="text-red-400 hover:text-red-300 hover:bg-red-600/10"
                                                        >
                                                            Delete
                                                        </Button>
                                                    )}
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
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                                <h3 className="mt-2 text-sm font-medium text-white">
                                    No students found
                                </h3>
                                <p className="mt-1 text-sm text-white/60">
                                    Get started by adding a new student.
                                </p>
                                <div className="mt-6">
                                    <Link href="/students/create">
                                        <Button className="bg-white text-black hover:bg-white/90">
                                            Add Student
                                        </Button>
                                    </Link>
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
                                        ? 'bg-white text-black'
                                        : 'bg-transparent border-white/20 text-white/60 hover:bg-white/5'
                                }
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Delete Confirmation Dialog */}
            <Dialog
                open={deleteDialog.open}
                onOpenChange={(open) => setDeleteDialog({ open, student: null })}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Student</DialogTitle>
                        <DialogDescription>
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
                            className="text-white/60 hover:text-white hover:bg-white/5"
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleDelete}
                            className="bg-red-600 hover:bg-red-700 text-white"
                        >
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
