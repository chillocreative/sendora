<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    status: Object,
});
</script>

<template>
    <AppLayout title="Server Health">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Nexus Health Monitor
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Status Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                     <!-- System Status -->
                    <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-100 p-8 relative">
                        <div class="absolute top-8 right-8">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                                :class="status.database === 'Online' ? 'bg-red-50 text-[#780116]' : 'bg-red-50 text-[#780116]'"
                            >
                                <span class="w-1.5 h-1.5 mr-2 rounded-full animate-pulse" :class="status.database === 'Online' ? 'bg-[#f7b538]' : 'bg-red-500'"></span>
                                {{ status.database === 'Online' ? 'Node Online' : 'Anomaly Detected' }}
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-8 tracking-tight">System Status</h3>
                        
                        <div class="space-y-6">
                            <div class="flex items-center justify-between p-6 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center">
                                    <div class="size-12 bg-white rounded-xl flex items-center justify-center text-[#780116] shadow-sm border border-slate-100 mr-5">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-sm">Primary Database</div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">MySQL / {{ status.php_version }}</div>
                                    </div>
                                </div>
                                <div class="text-[10px] font-black uppercase tracking-[0.2em]" :class="status.database === 'Online' ? 'text-[#780116]' : 'text-red-600'">{{ status.database }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Resource Usage -->
                     <div class="bg-slate-900 rounded-[2rem] shadow-2xl overflow-hidden border border-slate-800 p-8 text-white">
                        <h3 class="text-xl font-black mb-10 tracking-tight">Resource Telemetry</h3>
                         <div class="space-y-10">
                            <div>
                                <div class="flex justify-between items-end mb-4">
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 block mb-1">Volumetric Capacity</span>
                                        <span class="text-sm font-black text-slate-200">Disk Space Utilization</span>
                                    </div>
                                    <span class="font-black text-[#f7b538] text-sm tracking-tighter">{{ status.disk_usage }}%</span>
                                </div>
                                <div class="w-full bg-slate-800/50 rounded-full h-3 p-0.5 border border-slate-800">
                                    <div class="bg-gradient-to-r from-[#780116] to-[#f7b538] h-full rounded-full transition-all duration-1000 shadow-lg shadow-orange-950/20" :style="`width: ${status.disk_usage}%`"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex justify-between items-end mb-4">
                                    <div>
                                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 block mb-1">Volatile Memory</span>
                                        <span class="text-sm font-black text-slate-200">PHP Memory Load</span>
                                    </div>
                                    <span class="font-black text-[#db7c26] text-sm tracking-tighter">{{ status.memory_usage }}</span>
                                </div>
                                <div class="w-full bg-slate-800/50 rounded-full h-3 p-0.5 border border-slate-800">
                                    <div class="bg-gradient-to-r from-[#780116] to-[#db7c26] h-full rounded-full transition-all duration-1000 shadow-lg shadow-red-950/20" style="width: 25%"></div>
                                </div>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
