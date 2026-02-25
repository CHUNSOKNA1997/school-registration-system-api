import { Button } from '@/components/ui/button';

export default function EmptyState({ icon: Icon, title, description, actionLabel, onAction }) {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            {Icon && (
                <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-muted mb-4">
                    <Icon className="h-8 w-8 text-muted-foreground" />
                </div>
            )}
            <h3 className="text-base font-semibold text-foreground">{title}</h3>
            {description && (
                <p className="mt-1 text-sm text-muted-foreground max-w-xs">{description}</p>
            )}
            {actionLabel && onAction && (
                <Button onClick={onAction} className="mt-6">
                    {actionLabel}
                </Button>
            )}
        </div>
    );
}
