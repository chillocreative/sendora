<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    subscription: Object,
    whatsappCount: Number,
    contactCount: Number,
    chartData: Array,
    overallStats: Object,
    recentCampaigns: Array,
});

// Auto-refresh for live data
let pollInterval;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({
            only: ['whatsappCount', 'contactCount', 'chartData', 'overallStats', 'recentCampaigns'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 10000); // 10 seconds
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// Chart tooltip
const hoveredDay = ref(null);
const tooltipData = ref(null);

const maxSent = computed(() => {
    if (!props.chartData || props.chartData.length === 0) return 1;
    return Math.max(...props.chartData.map(d => d.sent), 5); // Minimum 5 for scale
});

const getBarHeight = (value) => {
    return (value / maxSent.value) * 100;
};

const showTooltip = (day, event) => {
    hoveredDay.value = day.date;
    tooltipData.value = day;
};

const hideTooltip = () => {
    hoveredDay.value = null;
    tooltipData.value = null;
};
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                    Performance Terminal
                </h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
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

                <!-- Performance Rates Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#f7b538]"></div>
                        <div class="text-3xl font-black text-[#f7b538] group-hover:scale-110 transition-transform">{{ overallStats?.send_rate || 0 }}%</div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Send Rate</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">{{ overallStats?.sent || 0 }} / {{ overallStats?.total_messages || 0 }}</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#db7c26]"></div>
                        <div class="text-3xl font-black text-[#db7c26] group-hover:scale-110 transition-transform">{{ overallStats?.delivery_rate || 0 }}%</div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Delivery Rate</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">{{ overallStats?.delivered || 0 }} delivered</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#d8572a]"></div>
                        <div class="text-3xl font-black text-[#d8572a] group-hover:scale-110 transition-transform">{{ overallStats?.open_rate || 0 }}%</div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Open Rate</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">{{ overallStats?.opened || 0 }} opened</p>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1 h-full bg-[#c32f27]"></div>
                        <div class="text-3xl font-black text-[#c32f27] group-hover:scale-110 transition-transform">{{ overallStats?.click_rate || 0 }}%</div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mt-1">Click Rate</p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">{{ overallStats?.clicked || 0 }} clicked</p>
                    </div>
                </div>

                <!-- Campaign Performance Chart -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Live Campaign Performance</h3>
                            <p class="text-sm text-slate-500">Real-time breakdown of message statuses</p>
                        </div>
                        <div class="flex items-center gap-6 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-[#f7b538]"></div>
                                <span class="text-slate-500 font-bold">Sent</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-[#db7c26]"></div>
                                <span class="text-slate-500 font-bold">Delivered</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-[#d8572a]"></div>
                                <span class="text-slate-500 font-bold">Opened</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Container -->
                    <div class="relative h-64">
                        <!-- Y-axis labels -->
                        <div class="absolute left-0 top-0 bottom-8 w-12 flex flex-col justify-between text-[10px] text-slate-400 font-bold">
                            <span>{{ maxSent }}</span>
                            <span>{{ Math.round(maxSent / 2) }}</span>
                            <span>0</span>
                        </div>

                        <!-- Chart Area -->
                        <div class="ml-12 h-full flex items-end gap-2 pb-8 border-b border-slate-50">
                            <div 
                                v-for="(day, index) in chartData" 
                                :key="index"
                                class="flex-1 flex flex-col items-center justify-end h-full gap-1 group relative cursor-pointer hover:bg-slate-50 rounded-t-lg transition-colors"
                                @mouseenter="showTooltip(day, $event)"
                                @mouseleave="hideTooltip"
                            >
                                <!-- Stacked/Layered Bars -->
                                <div class="w-full relative flex items-end px-1" style="height: 100%">
                                    
                                    <!-- Sent Layer (Back) -->
                                    <div 
                                        class="w-full absolute bottom-0 bg-[#f7b538] rounded-t-sm z-10 transition-all duration-500 group-hover:opacity-90"
                                        :style="{ height: getBarHeight(day.sent) + '%' }"
                                    ></div>

                                    <!-- Delivered Layer (Middle) -->
                                    <div 
                                        class="w-full absolute bottom-0 bg-[#db7c26] rounded-t-sm z-20 transition-all duration-500"
                                        :style="{ height: getBarHeight(day.delivered) + '%' }"
                                    ></div>

                                     <!-- Opened Layer (Front) -->
                                    <div 
                                        class="w-full absolute bottom-0 bg-[#d8572a] rounded-t-sm z-30 transition-all duration-500"
                                        :style="{ height: getBarHeight(day.opened) + '%' }"
                                    ></div>
                                </div>
                                
                                <!-- X-axis label -->
                                <span class="text-[9px] text-slate-400 font-bold whitespace-nowrap absolute -bottom-6 group-hover:text-slate-600">
                                    {{ day.date?.split(' ')[1] || '' }}
                                </span>

                                <!-- Tooltip -->
                                <div 
                                    v-if="hoveredDay === day.date"
                                    class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-800 text-white px-4 py-3 rounded-xl text-xs shadow-2xl z-50 whitespace-nowrap border border-slate-700 min-w-[120px]"
                                >
                                    <p class="font-black text-sm mb-2 text-center border-b border-white/10 pb-2">{{ day.date }}</p>
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex justify-between items-center gap-4">
                                            <span class="text-[#f7b538] font-bold">Sent</span>
                                            <span class="font-mono">{{ day.sent }}</span>
                                        </div>
                                        <div class="flex justify-between items-center gap-4">
                                            <span class="text-[#db7c26] font-bold">Delivered</span>
                                            <span class="font-mono">{{ day.delivered }}</span>
                                        </div>
                                        <div class="flex justify-between items-center gap-4">
                                            <span class="text-[#d8572a] font-bold">Opened</span>
                                            <span class="font-mono">{{ day.opened }}</span>
                                        </div>
                                        <div class="flex justify-between items-center gap-4">
                                            <span class="text-[#c32f27] font-bold">Clicked</span>
                                            <span class="font-mono">{{ day.clicked }}</span>
                                        </div>
                                        <div class="flex justify-between items-center gap-4 border-t border-white/10 pt-1 mt-1">
                                            <span class="text-red-400 font-bold">Failed</span>
                                            <span class="font-mono">{{ day.failed }}</span>
                                        </div>
                                    </div>
                                    <!-- Tail -->
                                    <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 border-8 border-transparent border-t-slate-800"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- WhatsApp Numbers -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-slate-500 text-sm font-bold uppercase tracking-wider">WhatsApp Numbers</p>
                                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ whatsappCount }}</h3>
                            </div>
                            <div class="p-3 bg-red-50 text-[#780116] rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            </div>
                        </div>
                        <div>
                             <div class="flex justify-between text-xs font-bold text-slate-400 mb-2">
                                <span>Used</span>
                                <span>{{ subscription?.plan?.limits?.whatsapp_nos ?? $page.props.auth.user.current_plan?.limits?.whatsapp_nos ?? 0 }} Limit</span>
                             </div>
                             <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#f7b538] h-2 rounded-full transition-all duration-1000" :style="{ width: Math.min(100, (whatsappCount / (subscription?.plan?.limits?.whatsapp_nos ?? $page.props.auth.user.current_plan?.limits?.whatsapp_nos ?? 1)) * 100) + '%' }"></div>
                             </div>
                        </div>
                    </div>

                    <!-- Contacts -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-slate-500 text-sm font-bold uppercase tracking-wider">Contacts</p>
                                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ contactCount.toLocaleString() }}</h3>
                            </div>
                            <div class="p-3 bg-red-50 text-[#db7c26] rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            </div>
                        </div>
                        <div>
                             <div class="flex justify-between text-xs font-bold text-slate-400 mb-2">
                                <span>Stored</span>
                                <span>{{ (subscription?.plan?.limits?.contacts ?? $page.props.auth.user.current_plan?.limits?.contacts ?? 0).toLocaleString() }} Limit</span>
                             </div>
                             <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#db7c26] h-2 rounded-full transition-all duration-1000" :style="{ width: Math.min(100, (contactCount / (subscription?.plan?.limits?.contacts ?? $page.props.auth.user.current_plan?.limits?.contacts ?? 1)) * 100) + '%' }"></div>
                             </div>
                        </div>
                    </div>

                    <!-- Total Messages -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col justify-between hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-slate-500 text-sm font-bold uppercase tracking-wider">Total Campaigns</p>
                                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ overallStats?.total_campaigns || 0 }}</h3>
                            </div>
                            <div class="p-3 bg-red-50 text-[#d8572a] rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                            </div>
                        </div>
                        <div>
                             <div class="flex justify-between text-xs font-bold text-slate-400 mb-2">
                                <span>Messages Sent</span>
                                <span>{{ overallStats?.total_messages || 0 }} Total</span>
                             </div>
                             <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-[#d8572a] h-2 rounded-full transition-all duration-1000" :style="{ width: Math.min(100, (overallStats?.sent || 0) / (overallStats?.total_messages || 1) * 100) + '%' }"></div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-900">Recent Campaigns</h3>
                            <p class="text-sm text-slate-500">Performance breakdown of your latest campaigns</p>
                        </div>
                        <a :href="route('reports.export')" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export Report
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table v-if="recentCampaigns && recentCampaigns.length > 0" class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase font-black tracking-widest">
                                    <th class="px-6 py-4">Campaign</th>
                                    <th class="px-6 py-4 text-center">Sent</th>
                                    <th class="px-6 py-4 text-center">Opened</th>
                                    <th class="px-6 py-4 text-center">Clicked</th>
                                    <th class="px-6 py-4 text-center">Open Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="campaign in recentCampaigns" :key="campaign.id" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-black text-slate-900">{{ campaign.name || 'Untitled' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-[#f7b538] font-black">{{ campaign.success_count }}</span>
                                        <span class="text-slate-400 text-xs">/ {{ campaign.total_count }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-[#db7c26]">{{ campaign.opened_count }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-[#c32f27]">{{ campaign.clicked_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest transition-colors" 
                                              :class="campaign.success_count > 0 && campaign.opened_count / campaign.success_count > 0.5 ? 'bg-red-50 text-[#780116]' : 'bg-slate-50 text-slate-400'">
                                            {{ campaign.success_count > 0 ? Math.round((campaign.opened_count / campaign.success_count) * 100) : 0 }}% Efficiency
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-12 text-center">
                            <div class="size-20 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-sm">
                                <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                            </div>
                            <p class="text-slate-400 font-bold uppercase text-[10px] tracking-widest mb-6">No campaigns identified in active sector</p>
                            <Link :href="route('campaigns.create')" class="inline-flex items-center px-8 py-3 bg-[#780116] text-white rounded-xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-xl shadow-red-200">
                                Initialize Campaign
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- SAAS Copywriting & Package Info -->
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 sm:p-12">
                    <div class="flex flex-col lg:flex-row gap-10 lg:gap-12 items-center">
                        <div class="w-full lg:w-1/2">
                            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 mb-4 sm:mb-6 tracking-tight">Powerful WhatsApp Engagement</h3>
                            <p class="text-slate-500 text-base sm:text-lg leading-relaxed mb-6 sm:mb-8">
                                Sendora empowers your business with seamless WhatsApp automation. Connect with your customers like never before through high-speed campaigns, automated replies, and efficient contact management.
                            </p>
                            <div class="flex items-center gap-4">
                                <div class="size-10 sm:size-12 bg-red-50 text-[#780116] rounded-xl sm:rounded-2xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-slate-700 font-bold text-sm sm:text-base">Secure and reliable message delivery</span>
                            </div>
                        </div>

                        <div class="w-full lg:w-1/2 bg-white rounded-[2rem] sm:rounded-[2.5rem] p-6 sm:p-8 border border-red-50 flex flex-col items-center text-center shadow-sm">
                            <div class="size-14 sm:size-16 bg-red-50 rounded-xl sm:rounded-2xl shadow-sm flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 sm:w-8 sm:h-8 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-7.618 3.033A11.954 11.954 0 013 11.239c0 5.391 3.551 10.102 8.454 11.537a1.171 1.171 0 00.708 0c4.903-1.435 8.454-6.146 8.454-11.537a11.954 11.954 0 01-1.382-5.228z"/></svg>
                            </div>
                            <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Your Current Package</p>
                            <h4 class="text-xl sm:text-2xl font-black text-slate-900 mb-6">{{ subscription?.plan?.name ?? $page.props.auth.user.current_plan?.name ?? 'Free Tier' }}</h4>
                            <Link :href="route('subscription.show')" class="w-full sm:w-auto px-8 py-3 bg-white border border-slate-200 text-slate-700 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-colors shadow-sm">
                                Manage Subscription
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
