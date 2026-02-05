<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    tickets: Object,
});

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
    <AppLayout title="Tickets">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Support Tickets</h1>
                    <p class="text-sm text-slate-500 mt-1">Track and manage your support requests</p>
                </div>
                <Link :href="route('tickets.create')" class="bg-[#780116] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#c32f27] transition shadow-lg shadow-red-100">
                    + New Ticket
                </Link>
            </div>

            <!-- Empty State -->
            <div v-if="tickets.data.length === 0" class="bg-white rounded-2xl border border-slate-200 p-16 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No tickets yet</h3>
                <p class="text-sm text-slate-500 mb-6">Create your first support ticket to get help</p>
                <Link :href="route('tickets.create')" class="bg-[#780116] text-white px-6 py-3 rounded-xl text-sm font-bold hover:bg-[#c32f27] transition">
                    Create Ticket
                </Link>
            </div>

            <!-- Tickets List -->
            <div v-else class="space-y-3">
                <Link
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    :href="route('tickets.show', ticket.id)"
                    class="block bg-white rounded-2xl border border-slate-200 p-6 hover:shadow-lg hover:shadow-slate-100 transition-all duration-200 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-base font-bold text-slate-900 group-hover:text-[#780116] transition truncate">
                                    {{ ticket.subject }}
                                </h3>
                                <!-- Unread badge -->
                                <span v-if="ticket.unread_count > 0" class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-[#780116] rounded-full shrink-0">
                                    {{ ticket.unread_count }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 truncate mb-3">{{ ticket.description }}</p>
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="statusColors[ticket.status]">
                                    {{ statusLabel(ticket.status) }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="priorityColors[ticket.priority]">
                                    {{ ticket.priority }}
                                </span>
                                <span class="text-xs text-slate-400">#{{ ticket.id }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs text-slate-400">{{ timeAgo(ticket.updated_at) }}</span>
                        </div>
                    </div>
                </Link>
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
