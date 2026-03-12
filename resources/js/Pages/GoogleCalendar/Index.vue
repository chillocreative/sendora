<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    connection: Object,
});

const syncing = ref(false);
const disconnecting = ref(false);

const syncCalendar = () => {
    syncing.value = true;
    router.post(route('google-calendar.sync'), {}, {
        preserveScroll: true,
        onFinish: () => syncing.value = false,
    });
};

const disconnect = () => {
    if (!confirm('Disconnect Google Calendar? Pending calendar reminders will be cancelled.')) return;
    disconnecting.value = true;
    router.post(route('google-calendar.disconnect'), {}, {
        onFinish: () => disconnecting.value = false,
    });
};

const timeAgo = (dateString) => {
    if (!dateString) return 'Never';
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
};
</script>

<template>
    <AppLayout title="Google Calendar">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">Google Calendar</h2>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

                <!-- Connected State -->
                <div v-if="connection" class="space-y-6">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="p-3 bg-blue-50 rounded-2xl">
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900">Connected</h3>
                                <p class="text-sm text-slate-500 font-medium">{{ connection.google_email }}</p>
                            </div>
                            <div class="ml-auto">
                                <div class="w-3 h-3 rounded-full bg-emerald-400 animate-pulse"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-slate-50 rounded-2xl p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Calendar</p>
                                <p class="text-sm font-bold text-slate-700">{{ connection.calendar_id }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-2xl p-5">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Last Synced</p>
                                <p class="text-sm font-bold text-slate-700">{{ timeAgo(connection.last_synced_at) }}</p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button @click="syncCalendar" :disabled="syncing"
                                class="flex-1 flex items-center justify-center gap-2 px-6 py-4 bg-blue-500 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-blue-600 transition shadow-lg shadow-blue-200 disabled:opacity-50">
                                <svg class="w-4 h-4" :class="syncing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                {{ syncing ? 'Syncing...' : 'Sync Now' }}
                            </button>
                            <button @click="disconnect" :disabled="disconnecting"
                                class="px-6 py-4 bg-white border border-red-200 text-red-500 rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-red-50 transition disabled:opacity-50">
                                Disconnect
                            </button>
                        </div>
                    </div>

                    <div class="bg-blue-50 rounded-2xl border border-blue-100 p-6">
                        <p class="text-sm text-blue-700 font-bold">Events from your Google Calendar are automatically synced every 15 minutes. Each event creates a WhatsApp reminder that will be sent to your connected number.</p>
                    </div>
                </div>

                <!-- Not Connected State -->
                <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-100 p-12 text-center">
                    <div class="size-20 bg-blue-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900 mb-2">Connect Google Calendar</h3>
                    <p class="text-slate-500 text-sm mb-8 max-w-md mx-auto">
                        Link your Google Calendar to automatically receive WhatsApp reminders for your upcoming events and meetings.
                    </p>
                    <Link :href="route('google-calendar.connect')"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-blue-500 text-white rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-blue-600 transition shadow-xl shadow-blue-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        Connect with Google
                    </Link>

                    <div class="mt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                        <div class="bg-slate-50 rounded-2xl p-5">
                            <div class="size-8 bg-white rounded-xl flex items-center justify-center mb-3 shadow-sm">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </div>
                            <p class="text-xs font-black text-slate-700">Auto Sync</p>
                            <p class="text-[10px] text-slate-400 mt-1">Events sync every 15 minutes</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-5">
                            <div class="size-8 bg-white rounded-xl flex items-center justify-center mb-3 shadow-sm">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <p class="text-xs font-black text-slate-700">WhatsApp Alerts</p>
                            <p class="text-[10px] text-slate-400 mt-1">Get reminded via WhatsApp</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-5">
                            <div class="size-8 bg-white rounded-xl flex items-center justify-center mb-3 shadow-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <p class="text-xs font-black text-slate-700">/sendora</p>
                            <p class="text-[10px] text-slate-400 mt-1">Create events via WhatsApp</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
