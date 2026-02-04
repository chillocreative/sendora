<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    isEnabled: Boolean,
    isEligible: Boolean,
    planName: String,
    numbers: Array,
});

const form = useForm({});

const toggleWarmer = () => {
    form.post(route('warmer.toggle'), {
        preserveScroll: true,
    });
};

const togglePool = (id) => {
    form.post(route('warmer.pool.toggle', id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="WhatsApp Warmer">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                WhatsApp Warmer
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Not Eligible Access Denied -->
                <div v-if="!isEligible" class="bg-white rounded-[2.5rem] p-10 shadow-xl border border-slate-100 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-slate-50/50"></div>
                     <div class="relative z-10 max-w-lg mx-auto">
                        <div class="w-20 h-20 bg-slate-100 rounded-3xl flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 mb-4">Premium Feature Locked</h3>
                        <p class="text-slate-500 font-medium mb-8">
                            The "WhatsApp Warmer" protection suite is exclusively available for <span class="text-[#780116] font-bold">Basic, Pro, and Business</span> plans. Protect your numbers with our advanced Human-Stagger AI.
                        </p>
                        <a :href="route('subscription.show')" class="inline-block px-8 py-4 bg-[#780116] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#c32f27] transition shadow-xl shadow-red-200">
                            Upgrade Now
                        </a>
                     </div>
                </div>

                <!-- Eligible Content -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Status Card -->
                    <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden relative">
                         <!-- Status Indicator Bar -->
                        <div class="h-1.5 w-full transition-colors duration-500" :class="isEnabled ? 'bg-green-500' : 'bg-slate-200'"></div>
                        
                        <div class="p-8 md:p-12">
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <h3 class="text-2xl font-black text-slate-900 mb-2">Protection Status</h3>
                                    <p class="text-slate-500 font-medium text-sm">
                                        Current Warmer AI State
                                    </p>
                                </div>
                                <div class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border transition-colors duration-300"
                                     :class="isEnabled ? 'bg-green-50 text-green-600 border-green-100' : 'bg-slate-50 text-slate-400 border-slate-100'">
                                    {{ isEnabled ? 'Active' : 'Inactive' }}
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-center py-10">
                                <button 
                                    @click="toggleWarmer"
                                    class="relative group cursor-pointer outline-none tap-highlight-transparent"
                                    :class="{ 'opacity-70 pointer-events-none': form.processing }"
                                >
                                    <!-- Toggle Background -->
                                    <div class="w-32 h-16 rounded-full transition-all duration-500 shadow-inner" 
                                         :class="isEnabled ? 'bg-green-500/10' : 'bg-slate-100'"></div>
                                    
                                    <!-- Toggle Circle -->
                                    <div class="absolute top-1 left-1 w-14 h-14 rounded-full shadow-lg transition-all duration-500 flex items-center justify-center transform"
                                         :class="[
                                            isEnabled ? 'translate-x-16 bg-green-500' : 'translate-x-0 bg-white',
                                            form.processing ? 'scale-95' : 'scale-100'
                                         ]">
                                        <svg v-if="isEnabled" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        <svg v-else class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                </button>
                            </div>

                            <p class="text-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                                {{ isEnabled ? 'System is actively randomizing message intervals (15-30s)' : 'Standard sending mode active (2-6s)' }}
                            </p>
                        </div>
                    </div>

                    <!-- Info/Explanation Card -->
                    <div class="bg-[#780116] rounded-[2.5rem] shadow-xl shadow-red-200/50 text-white p-8 md:p-12 relative overflow-hidden flex flex-col justify-center">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
                        <div class="absolute bottom-0 left-0 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl -ml-10 -mb-10"></div>
                        
                        <div class="relative z-10">
                            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-8 border border-white/10">
                                <svg class="w-8 h-8 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            
                            <h3 class="text-2xl font-black mb-4">How it works</h3>
                            <p class="text-white/80 leading-relaxed mb-6">
                                When enabled, the <span class="text-[#f7b538] font-bold">Human-Stagger AI</span> increases the interval between messages to a random delay of <span class="font-bold text-white">15 to 30 seconds</span>.
                            </p>
                            <p class="text-white/60 text-sm font-medium leading-relaxed">
                                This mimics natural human typing behavior and drastically reduces the likelihood of being flagged by spam filters. We recommend keeping this enabled for new numbers or during high-volume campaigns.
                            </p>
                        </div>
                    </div>
                    
                    <!-- AI Warmer Pool Section (New) -->
                    <div class="md:col-span-2 bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-8 md:p-12 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-green-50 rounded-full blur-3xl -mr-32 -mt-32 opacity-50 group-hover:opacity-100 transition-opacity"></div>
                        
                        <div class="relative z-10">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-2xl font-black text-slate-900">AI Warmer Pool</h3>
                                        <span class="px-3 py-1 bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-green-100 flex items-center gap-1.5">
                                            <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                                            Live AI Pool
                                        </span>
                                    </div>
                                    <p class="text-slate-500 font-medium">Connect your numbers to the community pool for automatic AI-AI conversations.</p>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 flex items-center gap-4">
                                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Pool Activity</div>
                                        <div class="text-sm font-black text-slate-900 leading-none">AI Engine Active</div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="numbers && numbers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="number in numbers" :key="number.id" 
                                     class="p-6 rounded-[2rem] border transition-all duration-300"
                                     :class="number.is_warmer_pool_enabled ? 'bg-green-50/50 border-green-200 shadow-lg shadow-green-100/50' : 'bg-white border-slate-100 hover:border-slate-200'">
                                    
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center border border-slate-100 shadow-sm relative">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                <div v-if="number.is_warmer_pool_enabled" class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white animate-pulse"></div>
                                            </div>
                                            <div>
                                                <div class="font-black text-slate-900 tracking-tight">{{ number.phone_number }}</div>
                                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Connected Device</div>
                                            </div>
                                        </div>
                                        <button @click="togglePool(number.id)" 
                                                class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                                                :class="number.is_warmer_pool_enabled ? 'bg-green-500 text-white shadow-lg shadow-green-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'">
                                            {{ number.is_warmer_pool_enabled ? 'Joined' : 'Join Pool' }}
                                        </button>
                                    </div>

                                    <div class="flex items-center justify-between px-2 pt-4 border-t border-slate-100/50 mt-4">
                                        <div class="flex flex-col">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Messages/Day</span>
                                            <span class="text-xs font-black text-slate-700">{{ number.warmer_messages_sent_today }} / {{ number.warmer_daily_limit }}</span>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Last AI Comms</span>
                                            <span class="text-xs font-black text-slate-700">{{ number.warmer_last_chatted_at ? 'Just now' : 'Waiting...' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-10 bg-slate-50 rounded-[2rem] border border-dashed border-slate-200">
                                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">No connected numbers found. Add a device to join the pool.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Full Width Tips Section -->
                    <div class="md:col-span-2 bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-8 md:p-12">
                        <h3 class="text-lg font-black text-slate-900 mb-8 flex items-center">
                            <span class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center mr-3 text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            </span>
                            Wait... What about warming up manually?
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div>
                                <h4 class="font-bold text-slate-800 mb-2">Day 1: Start Slow</h4>
                                <p class="text-slate-500 text-sm leading-relaxed">Limit your sending to 50-100 messages. Keep Warmer Mode enabled to ensure maximum safety.</p>
                            </div>
                             <div>
                                <h4 class="font-bold text-slate-800 mb-2">Day 2: Ramp Up</h4>
                                <p class="text-slate-500 text-sm leading-relaxed">Increase volume to 200 messages. Engage in real conversations if users reply.</p>
                            </div>
                             <div>
                                <h4 class="font-bold text-slate-800 mb-2">Day 3: Full Speed</h4>
                                <p class="text-slate-500 text-sm leading-relaxed">You can now aim for 500+ messages. Active engagement from your users helps build reputation score.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
