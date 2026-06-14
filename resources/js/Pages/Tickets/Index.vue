<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { useTickets } from "./useTickets.js";

const props = defineProps({
    // tickets is a Laravel paginator object: { data: [...], current_page, last_page, links, ... }
    tickets: Object,
    filters: Object,
});

const {
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
} = useTickets(props.filters);
</script>

<template>
    <Head title="Support Tickets" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-2xl text-slate-800 leading-tight">
                    Support Tickets
                </h2>
                <button
                    @click="showCreateForm = !showCreateForm"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-violet-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:from-indigo-700 hover:to-violet-700 focus:from-indigo-700 focus:to-violet-700 active:from-indigo-900 active:to-violet-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150 shadow-md shadow-indigo-200 hover:shadow-lg hover:shadow-indigo-300"
                >
                    {{ showCreateForm ? "Cancel" : "Create New Ticket" }}
                </button>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Create Ticket Form Card -->
                <div
                    v-if="showCreateForm"
                    class="bg-white overflow-hidden shadow-xl sm:rounded-xl border border-slate-200/80 transition-all duration-300 ease-in-out transform scale-100"
                >
                    <div class="p-6 sm:p-8">
                        <h3
                            class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2"
                        >
                            <span
                                class="w-2.5 h-2.5 rounded-full bg-indigo-500"
                            ></span>
                            Submit a New Support Ticket
                        </h3>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label
                                    for="title"
                                    class="block text-sm font-medium text-slate-700"
                                    >Subject / Title</label
                                >
                                <input
                                    id="title"
                                    type="text"
                                    v-model="form.title"
                                    required
                                    placeholder="Enter a descriptive title of the issue"
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition duration-150"
                                />
                                <div
                                    v-if="form.errors.title"
                                    class="text-rose-600 text-xs mt-1"
                                >
                                    {{ form.errors.title }}
                                </div>
                            </div>

                            <div>
                                <label
                                    for="description"
                                    class="block text-sm font-medium text-slate-700"
                                    >Detailed Description</label
                                >
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="5"
                                    required
                                    placeholder="Please describe the steps to reproduce the issue, error codes, or requirements..."
                                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition duration-150"
                                ></textarea>
                                <div
                                    v-if="form.errors.description"
                                    class="text-rose-600 text-xs mt-1"
                                >
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <div class="w-full sm:w-1/3">
                                <label
                                    for="priority"
                                    class="block text-sm text-slate-700 font-semibold mb-2"
                                    >Priority Level</label
                                >
                                <div class="grid grid-cols-3 gap-2">
                                    <label
                                        v-for="prio in [
                                            'low',
                                            'normal',
                                            'high',
                                        ]"
                                        :key="prio"
                                        class="cursor-pointer border rounded-lg p-3 text-center transition-all duration-150 capitalize flex flex-col justify-center items-center text-sm font-medium"
                                        :class="
                                            form.priority === prio
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-500/20'
                                                : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-600'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            name="priority"
                                            :value="prio"
                                            v-model="form.priority"
                                            class="sr-only"
                                        />
                                        <span>{{ prio }}</span>
                                    </label>
                                </div>
                                <div
                                    v-if="form.errors.priority"
                                    class="text-rose-600 text-xs mt-1"
                                >
                                    {{ form.errors.priority }}
                                </div>
                            </div>

                            <div
                                class="flex justify-end gap-3 pt-4 border-t border-slate-100"
                            >
                                <button
                                    type="button"
                                    @click="showCreateForm = false"
                                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition duration-150"
                                >
                                    Submit Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div
                    class="bg-white rounded-xl border border-slate-200/80 shadow-sm p-4"
                >
                    <form
                        @submit.prevent="applyFilters"
                        class="flex flex-wrap items-end gap-3"
                    >
                        <!-- Search -->
                        <div class="flex-1 min-w-[180px]">
                            <label
                                for="client-search"
                                class="block text-xs font-medium text-slate-500 mb-1"
                                >Search</label
                            >
                            <input
                                id="client-search"
                                type="text"
                                v-model="filters.search"
                                placeholder="Search by title or description…"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition duration-150"
                            />
                        </div>

                        <!-- Status -->
                        <div class="min-w-[140px]">
                            <label
                                for="client-status"
                                class="block text-xs font-medium text-slate-500 mb-1"
                                >Status</label
                            >
                            <select
                                id="client-status"
                                v-model="filters.status"
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm transition duration-150"
                            >
                                <option value="">All statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                            >
                                Clear
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tickets List Card -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-slate-200/80"
                >
                    <div class="p-6">
                        <div
                            v-if="tickets.data.length === 0"
                            class="text-center py-16"
                        >
                            <svg
                                class="mx-auto h-12 w-12 text-slate-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                                />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">
                                No support tickets yet
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Need help? Submit your first support ticket
                                using the button above.
                            </p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50/75">
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider rounded-l-lg"
                                        >
                                            ID / Title
                                        </th>
                                        <th
                                            class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                        >
                                            Status
                                        </th>
                                        <th
                                            class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                        >
                                            Priority
                                        </th>
                                        <th
                                            class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                        >
                                            SLA Status
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                        >
                                            Assigned Agent
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider rounded-r-lg"
                                        >
                                            Created At
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 bg-white"
                                >
                                    <tr
                                        v-for="ticket in tickets.data"
                                        :key="ticket.id"
                                        class="hover:bg-slate-50/75 transition-colors duration-150"
                                    >
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <Link
                                                    :href="
                                                        route(
                                                            'tickets.show',
                                                            ticket.id,
                                                        )
                                                    "
                                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 hover:underline transition duration-150"
                                                >
                                                    #{{ ticket.id }} -
                                                    {{ ticket.title }}
                                                </Link>
                                                <span
                                                    class="text-xs text-slate-400 mt-1 max-w-md truncate"
                                                    >{{
                                                        ticket.description
                                                    }}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-center"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border"
                                                :class="
                                                    getStatusClass(
                                                        ticket.status,
                                                    )
                                                "
                                            >
                                                {{
                                                    ticket.status.replace(
                                                        "_",
                                                        " ",
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-center"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border"
                                                :class="
                                                    getPriorityClass(
                                                        ticket.priority,
                                                    )
                                                "
                                            >
                                                {{ ticket.priority }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-center"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border"
                                                :class="
                                                    getSlaStateClass(
                                                        ticket.sla_state,
                                                    )
                                                "
                                            >
                                                {{
                                                    formatSlaState(
                                                        ticket.sla_state,
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-sm text-slate-600"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="h-6 w-6 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500 uppercase"
                                                >
                                                    {{
                                                        ticket.assigned_agent
                                                            ? ticket.assigned_agent.name.charAt(
                                                                  0,
                                                              )
                                                            : "?"
                                                    }}
                                                </span>
                                                <span>{{
                                                    ticket.assigned_agent
                                                        ? ticket.assigned_agent
                                                              .name
                                                        : "Unassigned"
                                                }}</span>
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-sm text-slate-400"
                                        >
                                            {{ formatDate(ticket.created_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pagination Links -->
                <div
                    v-if="tickets.last_page > 1"
                    class="flex items-center justify-center gap-1 pt-2"
                >
                    <template v-for="link in tickets.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm border transition-colors duration-150',
                                link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                                    : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50',
                            ]"
                            preserve-scroll
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 rounded-lg text-sm border bg-white text-slate-300 border-slate-200 cursor-not-allowed"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
