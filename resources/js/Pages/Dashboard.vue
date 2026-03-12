<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    subscription: Object,
    whatsappCount: Number,
    upcomingReminders: Array,
    reminderStats: Object,
    recentDeliveries: Array,
    calendarConnected: Boolean,
    aiStats: Object,
});

// Auto-refresh
let pollInterval;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['whatsappCount', 'upcomingReminders', 'reminderStats', 'recentDeliveries', 'calendarConnected', 'aiStats'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 15000);
});
onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const timeUntil = (dateString) => {
    if (!dateString) return '';
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((date - now) / 1000);
    if (diff < 0) return 'Overdue';
    if (diff < 60) return 'In < 1 min';
    if (diff < 3600) return `In ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `In ${Math.floor(diff / 3600)}h ${Math.floor((diff % 3600) / 60)}m`;
    return `In ${Math.floor(diff / 86400)} day(s)`;
};

const timeAgo = (dateString) => {
    if (!dateString) return 'Never';
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('en-GB', {
        weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
    });
};

const reminderUsagePercent = computed(() => {
    const limit = props.reminderStats?.plan_limit || 0;
    if (limit === 0) return 0; // unlimited
    return Math.min(100, (props.reminderStats?.created_this_month || 0) / limit * 100);
});

const sourceIcon = (source) => {
    const icons = {
        'web': 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'whatsapp_command': 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'google_calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    };
    return icons[source] || icons.web;
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                    Command Center
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Subscription Alerts -->
                <div v-if="subscription && subscription.status === 'waiting_for_payment'" class="bg-gradient-to-r from-[#db7c26] to-[#c32f27] rounded-2xl shadow-xl p-8 text-white flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <h4 class="text-2xl font-black">Payment Pending</h4>
                        <p class="text-white/80 mt-1">Your subscription to the <span class="font-bold border-b border-white/30">{{ subscription.plan.name }}</span> plan is waiting for payment.</p>
                    </div>
                    <Link :href="route('checkout')" class="px-8 py-4 bg-white text-[#c32f27] font-bold rounded-xl hover:bg-orange-50 transition-all shadow-lg">
                        Complete Payment
                    </Link>
                </div>

                <!-- Quick Stats Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <!-- WhatsApp Status -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-emerald-400"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">WhatsApp</p>
                                <div class="text-3xl font-black text-slate-800 mt-1">{{ whatsappCount }}</div>
                                <p class="text-xs text-slate-500 font-medium mt-1">Connected</p>
                            </div>
                            <div class="p-2.5 bg-emerald-50 text-emerald-500 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders This Month -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#780116]"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Reminders</p>
                                <div class="text-3xl font-black text-slate-800 mt-1">{{ reminderStats?.created_this_month || 0 }}</div>
                                <p class="text-xs text-slate-500 font-medium mt-1">
                                    {{ reminderStats?.plan_limit === 0 ? 'Unlimited' : `of ${reminderStats?.plan_limit} limit` }}
                                </p>
                            </div>
                            <div class="p-2.5 bg-red-50 text-[#780116] rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div v-if="reminderStats?.plan_limit > 0" class="mt-3">
                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-[#780116] h-1.5 rounded-full transition-all duration-1000" :style="{ width: reminderUsagePercent + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Sent Today -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#f7b538]"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Sent Today</p>
                                <div class="text-3xl font-black text-slate-800 mt-1">{{ reminderStats?.sent_today || 0 }}</div>
                                <p class="text-xs text-slate-500 font-medium mt-1">{{ reminderStats?.sent_this_month || 0 }} this month</p>
                            </div>
                            <div class="p-2.5 bg-amber-50 text-[#f7b538] rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar Status -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full" :class="calendarConnected ? 'bg-blue-400' : 'bg-slate-200'"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Google Calendar</p>
                                <div class="text-lg font-black mt-2" :class="calendarConnected ? 'text-blue-600' : 'text-slate-400'">
                                    {{ calendarConnected ? 'Connected' : 'Not Connected' }}
                                </div>
                            </div>
                            <Link :href="route('google-calendar.index')" class="p-2.5 rounded-xl transition" :class="calendarConnected ? 'bg-blue-50 text-blue-500' : 'bg-slate-50 text-slate-300'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Reminders -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Upcoming Reminders</h3>
                            <p class="text-sm text-slate-500">Your next scheduled reminders</p>
                        </div>
                        <Link :href="route('reminders.create')" class="flex items-center gap-2 px-5 py-2.5 bg-[#780116] text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-[#c32f27] transition shadow-lg shadow-red-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            New Reminder
                        </Link>
                    </div>

                    <div v-if="upcomingReminders && upcomingReminders.length > 0" class="divide-y divide-slate-50">
                        <div v-for="reminder in upcomingReminders" :key="reminder.id"
                            class="flex items-center justify-between px-6 py-4 hover:bg-slate-50/50 transition group">
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="p-2.5 rounded-xl bg-red-50 text-[#780116] flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-black text-slate-900 truncate">{{ reminder.title }}</p>
                                    <p class="text-xs text-slate-500 font-medium">
                                        {{ formatDate(reminder.event_at || reminder.reminder_at) }}
                                        <span v-if="reminder.location" class="ml-2 text-slate-400">· {{ reminder.location }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-amber-50 text-amber-600">
                                    {{ timeUntil(reminder.reminder_at) }}
                                </span>
                                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path :d="sourceIcon(reminder.source)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-12 text-center">
                        <div class="size-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-slate-400 font-bold text-sm">No upcoming reminders</p>
                        <p class="text-slate-400 text-xs mt-1">Create a reminder or send a /sendora command</p>
                    </div>
                </div>

                <!-- AI Playbook Section -->
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl shadow-xl border border-slate-700/50 overflow-hidden">
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-emerald-500/20 rounded-xl">
                                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white">AI Autopilot</h3>
                                    <p class="text-xs text-slate-400 font-medium">Real-time AI conversation engine status</p>
                                </div>
                            </div>
                            <Link :href="route('playbooks.index')" class="flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-xl text-xs font-bold hover:bg-white/20 transition backdrop-blur-sm border border-white/10">
                                Manage Playbooks
                            </Link>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Active Playbooks</span>
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-white">{{ aiStats?.active_playbooks || 0 }}</div>
                                <p class="text-xs text-slate-500 mt-1">of {{ aiStats?.total_playbooks || 0 }} total</p>
                            </div>
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">AI Replies Today</span>
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-white">{{ aiStats?.ai_replies_today || 0 }}</div>
                                <p class="text-xs text-slate-500 mt-1">{{ aiStats?.ai_replies_this_month || 0 }} this month</p>
                            </div>
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full bg-amber-400"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Active Chats</span>
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-white">{{ aiStats?.active_conversations || 0 }}</div>
                                <p class="text-xs text-slate-500 mt-1">{{ aiStats?.total_conversations || 0 }} total</p>
                            </div>
                            <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-2 h-2 rounded-full" :class="(aiStats?.escalated_conversations || 0) > 0 ? 'bg-red-400 animate-pulse' : 'bg-slate-500'"></div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Escalated</span>
                                </div>
                                <div class="text-2xl sm:text-3xl font-black" :class="(aiStats?.escalated_conversations || 0) > 0 ? 'text-red-400' : 'text-white'">{{ aiStats?.escalated_conversations || 0 }}</div>
                                <p class="text-xs text-slate-500 mt-1">{{ aiStats?.numbers_with_ai || 0 }} numbers with AI</p>
                            </div>
                        </div>

                        <!-- Recent AI Conversations -->
                        <div v-if="aiStats?.recent_conversations && aiStats.recent_conversations.length > 0">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Live Conversations</h4>
                            <div class="space-y-2">
                                <div v-for="conv in aiStats.recent_conversations" :key="conv.id"
                                    class="flex items-center justify-between p-3 sm:p-4 bg-white/5 rounded-xl border border-white/5 hover:bg-white/10 transition group">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0"
                                            :class="conv.status === 'escalated' ? 'bg-red-500/20' : 'bg-emerald-500/20'">
                                            <svg v-if="conv.status === 'escalated'" class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                            <svg v-else class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-white truncate">{{ conv.contact_name || conv.contact_phone }}</div>
                                            <div class="text-[10px] text-slate-500 font-medium truncate">{{ conv.message_count }} messages</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-500 font-bold whitespace-nowrap">{{ timeAgo(conv.last_customer_message_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-slate-500 text-xs font-bold">No active AI conversations yet</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Deliveries -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h3 class="text-lg font-black text-slate-900">Recent Deliveries</h3>
                        <p class="text-sm text-slate-500">Status of your latest reminder notifications</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table v-if="recentDeliveries && recentDeliveries.length > 0" class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                    <th class="px-6 py-4">Reminder</th>
                                    <th class="px-6 py-4 text-center">Source</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-right">Sent</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="delivery in recentDeliveries" :key="delivery.id" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-black text-slate-900">{{ delivery.title }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest"
                                              :class="{
                                                'bg-blue-50 text-blue-600': delivery.source === 'google_calendar',
                                                'bg-emerald-50 text-emerald-600': delivery.source === 'whatsapp_command',
                                                'bg-slate-50 text-slate-500': delivery.source === 'web',
                                              }">
                                            {{ delivery.source === 'whatsapp_command' ? '/sendora' : delivery.source === 'google_calendar' ? 'Calendar' : 'Web' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"
                                              :class="delivery.status === 'sent' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                                            {{ delivery.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-xs text-slate-500 font-bold">
                                        {{ timeAgo(delivery.sent_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-12 text-center">
                            <p class="text-slate-400 font-bold text-sm">No deliveries yet</p>
                            <p class="text-slate-400 text-xs mt-1">Reminders will appear here once sent</p>
                        </div>
                    </div>
                </div>

                <!-- /sendora Quick Reference + Package Info -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- /sendora Command Reference -->
                    <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl shadow-xl p-8 text-white">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2.5 bg-[#780116]/30 rounded-xl">
                                <svg class="w-5 h-5 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black">/sendora Commands</h3>
                                <p class="text-xs text-slate-400">Send to your own WhatsApp number</p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Set a Meeting</p>
                                <code class="text-sm text-emerald-300 font-mono">/sendora Meeting with John tomorrow at 3pm in my office</code>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Birthday Reminder</p>
                                <code class="text-sm text-emerald-300 font-mono">/sendora Sarah's birthday on 25/04/2026</code>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Daily Standup</p>
                                <code class="text-sm text-emerald-300 font-mono">/sendora Daily standup every day at 9am</code>
                            </div>
                        </div>
                    </div>

                    <!-- Package Info -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 flex flex-col justify-between">
                        <div>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Your Current Package</p>
                            <h4 class="text-2xl font-black text-slate-900 mb-6">{{ subscription?.plan?.name ?? 'No Plan' }}</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 text-sm">
                                    <svg class="w-4 h-4 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-slate-600 font-bold">{{ subscription?.plan?.limits?.whatsapp_nos || 0 }} WhatsApp Numbers</span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <svg class="w-4 h-4 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-slate-600 font-bold">
                                        {{ subscription?.plan?.limits?.reminders_per_month === 0 ? 'Unlimited' : (subscription?.plan?.limits?.reminders_per_month || 0) }} Reminders / month
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <svg class="w-4 h-4 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span class="text-slate-600 font-bold">Google Calendar Sync</span>
                                </div>
                            </div>
                        </div>
                        <Link :href="route('subscription.show')" class="mt-8 w-full text-center px-8 py-3 bg-white border border-slate-200 text-slate-700 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                            Manage Subscription
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
