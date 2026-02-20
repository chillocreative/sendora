<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    conversations: Object,
    whatsappNumbers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const whatsappNumberId = ref(props.filters?.whatsapp_number_id || '');

let searchTimeout = null;

const applyFilters = () => {
    router.get(route('conversations.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
        whatsapp_number_id: whatsappNumberId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch([status, whatsappNumberId], applyFilters);

const statusBadge = (s) => {
    switch (s) {
        case 'active': return { label: 'AI Active', class: 'bg-green-50 text-green-600 border-green-100' };
        case 'paused': return { label: 'Paused', class: 'bg-yellow-50 text-yellow-600 border-yellow-100' };
        case 'closed': return { label: 'Closed', class: 'bg-slate-100 text-slate-400 border-slate-200' };
        default: return { label: s, class: 'bg-slate-100 text-slate-400 border-slate-200' };
    }
};

const timeAgo = (date) => {
    if (!date) return 'Never';
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
};
</script>

<template>
    <AppLayout title="Conversations">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                Conversations
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Toolbar -->
                    <div class="p-8 border-b border-slate-50 bg-white space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-6">
                            <div class="text-center sm:text-left">
                                <h3 class="font-black text-slate-900 text-xl tracking-tight leading-none mb-2">Message Inbox</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">AI-managed conversations with your WhatsApp contacts</p>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <input v-model="search" type="text" placeholder="Search by phone or name..." class="w-full px-5 py-3 bg-slate-50 border-slate-100 rounded-2xl font-bold text-sm focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                            </div>
                            <select v-model="status" class="px-5 py-3 bg-slate-50 border-slate-100 rounded-2xl font-bold text-sm focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                <option value="">All Statuses</option>
                                <option value="active">AI Active</option>
                                <option value="paused">Paused</option>
                                <option value="closed">Closed</option>
                            </select>
                            <select v-model="whatsappNumberId" class="px-5 py-3 bg-slate-50 border-slate-100 rounded-2xl font-bold text-sm focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                <option value="">All Numbers</option>
                                <option v-for="num in whatsappNumbers" :key="num.id" :value="num.id">{{ num.phone_number || 'Device #' + num.id }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Conversation List -->
                    <div class="overflow-x-auto">
                        <table v-if="conversations.data.length > 0" class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 font-black">Contact</th>
                                    <th class="px-8 py-5 font-black">WhatsApp Number</th>
                                    <th class="px-8 py-5 font-black text-center">Status</th>
                                    <th class="px-8 py-5 font-black text-center">Messages</th>
                                    <th class="px-8 py-5 font-black text-right">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="conv in conversations.data" :key="conv.id" class="hover:bg-slate-50/50 transition-all cursor-pointer group" @click="router.get(route('conversations.show', conv.id))">
                                    <td class="px-8 py-6">
                                        <div class="font-black text-slate-900">{{ conv.contact_name || 'Unknown' }}</div>
                                        <div class="text-[11px] text-slate-400 font-bold mt-0.5">{{ conv.contact_phone }}</div>
                                    </td>
                                    <td class="px-8 py-6 text-slate-500 font-bold">
                                        {{ conv.whatsapp_number?.phone_number || '-' }}
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="statusBadge(conv.status).class">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="conv.status === 'active' ? 'bg-green-400 animate-pulse' : conv.status === 'paused' ? 'bg-yellow-400' : 'bg-slate-300'"></span>
                                            {{ statusBadge(conv.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-slate-100 text-slate-600 rounded-full text-xs font-black">
                                            {{ conv.message_count }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right text-slate-400 font-bold text-xs">
                                        {{ timeAgo(conv.last_customer_message_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-12 text-center">
                            <div class="inline-flex p-4 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">No conversations yet</h3>
                            <p class="text-slate-500 mt-1">Conversations will appear here when customers message your WhatsApp numbers.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="conversations.data.length > 0 && conversations.last_page > 1" class="px-8 py-6 border-t border-slate-50 flex justify-between items-center">
                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Showing {{ conversations.from }}-{{ conversations.to }} of {{ conversations.total }}
                        </div>
                        <div class="flex gap-2">
                            <Link v-for="link in conversations.links" :key="link.label" :href="link.url || '#'" class="px-4 py-2 rounded-xl text-xs font-black transition-all" :class="link.active ? 'bg-[#780116] text-white shadow-lg' : link.url ? 'bg-slate-50 text-slate-600 hover:bg-slate-100' : 'bg-slate-50 text-slate-300 cursor-not-allowed'" v-html="link.label" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
