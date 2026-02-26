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
import { BookOpen, Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import PageHeader from '@/components/PageHeader';
import EmptyState from '@/components/EmptyState';
import ConfirmDialog from '@/components/ConfirmDialog';

const formatType = (type) => (type || '—').replace(/\b\w/g, c => c.toUpperCase());

export default function SubjectsIndex({ auth, subjects }) {
    const [deleteDialog, setDeleteDialog] = useState({ open: false, subject: null });
    const [deleting, setDeleting] = useState(false);

    const openDelete = (subject) => setDeleteDialog({ open: true, subject });
    const closeDelete = () => setDeleteDialog({ open: false, subject: null });

    const handleDelete = () => {
        if (!deleteDialog.subject) return;
        setDeleting(true);
        router.delete(`/subjects/${deleteDialog.subject.id}`, {
            onSuccess: () => {
                toast.success('Subject deleted successfully');
                closeDelete();
            },
            onError: () => toast.error('Failed to delete subject'),
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Subjects" />

            <PageHeader
                icon={BookOpen}
                title="Subjects"
                description="Manage subject catalog and fee structure"
            >
                <Button onClick={() => router.get('/subjects/create')}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Subject
                </Button>
            </PageHeader>

            <Card className="gap-0 py-0">
                <CardHeader className="border-b px-6 py-4">
                    <div className="flex items-center gap-2">
                        <CardTitle className="text-base font-semibold">All Subjects</CardTitle>
                        <Badge variant="secondary">{subjects?.data?.length ?? 0}</Badge>
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    {subjects?.data?.length > 0 ? (
                        <>
                            <div className="overflow-x-auto">
                                <TooltipProvider>
                                    <Table striped hoverable className="text-sm [&_th]:py-3.5 [&_td]:py-3.5">
                                        <TableHead>
                                            <TableRow>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Code</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Name</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Grade</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Type</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Credits</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Fee</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">Status</TableHeadCell>
                                                <TableHeadCell className="bg-muted/50 text-right text-xs uppercase tracking-wide text-muted-foreground">Actions</TableHeadCell>
                                            </TableRow>
                                        </TableHead>
                                        <TableBody className="divide-y">
                                            {subjects.data.map((subject) => (
                                                <TableRow key={subject.id} className="border-border bg-background transition-colors hover:bg-muted/30">
                                                    <TableCell className="font-mono text-sm font-medium text-primary">{subject.subject_code}</TableCell>
                                                    <TableCell>
                                                        <p className="font-medium text-foreground">{subject.name}</p>
                                                        {subject.name_khmer && <p className="text-xs text-muted-foreground">{subject.name_khmer}</p>}
                                                    </TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">Grade {subject.grade_level}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{formatType(subject.subject_type)}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">{subject.credits}</TableCell>
                                                    <TableCell className="text-sm text-muted-foreground">${subject.fee ?? 0}</TableCell>
                                                    <TableCell>
                                                        <Badge
                                                            variant="outline"
                                                            className={subject.is_active
                                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                                : 'bg-slate-100 text-slate-600 border-slate-200'}
                                                        >
                                                            {subject.is_active ? 'Active' : 'Inactive'}
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
                                                                        onClick={() => router.get(`/subjects/${subject.id}`)}
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
                                                                                onClick={() => router.get(`/subjects/${subject.id}/edit`)}
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
                                                                                onClick={() => openDelete(subject)}
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

                            {subjects?.links?.length > 3 && (
                                <div className="flex items-center justify-center gap-1 border-t px-6 py-4">
                                    {subjects.links.map((link, i) => (
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
                            icon={BookOpen}
                            title="No subjects found"
                            description="Get started by adding the first subject."
                            actionLabel="Add Subject"
                            onAction={() => router.get('/subjects/create')}
                        />
                    )}
                </CardContent>
            </Card>

            <ConfirmDialog
                open={deleteDialog.open}
                onOpenChange={closeDelete}
                title="Delete Subject"
                description={`Are you sure you want to delete ${deleteDialog.subject?.name}? This action cannot be undone.`}
                confirmLabel="Delete"
                processing={deleting}
                onConfirm={handleDelete}
            />
        </AuthenticatedLayout>
    );
}
