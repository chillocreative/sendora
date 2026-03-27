<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    numbers: Array,
    deviceLimit: {
        type: Number,
        default: 1
    },
});

const page = usePage();

// Calculate device usage
const devicesUsed = computed(() => props.numbers.length);
const devicesRemaining = computed(() => Math.max(0, props.deviceLimit - props.numbers.length));
const canAddDevice = computed(() => devicesUsed.value < props.deviceLimit);
const usagePercentage = computed(() => (devicesUsed.value / props.deviceLimit) * 100);

const createDevice = () => {
    router.post(route('whatsapp.create'));
};

const deleteDevice = (id) => {
    if (confirm('Are you sure you want to remove this device?')) {
        router.delete(route('whatsapp.destroy', id));
    }
};
</script>

<template>
    <AppLayout title="Device Manager">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Device Manager
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Device Usage & Add Button Card -->
                <div class="bg-white rounded-2xl shadow-xl p-6 mb-8 border border-slate-100">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <!-- Usage Info -->
                        <div class="flex items-center gap-6">
                            <div class="bg-gradient-to-br from-[#780116] to-[#db7c26] w-16 h-16 rounded-2xl flex items-center justify-center shadow-lg shadow-red-200">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">WhatsApp Devices</h3>
                                <p class="text-slate-500 mt-1">
                                    <span class="font-bold text-[#780116]">{{ devicesUsed }}</span> of 
                                    <span class="font-bold">{{ deviceLimit }}</span> devices connected
                                </p>
                                <!-- Progress Bar -->
                                <div class="w-48 h-2 bg-slate-100 rounded-full mt-2 overflow-hidden">
                                    <div 
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="usagePercentage >= 100 ? 'bg-gradient-to-r from-[#d8572a] to-[#c32f27]' : 'bg-gradient-to-r from-[#780116] to-[#f7b538]'"
                                        :style="{ width: `${usagePercentage}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Add Device Button -->
                        <div class="flex flex-col items-end gap-2">
                            <button 
                                v-if="canAddDevice"
                                @click="createDevice"
                                class="bg-gradient-to-r from-[#780116] to-[#db7c26] text-white px-6 py-3 rounded-xl font-bold hover:from-[#c32f27] hover:to-[#d8572a] transition-all shadow-lg shadow-red-100 flex items-center gap-2 transform hover:scale-105"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add New Device
                            </button>
                            <button 
                                v-else
                                disabled
                                class="bg-slate-200 text-slate-500 px-6 py-3 rounded-xl font-bold cursor-not-allowed flex items-center gap-2"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Device Limit Reached
                            </button>
                            <p v-if="canAddDevice" class="text-sm text-slate-400">
                                {{ devicesRemaining }} slot{{ devicesRemaining !== 1 ? 's' : '' }} remaining
                            </p>
                            <Link v-else href="/subscription" class="text-sm text-[#780116] hover:text-[#c32f27] font-medium">
                                Upgrade plan for more devices →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="numbers.length === 0" class="bg-white p-12 rounded-2xl shadow-xl text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-red-50/50 via-transparent to-orange-50/50"></div>
                    
                    <div class="relative z-10">
                        <div class="bg-gradient-to-br from-[#780116] to-[#db7c26] w-24 h-24 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-200">
                            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3">No Connected Devices</h3>
                        <p class="text-gray-500 mb-8 max-w-md mx-auto">Link your WhatsApp account to start broadcasting messages and setting up auto-replies.</p>
                        <button 
                            @click="createDevice"
                            class="bg-gradient-to-r from-[#780116] to-[#db7c26] text-white px-8 py-3.5 rounded-xl font-bold hover:from-[#c32f27] hover:to-[#d8572a] transition-all shadow-xl shadow-red-200 transform hover:scale-105"
                        >
                            Link Your First Device
                        </button>
                    </div>
                </div>

                <!-- Device Cards Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="number in numbers" 
                        :key="number.id" 
                        class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden hover:shadow-2xl transition-all duration-300 group"
                    >
                        <!-- Status Bar on Top -->
                        <div 
                            class="h-1.5 w-full"
                            :class="number.status === 'connected' ? 'bg-gradient-to-r from-[#f7b538] to-[#db7c26]' : 'bg-gradient-to-r from-red-400 to-rose-500'"
                        ></div>
                        
                        <div class="p-6">
                            <!-- Device Header -->
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex items-center">
                                    <div 
                                        class="w-14 h-14 rounded-2xl flex items-center justify-center mr-4 shadow-lg transition-transform group-hover:scale-105"
                                        :class="number.status === 'connected' 
                                            ? 'bg-gradient-to-br from-[#780116] to-[#db7c26] shadow-red-200' 
                                            : 'bg-gradient-to-br from-slate-400 to-slate-500 shadow-slate-300/50'"
                                    >
                                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                        </svg>
                                    </div>
                                    
                                    <div>
                                        <div class="font-bold text-lg text-slate-900 mb-1 max-w-[180px] truncate">
                                            {{ number.phone_number || 'Unnamed Device' }}
                                        </div>
                                        <div class="text-xs text-slate-400 font-mono mb-1">Device ID: {{ number.id }}</div>
                                        <div 
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                            :class="number.status === 'connected' 
                                                ? 'bg-gradient-to-r from-green-50 to-emerald-50 text-green-700 border border-green-200' 
                                                : 'bg-gradient-to-r from-red-50 to-rose-50 text-red-700 border border-red-200'"
                                        >
                                            <span 
                                                class="w-2 h-2 rounded-full mr-2"
                                                :class="number.status === 'connected' ? 'bg-[#f7b538] animate-pulse' : 'bg-red-500'"
                                            ></span>
                                            {{ number.status }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Device Stats -->
                            <div v-if="number.status === 'connected'" class="grid grid-cols-2 gap-4 mb-6 p-4 bg-slate-50 rounded-xl">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-slate-900">{{ number.messages_sent || 0 }}</div>
                                    <div class="text-xs text-slate-500 uppercase tracking-wide">Messages Sent</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-slate-900">{{ number.last_seen || 'Now' }}</div>
                                    <div class="text-xs text-slate-500 uppercase tracking-wide">Last Active</div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <Link 
                                    :href="route('whatsapp.show', number.id)"
                                    class="flex items-center justify-center w-full py-3 px-4 rounded-xl font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-[1.02] active:scale-[0.98]"
                                    :class="number.status === 'connected' 
                                        ? 'bg-gradient-to-r from-[#780116] to-[#db7c26] text-white hover:from-[#c32f27] hover:to-[#d8572a] shadow-red-200' 
                                        : 'bg-gradient-to-r from-[#db7c26] to-[#c32f27] text-white hover:from-[#d8572a] hover:to-[#c32f27] shadow-orange-200'"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="number.status === 'connected'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <circle v-if="number.status === 'connected'" cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    {{ number.status === 'connected' ? 'Manage Session' : 'Scan QR Code' }}
                                </Link>
                                
                                <button 
                                    @click="deleteDevice(number.id)"
                                    class="flex items-center justify-center w-full py-3 px-4 rounded-xl bg-white border-2 border-red-200 text-red-600 font-bold hover:bg-gradient-to-r hover:from-red-50 hover:to-rose-50 hover:border-red-300 transition-all duration-300 shadow-sm hover:shadow-md"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Remove Device
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Device Card (Placeholder) -->
                    <div 
                        v-if="canAddDevice"
                        @click="createDevice"
                        class="bg-white rounded-2xl shadow-lg border-2 border-dashed border-slate-200 overflow-hidden hover:shadow-xl hover:border-red-300 transition-all duration-300 cursor-pointer group min-h-[300px] flex items-center justify-center"
                    >
                        <div class="text-center p-8">
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 group-hover:bg-gradient-to-br group-hover:from-[#780116] group-hover:to-[#db7c26] flex items-center justify-center mx-auto mb-4 transition-all duration-300">
                                <svg class="w-8 h-8 text-slate-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-slate-600 group-hover:text-[#780116] transition-colors">Add New Device</h4>
                            <p class="text-sm text-slate-400 mt-1">{{ devicesRemaining }} slot{{ devicesRemaining !== 1 ? 's' : '' }} available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
