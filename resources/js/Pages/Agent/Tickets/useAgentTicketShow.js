import { useForm } from '@inertiajs/vue3';

export function useAgentTicketShow(props) {
    // Form for support actions (status, priority, assignee)
    const actionForm = useForm({
        status: props.ticket.status,
        priority: props.ticket.priority,
        assigned_to_user_id: props.ticket.assigned_to_user_id || '',
    });

    // Form for adding a comment
    const commentForm = useForm({
        body: '',
        is_internal: false,
    });

    const submitAction = () => {
        actionForm.patch(route('agent.tickets.update', props.ticket.id), {
            preserveScroll: true,
        });
    };

    const submitComment = () => {
        commentForm.post(route('agent.tickets.comments.store', props.ticket.id), {
            onSuccess: () => {
                commentForm.reset('body');
            },
            preserveScroll: true,
        });
    };

    const getPriorityClass = (priority) => {
        switch (priority) {
            case 'high':
                return 'bg-rose-50 text-rose-700 ring-rose-600/10 border-rose-200';
            case 'normal':
                return 'bg-amber-50 text-amber-700 ring-amber-600/10 border-amber-200';
            case 'low':
                return 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 border-emerald-200';
            default:
                return 'bg-slate-50 text-slate-700 ring-slate-600/10 border-slate-200';
        }
    };

    const getStatusClass = (status) => {
        switch (status) {
            case 'open':
                return 'bg-indigo-50 text-indigo-700 ring-indigo-600/10 border-indigo-200';
            case 'in_progress':
                return 'bg-sky-50 text-sky-700 ring-sky-600/10 border-sky-200';
            case 'resolved':
                return 'bg-emerald-50 text-emerald-700 ring-emerald-600/10 border-emerald-200';
            case 'closed':
                return 'bg-slate-100 text-slate-800 border-slate-200';
            default:
                return 'bg-slate-50 text-slate-700 border-slate-200';
        }
    };

    const getSlaStateClass = (state) => {
        switch (state) {
            case 'overdue':
                return 'bg-red-100 text-red-800 animate-pulse border-red-200';
            case 'due_soon':
                return 'bg-orange-100 text-orange-800 border-orange-200';
            case 'on_track':
                return 'bg-emerald-100 text-emerald-800 border-emerald-200';
            default:
                return 'bg-slate-100 text-slate-800 border-slate-200';
        }
    };

    const formatSlaState = (state) => {
        return state.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return {
        actionForm,
        commentForm,
        submitAction,
        submitComment,
        getPriorityClass,
        getStatusClass,
        getSlaStateClass,
        formatSlaState,
        formatDate,
    };
}
