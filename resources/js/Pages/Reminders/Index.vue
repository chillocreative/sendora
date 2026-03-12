<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reminders: Object,
    filter: String,
});

const activeFilter = ref(props.filter || 'upcoming');

const setFilter = (f) => {
    activeFilter.value = f;
    router.get(route('reminders.index'), { filter: f }, { preserveState: true });
};

const cancelReminder = (id) => {
    if (confirm('Cancel this reminder?')) {
        router.delete(route('reminders.destroy', id), { preserveScroll: true });
    }
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-GB', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const statusColor = (status) => {
    const colors = {
        pending: 'bg-amber-50 text-amber-600',
        sent: 'bg-emerald-50 text-emerald-600',
        failed: 'bg-red-50 text-red-600',
        cancelled: 'bg-slate-50 text-slate-400',
    };
    return colors[status] || colors.pending;
};

const sourceLabel = (source) => {
    const labels = {
        web: 'Web',
        whatsapp_command: '/sendora',
        google_calendar: 'Calendar',
    };
    return labels[source] || source;
};
</script>

<template>
    <AppLayout title="Reminders">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">Reminders</h2>
                <Link :href="route('reminders.create')" class="flex items-center gap-2 px-6 py-3 bg-[#780116] text-white rounded-xl text-[11px] font-black uppercase tracking-widest hover:bg-[#c32f27] transition shadow-lg shadow-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    New Reminder
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Filter Tabs -->
                <div class="flex gap-2 mb-6">
                    <button v-for="f in ['upcoming', 'past', 'all']" :key="f"
                        @click="setFilter(f)"
                        class="px-5 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition"
                        :class="activeFilter === f ? 'bg-[#780116] text-white shadow-lg shadow-red-200' : 'bg-white text-slate-500 border border-slate-100 hover:bg-slate-50'">
                        {{ f }}
                    </button>
                </div>

                <!-- Reminder List -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div v-if="reminders.data && reminders.data.length > 0" class="divide-y divide-slate-50">
                        <div v-for="reminder in reminders.data" :key="reminder.id"
                            class="flex items-center justify-between px-6 py-5 hover:bg-slate-50/50 transition group">
                            <div class="flex items-center gap-4 min-w-0 flex-1">
                                <div class="p-2.5 rounded-xl flex-shrink-0"
                                    :class="reminder.status === 'pending' ? 'bg-amber-50 text-amber-500' : reminder.status === 'sent' ? 'bg-emerald-50 text-emerald-500' : 'bg-slate-50 text-slate-300'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-black text-slate-900 truncate">{{ reminder.title }}</p>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                                        {{ formatDate(reminder.event_at || reminder.reminder_at) }}
                                        <span v-if="reminder.location" class="ml-2 text-slate-400">· {{ reminder.location }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest bg-slate-50 text-slate-400">
                                    {{ sourceLabel(reminder.source) }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest" :class="statusColor(reminder.status)">
                                    {{ reminder.status }}
                                </span>
                                <div v-if="reminder.status === 'pending'" class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                    <Link :href="route('reminders.edit', reminder.id)" class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </Link>
                                    <button @click="cancelReminder(reminder.id)" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-16 text-center">
                        <div class="size-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-slate-400 font-bold text-sm">No reminders found</p>
                        <p class="text-slate-400 text-xs mt-1 mb-6">Create your first reminder or send a /sendora command</p>
                        <Link :href="route('reminders.create')" class="inline-flex items-center px-8 py-3 bg-[#780116] text-white rounded-xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-xl shadow-red-200">
                            Create Reminder
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="reminders.links && reminders.last_page > 1" class="px-6 py-4 border-t border-slate-50 flex justify-center gap-1">
                        <template v-for="link in reminders.links" :key="link.label">
                            <Link v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                                :class="link.active ? 'bg-[#780116] text-white' : 'text-slate-400 hover:bg-slate-50'"
                                v-html="link.label" />
                            <span v-else class="px-3 py-1.5 text-xs text-slate-300" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
