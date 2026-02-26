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
import { School, Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import PageHeader from '@/components/PageHeader';
import EmptyState from '@/components/EmptyState';
import ConfirmDialog from '@/components/ConfirmDialog';

export default function ClassesIndex({ auth, classrooms }) {
    const [deleteDialog, setDeleteDialog] = useState({ open: false, classroom: null });
    const [deleting, setDeleting] = useState(false);

    const openDelete = (classroom) => setDeleteDialog({ open: true, classroom });
    const closeDelete = () => setDeleteDialog({ open: false, classroom: null });

    const handleDelete = () => {
        if (!deleteDialog.classroom) return;
        setDeleting(true);
        router.delete(`/classes/${deleteDialog.classroom.uuid}`, {
            onSuccess: () => {
                toast.success('Class deleted successfully');
                closeDelete();
            },
            onError: () => toast.error('Failed to delete class'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Classes" />

            <PageHeader
                icon={School}
                title="Classes"
                description="Manage class groups and capacity"
                className="mb-4"
            >
                <Button onClick={() => router.get('/classes/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Class
                </Button>
            </PageHeader>

            <Card className="gap-0 py-0">
                <div className="flex items-center gap-2 border-b px-6 py-3">
                    <CardTitle className="text-base font-semibold">All Classes</CardTitle>
                    <Badge variant="secondary">{classrooms?.data?.length ?? 0}</Badge>
                </div>
                <CardContent className="p-0">
                    {classrooms?.data?.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <TooltipProvider>
                                    <Table striped hoverable className="text-sm [&_th]:py-3.5 [&_td]:py-3.5">
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Name</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Grade</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Section</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Academic Year</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Capacity</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Enrollment</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Status</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-right text-xs uppercase tracking-wide text-muted-foreground">Actions</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {classrooms.data.map((classroom) => (
                                                <TableRow key={classroom.id} className="border-border bg-background transition-colors hover:bg-muted/30">
                                                    <TableCell className="font-medium text-foreground">{classroom.name}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{classroom.grade_level}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{classroom.section}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{classroom.academic_year}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{classroom.capacity}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{classroom.students_count ?? classroom.current_enrollment ?? 0}</TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant="outline"
                                                            className={classroom.is_active
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                : 'bg-slate-100 text-slate-600 border-slate-200'}
                                                        >
                                                            {classroom.is_active ? 'Active' : 'Inactive'}
                                                        </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Tooltip>
                                                                <TooltipTrigger asChild>
                                                                    <Button
                                                                        variant="ghost"
                                                                        size="sm"
                                                                        className="text-muted-foreground hover:bg-transparent hover:text-foreground"
                                                                        onClick={() => router.get(`/classes/${classroom.uuid}`)}
                                                                    >
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </TooltipTrigger>
                                                                <TooltipContent>View</TooltipContent>
                                                            </Tooltip>

                                                            {auth.user?.is_admin && (
                                                                <>
                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                className="text-muted-foreground hover:bg-transparent hover:text-foreground"
                                                                                onClick={() => router.get(`/classes/${classroom.uuid}/edit`)}
                                                                            >
                                                                                <Pencil className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>Edit</TooltipContent>
                                                                    </Tooltip>

                                                                    <Tooltip>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                className="text-destructive hover:bg-transparent hover:text-destructive"
                                                                                onClick={() => openDelete(classroom)}
                                                                            >
                                                                                <Trash2 className="h-4 w-4" />
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>Delete</TooltipContent>
                                                                    </Tooltip>
                                                                </>
                                                            )}
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </TooltipProvider>
                            </div>

                            {classrooms?.links?.length > 3 && (
                                <div className="flex items-center justify-center gap-1 border-t px-6 py-4">
                                    {classrooms.links.map((link, i) => (
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
                            icon={School}
                            title="No classes found"
                            description="Get started by adding the first class."
                            actionLabel="Add Class"
                            onAction={() => router.get('/classes/create')}
                        />
                    )}
                </CardContent>
            </Card>

            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={closeDelete}
                title="Delete Class"
                description={`Are you sure you want to delete ${deleteDialog.classroom?.name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
