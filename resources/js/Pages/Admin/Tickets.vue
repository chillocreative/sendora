<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tickets: Object,
    filters: Object,
});

const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');

const applyFilters = () => {
    router.get(route('admin.tickets'), {
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    }, { preserveState: true });
};

const clearFilters = () => {
    statusFilter.value = '';
    priorityFilter.value = '';
    router.get(route('admin.tickets'));
};

const deleteTicket = (ticketId, e) => {
    e.stopPropagation();
    if (!confirm('Are you sure you want to delete this ticket? This action cannot be undone.')) return;
    router.delete(route('admin.tickets.destroy', ticketId), {
        preserveScroll: true,
    });
};

const statusColors = {
    open: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    resolved: 'bg-green-100 text-green-700',
    closed: 'bg-slate-100 text-slate-500',
};

const priorityColors = {
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600',
};

const statusLabel = (status) => status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

const timeAgo = (date) => {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return 'Just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
};
</script>

<template>
    <AppLayout title="Support Tickets">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Support Tickets</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage all user support tickets</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 flex items-center gap-4 flex-wrap">
                <select v-model="statusFilter" @change="applyFilters" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#780116]/20">
                    <option value="">All Status</option>
                    <option value="open">Open</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="closed">Closed</option>
                </select>
                <select v-model="priorityFilter" @change="applyFilters" class="px-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#780116]/20">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
                <button v-if="statusFilter || priorityFilter" @click="clearFilters" class="text-xs font-bold text-slate-500 hover:text-[#780116] transition">
                    Clear Filters
                </button>
                <div class="ml-auto text-sm text-slate-400">{{ tickets.total }} ticket{{ tickets.total !== 1 ? 's' : '' }}</div>
            </div>

            <!-- Empty State -->
            <div v-if="tickets.data.length === 0" class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No tickets found</h3>
                <p class="text-sm text-slate-500">No support tickets match your filters</p>
            </div>

            <!-- Tickets Table -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Ticket</th>
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">User</th>
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Priority</th>
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Replies</th>
                            <th class="text-left px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Updated</th>
                            <th class="text-right px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            class="border-b border-slate-50 hover:bg-slate-50/50 transition cursor-pointer"
                            @click="router.visit(route('admin.tickets.show', ticket.id))"
                        >
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900">#{{ ticket.id }} {{ ticket.subject }}</div>
                                <p class="text-xs text-slate-400 mt-1 truncate max-w-xs">{{ ticket.description }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600">{{ ticket.user?.name || 'Unknown' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="priorityColors[ticket.priority]">
                                    {{ ticket.priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="statusColors[ticket.status]">
                                    {{ statusLabel(ticket.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-500">{{ ticket.replies_count }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-400">{{ timeAgo(ticket.updated_at) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button
                                    @click="deleteTicket(ticket.id, $event)"
                                    class="text-slate-400 hover:text-red-500 transition p-1"
                                    title="Delete ticket"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex justify-center gap-2 mt-8">
                <Link
                    v-for="link in tickets.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="px-4 py-2 rounded-lg text-sm font-bold transition"
                    :class="link.active ? 'bg-[#780116] text-white' : link.url ? 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200' : 'text-slate-300 cursor-not-allowed'"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
