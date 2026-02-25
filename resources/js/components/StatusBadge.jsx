import { Badge } from '@/components/ui/badge';

const STATUS_STYLES = {
    active:    'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-50',
    inactive:  'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-100',
    graduated: 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-50',
    suspended: 'bg-red-50 text-red-700 border-red-200 hover:bg-red-50',
    pending:   'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-50',
    paid:      'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-50',
    overdue:   'bg-red-50 text-red-700 border-red-200 hover:bg-red-50',
};

export default function StatusBadge({ status }) {
    const style = STATUS_STYLES[status] ?? STATUS_STYLES.inactive;
    const label = status ? status.charAt(0).toUpperCase() + status.slice(1) : '—';

    return (
        <Badge variant="outline" className={`font-medium ${style}`}>
            {label}
        </Badge>
    );
}
