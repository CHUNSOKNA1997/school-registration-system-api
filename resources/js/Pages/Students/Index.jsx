import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    Table, TableBody, TableCell, TableHead,
    TableHeadCell, TableRow,
} from 'flowbite-react';
import { toast } from 'sonner';
import { Users, Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import PageHeader from '@/components/PageHeader';
import EmptyState from '@/components/EmptyState';
import StatusBadge from '@/components/StatusBadge';
import ConfirmDialog from '@/components/ConfirmDialog';

export default function StudentsIndex({ auth, students }) {
    const [deleteDialog, setDeleteDialog] = useState({ open: false, student: null });
    const [deleting, setDeleting] = useState(false);

    const openDelete = (student) => setDeleteDialog({ open: true, student });
    const closeDelete = () => setDeleteDialog({ open: false, student: null });

    const handleDelete = () => {
        if (!deleteDialog.student) return;
        setDeleting(true);
        router.delete(`/students/${deleteDialog.student.id}`, {
            onSuccess: () => {
                toast.success('Student deleted successfully');
                closeDelete();
            },
            onError: () => toast.error('Failed to delete student'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Students" />

            <PageHeader
                icon={Users}
                title="Students"
                description="Manage student records and enrollments"
            >
                <Button onClick={() => router.get('/students/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Student
                </Button>
            </PageHeader>

            {/* Table */}
            <Card className="py-3">
                <CardHeader className="grid-rows-[auto] gap-0 border-b px-6 pt-3 pb-2 [.border-b]:pb-2">
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-base font-semibold">All Students</CardTitle>
                        <Badge variant="secondary">{students?.data?.length ?? 0}</Badge>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {students?.data?.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <TooltipProvider>
                                    <Table striped hoverable className="text-sm">
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Student Code</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Name</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Gender</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Class</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Phone</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Status</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-right text-xs uppercase tracking-wide text-muted-foreground">Actions</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {students.data.map((student) => (
                                                <TableRow key={student.id} className="border-border bg-background transition-colors hover:bg-muted/30">
                                                    <TableCell className="whitespace-nowrap font-mono text-sm font-medium text-primary">
                                                        {student.student_code}
                                                    </TableCell>
                                                    <TableCell>
                                                        <p className="font-medium text-foreground">
                                                            {student.first_name} {student.last_name}
                                                        </p>
                                                        {student.khmer_name && (
                                                            <p className="text-xs text-muted-foreground">{student.khmer_name}</p>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="capitalize text-sm text-muted-foreground">
                                                        {student.gender}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {student.class?.name_en ?? '—'}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">
                                                        {student.phone ?? '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <StatusBadge status={student.status} />
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost" size="sm"
                                                                        className="text-muted-foreground hover:bg-transparent hover:text-foreground"
                                                                        onClick={() => router.get(`/students/${student.id}`)}
                                                                    >
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>View</TooltipContent>
                                                            </Tooltip>

                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost" size="sm"
                                                                        className="text-muted-foreground hover:bg-transparent hover:text-foreground"
                                                                        onClick={() => router.get(`/students/${student.id}/edit`)}
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>Edit</TooltipContent>
                                                            </Tooltip>

                                                            {auth.user?.is_admin && (
                                                                <Tooltip>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="ghost" size="sm"
                                                                            className="text-destructive hover:bg-transparent hover:text-destructive"
                                                                            onClick={() => openDelete(student)}
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>Delete</TooltipContent>
                                                                </Tooltip>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </TooltipProvider>
                            </div>

                            {/* Pagination */}
                            {students?.links?.length > 3 && (
                                <div className="flex items-center justify-center gap-1 border-t px-6 py-4">
                                    {students.links.map((link, i) => (
                                        <Button
                                            key={i}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            disabled={!link.url}
                                            onClick={() => link.url && router.get(link.url)}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            )}
                        </>
                    ) : (
                        <EmptyState
                            icon={Users}
                            title="No students found"
                            description="Get started by adding the first student to the system."
                            actionLabel="Add Student"
                            onAction={() => router.get('/students/create')}
                        />
                    )}
                </CardContent>
            </Card>

            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={closeDelete}
                title="Delete Student"
                description={`Are you sure you want to delete ${deleteDialog.student?.first_name} ${deleteDialog.student?.last_name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
