import { ref, reactive } from 'vue';
import { useForm, router } from '@inertiajs/vue3';

export function useTickets(initialFilters = {}) {
    const showCreateForm = ref(false);

    const form = useForm({
        title: '',
        description: '',
        priority: 'normal',
    });

    const filters = reactive({
        status: initialFilters.status ?? '',
        search: initialFilters.search ?? '',
    });

    const applyFilters = () => {
        router.get(route('tickets.index'), filters, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        filters.status = '';
        filters.search = '';
        router.get(route('tickets.index'), {}, { replace: true });
    };

    const submit = () => {
        form.post(route('tickets.store'), {
            onSuccess: () => {
                form.reset();
                showCreateForm.value = false;
            },
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
        showCreateForm,
        form,
        filters,
        applyFilters,
        clearFilters,
        submit,
        getPriorityClass,
        getStatusClass,
        getSlaStateClass,
        formatSlaState,
        formatDate,
    };
}
