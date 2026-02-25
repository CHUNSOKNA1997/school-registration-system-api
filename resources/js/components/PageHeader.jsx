export default function PageHeader({ icon: Icon, title, description, children }) {
    return (
        <div className="flex items-center justify-between mb-6">
            <div className="flex items-center gap-4">
                {Icon && (
                    <div className="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10">
                        <Icon className="h-6 w-6 text-primary" />
                    </div>
                )}
                <div>
                    <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
                    {description && (
                        <p className="text-sm text-muted-foreground mt-0.5">{description}</p>
                    )}
                </div>
            </div>
            {children && <div className="flex items-center gap-2">{children}</div>}
        </div>
    );
}
