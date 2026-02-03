<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import axios from 'axios';

defineProps({
    numbers: Array,
});

const testForm = reactive({
    phone: '',
    message: '',
    processing: false,
    success: false,
    error: null
});

const createDevice = () => {
    router.post(route('whatsapp.create'));
};

const deleteDevice = (id) => {
    if (confirm('Are you sure you want to remove this device?')) {
        router.delete(route('whatsapp.destroy', id));
    }
};

const sendTestMessage = async () => {
    testForm.processing = true;
    testForm.success = false;
    testForm.error = null;

    try {
        await axios.post(route('test-message.send'), {
            phone: testForm.phone,
            message: testForm.message
        });

        testForm.success = true;
        testForm.phone = '';
        testForm.message = '';

        // Clear success message after 3 seconds
        setTimeout(() => {
            testForm.success = false;
        }, 3000);
    } catch (error) {
        testForm.error = error.response?.data?.message || 'Failed to send message. Please try again.';
        
        // Clear error message after 5 seconds
        setTimeout(() => {
            testForm.error = null;
        }, 5000);
    } finally {
        testForm.processing = false;
    }
};
</script>

<template>
    <AppLayout title="System WhatsApp">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                System WhatsApp
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- System Header Card -->
                <div class="bg-white rounded-[2.5rem] shadow-xl p-8 mb-10 border border-slate-100 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#780116]/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 mb-2">System Notification Device</h3>
                            <p class="text-slate-500 max-w-lg font-medium">Connect your WhatsApp here to receive system-wide alerts, system health notifications, and automated alerts.</p>
                        </div>
                        <button 
                            @click="createDevice"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-[#780116] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#c32f27] transition shadow-xl shadow-red-200 transform hover:scale-105 active:scale-95"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            Connect Device
                        </button>
                    </div>
                </div>

                <div v-if="numbers.length === 0" class="bg-white p-20 rounded-[3rem] shadow-xl text-center border border-slate-50">
                    <div class="bg-red-50 w-24 h-24 rounded-[2rem] flex items-center justify-center mx-auto mb-8 shadow-lg shadow-red-100 transform -rotate-12">
                        <svg class="w-12 h-12 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">No System Device Connected</h3>
                    <p class="text-slate-500 mb-10 max-w-sm mx-auto font-medium leading-relaxed">Push system updates and critical alerts directly to your WhatsApp. Start by linking your admin account.</p>
                    <button 
                        @click="createDevice"
                        class="bg-[#780116] text-white px-10 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#c32f27] transition shadow-2xl shadow-red-200 transform hover:scale-105 active:scale-95"
                    >
                        Connect WhatsApp Now
                    </button>
                </div>

                <div v-else class="space-y-8">
                    <!-- Connected Devices - Horizontal Layout on Top -->
                    <div class="space-y-6">
                        <div v-for="number in numbers" :key="number.id" class="bg-white rounded-[2.5rem] shadow-xl border border-slate-50 overflow-hidden hover:shadow-2xl transition-all duration-500 group">
                            <div class="h-1.5 w-full bg-gradient-to-r from-[#780116] to-[#db7c26]"></div>
                            <div class="p-8">
                                <div class="flex items-center justify-between">
                                    <!-- Left: Icon + Info -->
                                    <div class="flex items-center flex-1">
                                        <!-- WhatsApp Icon -->
                                        <div class="w-20 h-20 rounded-[1.75rem] flex items-center justify-center bg-gradient-to-br from-[#780116] to-[#db7c26] mr-6 shadow-xl shadow-red-200/50 flex-shrink-0 transform group-hover:scale-110 transition-transform duration-500">
                                            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                            </svg>
                                        </div>
                                        
                                        <!-- Device Info -->
                                        <div class="flex-1">
                                            <div class="font-black text-xl text-slate-900 mb-2 truncate max-w-xs">{{ number.phone_number || 'Disconnected System Device' }}</div>
                                            
                                            <!-- Status Badge -->
                                            <div class="inline-flex items-center px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest"
                                                 :class="number.status === 'connected' 
                                                    ? 'bg-red-50 text-[#780116] border border-red-100' 
                                                    : 'bg-slate-50 text-slate-400 border border-slate-100'">
                                                <span class="w-2.5 h-2.5 rounded-full mr-2.5 shadow-sm"
                                                      :class="number.status === 'connected' ? 'bg-[#f7b538] animate-pulse' : 'bg-slate-300'">
                                                </span>
                                                {{ number.status }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: Action Buttons (Icon Only) -->
                                    <div class="flex items-center gap-4 ml-6">
                                        <Link 
                                            :href="route('whatsapp.show', number.id)"
                                            class="flex items-center justify-center w-14 h-14 rounded-2xl font-bold transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-110 active:scale-95 group/btn"
                                            :class="number.status === 'connected' 
                                                ? 'bg-[#780116] text-white hover:bg-[#c32f27]' 
                                                : 'bg-[#db7c26] text-white hover:bg-[#d8572a]'"
                                            :title="number.status === 'connected' ? 'Manage Session' : 'Scan QR Code'"
                                        >
                                            <svg class="w-7 h-7 group-hover/btn:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path v-if="number.status === 'connected'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                <circle v-if="number.status === 'connected'" cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                            </svg>
                                        </Link>
                                        
                                        <button 
                                            @click="deleteDevice(number.id)"
                                            class="flex items-center justify-center w-14 h-14 rounded-2xl bg-white border border-red-100 text-red-400 font-bold hover:bg-red-50 hover:text-red-600 transition-all duration-300 shadow-sm hover:shadow-xl transform hover:scale-110 active:scale-95"
                                            title="Disconnect Device"
                                        >
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Send Test Message Card - Compact Version -->
                    <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-50 overflow-hidden hover:shadow-2xl transition-all duration-500 relative">
                        <!-- Gradient Background Overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-red-50/40 via-orange-50/30 to-white pointer-events-none"></div>
                        
                        <div class="p-10 relative z-10">
                            <!-- Header with Icon -->
                            <div class="flex items-center mb-8">
                                <div class="w-16 h-16 rounded-2xl flex items-center justify-center bg-gradient-to-br from-[#780116] to-[#db7c26] mr-5 shadow-xl shadow-red-200/50 transform group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-base text-slate-900">Send Test Message</div>
                                    <div class="text-xs text-slate-500 mt-0.5">Quick WhatsApp test</div>
                                </div>
                            </div>

                            <form @submit.prevent="sendTestMessage" class="space-y-3">
                                <!-- Phone Number Input -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Phone Number</label>
                                    <input 
                                        v-model="testForm.phone"
                                        type="text"
                                        placeholder="60123456789"
                                        class="w-full px-5 py-4 rounded-2xl border border-slate-100 bg-white/80 backdrop-blur-sm focus:border-[#780116] focus:ring-4 focus:ring-red-100 transition-all outline-none text-slate-900 placeholder-slate-300 font-bold"
                                        required
                                    />
                                    <p class="text-[10px] text-slate-400 font-bold mt-2 ml-1 uppercase tracking-wider">Include country code without (+)</p>
                                </div>

                                <!-- Message Textarea -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Message</label>
                                    <textarea 
                                        v-model="testForm.message"
                                        rows="3"
                                        placeholder="Type your test message..."
                                        class="w-full px-5 py-4 rounded-2xl border border-slate-100 bg-white/80 backdrop-blur-sm focus:border-[#780116] focus:ring-4 focus:ring-red-100 transition-all outline-none resize-none text-slate-900 placeholder-slate-300 font-bold"
                                        required
                                    ></textarea>
                                </div>

                                <!-- Submit Button -->
                                <button 
                                    type="submit"
                                    :disabled="testForm.processing"
                                    class="w-full py-5 px-4 rounded-2xl bg-[#780116] text-white font-black text-sm uppercase tracking-widest hover:bg-[#c32f27] transition-all duration-300 shadow-xl shadow-red-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98]"
                                >
                                    <span v-if="testForm.processing" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Sending...
                                    </span>
                                    <span v-else class="flex items-center justify-center gap-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        Send Test Message
                                    </span>
                                </button>
                            </form>

                            <!-- Success Message -->
                            <div v-if="testForm.success" class="mt-3 p-3 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg shadow-md animate-fade-in">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-xs text-green-800 font-semibold">Message sent successfully!</p>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div v-if="testForm.error" class="mt-3 p-3 bg-gradient-to-r from-red-50 to-rose-50 border-2 border-red-200 rounded-lg shadow-md animate-fade-in">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-red-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-xs text-red-800 font-semibold">{{ testForm.error }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
