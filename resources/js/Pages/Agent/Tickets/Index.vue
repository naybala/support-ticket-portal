<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { useAgentTickets } from "./useAgentTickets.js";
import { usePage } from "@inertiajs/vue3";

// tickets is a Laravel paginator object: { data: [...], current_page, last_page, links, ... }

const props = defineProps({
    tickets: Object,
    filters: Object,
    agents: Array,
    organizations: Array,
});

const {
    statusFilter,
    priorityFilter,
    organizationIdFilter,
    searchQuery,
    applyFilters,
    resetFilters,
    getPriorityClass,
    getStatusClass,
    getSlaStateClass,
    formatSlaState,
    formatDate,
} = useAgentTickets(props);
</script>

<template>
    <Head title="Agent Support Portal" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4"
            >
                <div>
                    <h2 class="font-bold text-2xl text-slate-800 leading-tight">
                        Agent Support Dashboard
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Manage and resolve tickets across all client
                        organizations.
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Filters panel -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5"
                >
                    <form @submit.prevent="applyFilters">
                        <div
                            class="flex flex-col lg:flex-row justify-between items-end gap-4"
                        >
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 w-full"
                            >
                                <!-- Search -->
                                <div class="flex flex-col">
                                    <label
                                        for="search-filter"
                                        class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                        >Search</label
                                    >
                                    <input
                                        id="search-filter"
                                        type="text"
                                        v-model="searchQuery"
                                        placeholder="Search title/desc..."
                                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 bg-white"
                                    />
                                </div>

                                <!-- Status -->
                                <div class="flex flex-col">
                                    <label
                                        for="status-filter"
                                        class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                        >Status</label
                                    >
                                    <select
                                        id="status-filter"
                                        v-model="statusFilter"
                                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 bg-white"
                                    >
                                        <option value="">All Statuses</option>
                                        <option value="open">Open</option>
                                        <option value="in_progress">
                                            In Progress
                                        </option>
                                        <option value="resolved">
                                            Resolved
                                        </option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>

                                <!-- Priority -->
                                <div class="flex flex-col">
                                    <label
                                        for="priority-filter"
                                        class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                        >Priority</label
                                    >
                                    <select
                                        id="priority-filter"
                                        v-model="priorityFilter"
                                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 bg-white"
                                    >
                                        <option value="">All Priorities</option>
                                        <option value="low">Low</option>
                                        <option value="normal">Normal</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>

                                <!-- Organization -->
                                <div class="flex flex-col">
                                    <label
                                        for="org-filter"
                                        class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5"
                                        >Organization</label
                                    >
                                    <select
                                        id="org-filter"
                                        v-model="organizationIdFilter"
                                        class="rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 bg-white"
                                    >
                                        <option value="">
                                            All Organizations
                                        </option>
                                        <option
                                            v-for="org in organizations"
                                            :key="org.id"
                                            :value="org.id"
                                        >
                                            {{ org.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 shrink-0">
                                <button
                                    type="submit"
                                    class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                                >
                                    Apply
                                </button>
                                <button
                                    type="button"
                                    @click="resetFilters"
                                    class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150"
                                >
                                    Clear
                                </button>
                            </div>
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">
                                All caught up!
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                No tickets match the selected filter criteria.
                            </p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50/75">
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider rounded-l-lg"
                                        >
                                            ID / Subject
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"
                                        >
                                            Client & Org
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
                                                            'agent.tickets.show',
                                                            ticket.id,
                                                        )
                                                    "
                                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-900 hover:underline transition duration-150"
                                                >
                                                    #{{ ticket.id }} -
                                                    {{ ticket.title }}
                                                </Link>
                                                <span
                                                    class="text-xs text-slate-400 mt-1 max-w-xs truncate"
                                                    >{{
                                                        ticket.description
                                                    }}</span
                                                >
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-sm font-medium text-slate-800"
                                                    >{{
                                                        ticket.creator.name
                                                    }}</span
                                                >
                                                <span
                                                    class="text-xs text-slate-400 mt-0.5"
                                                    >{{
                                                        ticket.organization
                                                            ? ticket
                                                                  .organization
                                                                  .name
                                                            : "No Org"
                                                    }}</span
                                                >
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-5 whitespace-nowrap text-center"
                                        >
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border"
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
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border"
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
                                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border"
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
                                                        : "Queue"
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
