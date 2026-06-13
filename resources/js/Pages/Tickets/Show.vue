<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    ticket: Object,
    comments: Array,
});

const form = useForm({
    body: '',
});

const submitComment = () => {
    form.post(route('tickets.comments.store', props.ticket.id), {
        onSuccess: () => {
            form.reset();
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
</script>

<template>
    <Head :title="`Ticket #${ticket.id} - ${ticket.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('tickets.index')"
                    class="p-2 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg text-slate-500 hover:text-slate-700 transition shadow-sm"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-slate-400">Ticket #{{ ticket.id }}</span>
                        <span 
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold border"
                            :class="getStatusClass(ticket.status)"
                        >
                            {{ ticket.status.replace('_', ' ') }}
                        </span>
                    </div>
                    <h2 class="font-bold text-xl text-slate-800 leading-tight mt-1">
                        {{ ticket.title }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Ticket Details & Conversation (Main Content) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Ticket Description Card -->
                        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden">
                            <div class="p-6 sm:p-8">
                                <div class="flex justify-between items-center pb-4 border-b border-slate-100 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm uppercase">
                                            {{ ticket.creator.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-800">{{ ticket.creator.name }}</div>
                                            <div class="text-xs text-slate-400">Created on {{ formatDate(ticket.created_at) }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-slate-700 whitespace-pre-wrap leading-relaxed text-sm">
                                    {{ ticket.description }}
                                </div>
                            </div>
                        </div>

                        <!-- Comments Section -->
                        <div class="space-y-6">
                            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                                <span>Conversation</span>
                                <span class="bg-slate-200 text-slate-700 text-xs px-2.5 py-0.5 rounded-full font-semibold">{{ comments.length }}</span>
                            </h3>

                            <!-- Comments Timeline -->
                            <div v-if="comments.length === 0" class="bg-white rounded-xl border border-slate-200/80 p-8 text-center text-slate-400 text-sm">
                                No comments or updates yet. Use the box below to start the conversation.
                            </div>

                            <div v-else class="space-y-4">
                                <div 
                                    v-for="comment in comments" 
                                    :key="comment.id"
                                    class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6"
                                    :class="comment.user.role === 'agent' ? 'border-l-4 border-l-indigo-500' : ''"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex items-center gap-3">
                                            <div 
                                                class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs uppercase"
                                                :class="comment.user.role === 'agent' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700'"
                                            >
                                                {{ comment.user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-800">
                                                    {{ comment.user.name }}
                                                    <span 
                                                        v-if="comment.user.role === 'agent'"
                                                        class="ml-1.5 inline-flex items-center px-1.5 py-0.2 bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase rounded border border-indigo-100"
                                                    >
                                                        Agent
                                                    </span>
                                                </div>
                                                <div class="text-xs text-slate-400">{{ formatDate(comment.created_at) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-slate-700 text-sm whitespace-pre-wrap leading-relaxed">
                                        {{ comment.body }}
                                    </p>
                                </div>
                            </div>

                            <!-- Comment Input Form -->
                            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                                <h4 class="text-sm font-bold text-slate-800 mb-4">Post a Response</h4>
                                <form @submit.prevent="submitComment" class="space-y-4">
                                    <div>
                                        <textarea
                                            v-model="form.body"
                                            rows="4"
                                            required
                                            placeholder="Type your message here..."
                                            class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition duration-150"
                                        ></textarea>
                                        <div v-if="form.errors.body" class="text-rose-600 text-xs mt-1">{{ form.errors.body }}</div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button
                                            type="submit"
                                            :disabled="form.processing"
                                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150 shadow-sm"
                                        >
                                            Send Message
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Information Sidebar -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-6 space-y-6">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider pb-3 border-b border-slate-100">Ticket Information</h3>
                            
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Priority</span>
                                <div class="mt-1.5">
                                    <span 
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border"
                                        :class="getPriorityClass(ticket.priority)"
                                    >
                                        {{ ticket.priority }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">SLA Status</span>
                                <div class="mt-1.5 flex flex-col gap-1">
                                    <span 
                                        class="inline-flex items-center w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold border"
                                        :class="getSlaStateClass(ticket.sla_state)"
                                    >
                                        {{ formatSlaState(ticket.sla_state) }}
                                    </span>
                                    <span class="text-xs text-slate-400 mt-1">Due by: {{ formatDate(ticket.sla_due_at) }}</span>
                                </div>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Agent</span>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <span class="h-8 w-8 rounded-full bg-indigo-50 flex items-center justify-center text-xs font-bold text-indigo-600 uppercase border border-indigo-100">
                                        {{ ticket.assigned_agent ? ticket.assigned_agent.name.charAt(0) : '?' }}
                                    </span>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-700">
                                            {{ ticket.assigned_agent ? ticket.assigned_agent.name : 'Unassigned' }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ ticket.assigned_agent ? ticket.assigned_agent.email : 'Support Queue' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Organization</span>
                                <div class="text-sm font-semibold text-slate-700 mt-1">
                                    {{ ticket.organization.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
