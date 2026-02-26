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
import { GraduationCap, Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import PageHeader from '@/components/PageHeader';
import EmptyState from '@/components/EmptyState';
import ConfirmDialog from '@/components/ConfirmDialog';

const formatType = (type) => (type || '—').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

export default function TeachersIndex({ auth, teachers }) {
    const [deleteDialog, setDeleteDialog] = useState({ open: false, teacher: null });
    const [deleting, setDeleting] = useState(false);

    const openDelete = (teacher) => setDeleteDialog({ open: true, teacher });
    const closeDelete = () => setDeleteDialog({ open: false, teacher: null });

    const handleDelete = () => {
        if (!deleteDialog.teacher) return;
        setDeleting(true);
        router.delete(`/teachers/${deleteDialog.teacher.id}`, {
            onSuccess: () => {
                toast.success('Teacher deleted successfully');
                closeDelete();
            },
            onError: () => toast.error('Failed to delete teacher'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Teachers" />

            <PageHeader
                icon={GraduationCap}
                title="Teachers"
                description="Manage teachers and staff records"
            >
                <Button onClick={() => router.get('/teachers/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Teacher
                </Button>
            </PageHeader>

            <Card>
                <CardHeader className="border-b px-6">
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-base font-semibold">All Teachers</CardTitle>
                        <Badge variant="secondary">{teachers?.data?.length ?? 0}</Badge>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {teachers?.data?.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <TooltipProvider>
                                    <Table striped hoverable className="text-sm">
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Code</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Name</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Gender</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Phone</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Employment</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Status</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-right text-xs uppercase tracking-wide text-muted-foreground">Actions</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {teachers.data.map((teacher) => (
                                                <TableRow key={teacher.id} className="border-border bg-background">
                                                    <TableCell className="font-mono text-sm font-medium text-primary">{teacher.teacher_code}</TableCell>
                                                    <TableCell>
                                                        <p className="font-medium text-foreground">{teacher.first_name} {teacher.last_name}</p>
                                                        {teacher.email && <p className="text-xs text-muted-foreground">{teacher.email}</p>}
                                                    </TableCell>
                                                    <TableCell className="capitalize text-sm text-muted-foreground">{teacher.gender}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{teacher.phone}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{formatType(teacher.employment_type)}</TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant="outline"
                                                            className={teacher.is_active
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                : 'bg-slate-100 text-slate-600 border-slate-200'}
                                                        >
                                                            {teacher.is_active ? 'Active' : 'Inactive'}
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
                                                                        onClick={() => router.get(`/teachers/${teacher.id}`)}
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
                                                                                onClick={() => router.get(`/teachers/${teacher.id}/edit`)}
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
                                                                                onClick={() => openDelete(teacher)}
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

                            {teachers?.links?.length > 3 && (
                                <div className="flex items-center justify-center gap-1 border-t px-6 py-4">
                                    {teachers.links.map((link, i) => (
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
                            icon={GraduationCap}
                            title="No teachers found"
                            description="Get started by adding the first teacher."
                            actionLabel="Add Teacher"
                            onAction={() => router.get('/teachers/create')}
                        />
                    )}
                </CardContent>
            </Card>

            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={closeDelete}
                title="Delete Teacher"
                description={`Are you sure you want to delete ${deleteDialog.teacher?.first_name} ${deleteDialog.teacher?.last_name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
