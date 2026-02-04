<template>
    <AppLayout title="Send Test Message">
        <div class="py-8">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-10 text-center sm:text-left">
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight leading-none mb-4">Signal Integrity Test</h2>
                    <p class="text-slate-500 font-bold uppercase text-[10px] tracking-[0.2em]">Validate node connectivity via manual transmission</p>
                </div>

                <!-- WhatsApp Status Card -->
                <div v-if="whatsappNumbers.length > 0" class="mb-8 bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 p-8 border border-slate-100 transition-all hover:shadow-2xl">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="flex items-center space-x-6 text-center sm:text-left flex-col sm:flex-row">
                            <div class="w-20 h-20 rounded-3xl bg-red-50 text-[#780116] flex items-center justify-center relative">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#f7b538] opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-[#f7b538]"></span>
                                </span>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 text-xl tracking-tight leading-none mb-2">Protocol Interface</h3>
                                <p class="text-xs font-black tracking-widest uppercase text-[#db7c26]">
                                    {{ whatsappNumbers.length }} Uplinks Synchronized
                                </p>
                            </div>
                        </div>
                        <Link :href="route('whatsapp.index')"
                               class="px-8 py-4 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-[#c32f27] transition shadow-xl shadow-red-200">
                            Manage Nodes
                        </Link>
                    </div>
                </div>

                <!-- No WhatsApp Warning -->
                <div v-else class="mb-8 bg-red-50 border border-red-100 rounded-[2.5rem] p-10 text-center">
                    <div class="w-20 h-20 bg-white shadow-xl shadow-red-200/50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-2">Node Encryption Required</h3>
                    <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8">No transmission hardware identified in active sector</p>
                    <Link :href="route('whatsapp.index')" class="inline-flex items-center px-10 py-4 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-xl shadow-red-200">
                        Launch Command Center
                    </Link>
                </div>

                <!-- Send Message Form -->
                <div v-if="whatsappNumbers.length > 0" class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 p-10 border border-slate-100">
                    <form @submit.prevent="sendMessage">
                        <!-- Sender Account -->
                         <div class="mb-8">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                Origin Node
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-[#780116] transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <select 
                                    v-model="form.whatsapp_number_id" 
                                    class="w-full pl-14 pr-10 py-5 bg-slate-50/50 border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all font-bold appearance-none"
                                    required
                                >
                                    <option v-for="number in whatsappNumbers" :key="number.id" :value="number.id">
                                        {{ number.name || 'Unnamed Account' }} ({{ number.phone_number || number.id }})
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </div>
                            </div>
                        </div>
                        <!-- Phone Number -->
                        <div class="mb-8">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                Target Destination
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-[#780116] transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                </div>
                                <input 
                                    v-model="form.phone" 
                                    type="text" 
                                    placeholder="e.g., 60123456789"
                                    class="w-full pl-14 pr-6 py-5 border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all bg-slate-50/50 placeholder:text-slate-300 font-bold"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="mb-8">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                Payload Metadata
                            </label>
                            <textarea 
                                v-model="form.message" 
                                rows="5"
                                placeholder="Type your test message or media caption here..."
                                class="w-full px-6 py-5 border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all bg-slate-50/50 placeholder:text-slate-300 font-bold resize-none"
                            ></textarea>
                            <div class="flex justify-end mt-2">
                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ form.message?.length || 0 }} bits utilized</span>
                            </div>
                        </div>

                        <!-- Media Upload -->
                        <div class="mb-10">
                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4">
                                Attach Protocol Asset
                            </label>
                            <div class="mt-1 flex justify-center px-10 pt-10 pb-10 border-2 border-slate-100 border-dashed rounded-[2.5rem] hover:border-[#db7c26] transition-all bg-slate-50/30 group">
                                <div class="space-y-4 text-center">
                                    <div v-if="!form.media" class="relative">
                                        <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mx-auto shadow-xl shadow-slate-200 group-hover:scale-110 transition-transform">
                                            <svg class="w-10 h-10 text-slate-200 group-hover:text-[#db7c26] transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div v-else class="flex flex-col items-center">
                                        <div class="w-20 h-20 bg-red-50 rounded-3xl text-[#780116] flex items-center justify-center mb-4 shadow-xl shadow-red-100">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                        </div>
                                        <p class="text-sm font-black text-slate-900 tracking-tight">{{ form.media.name }}</p>
                                        <button type="button" @click="form.media = null" class="text-[10px] text-[#db7c26] font-black uppercase tracking-widest mt-2 hover:underline">Purge Asset</button>
                                    </div>
                                    <div v-if="!form.media" class="flex flex-col text-sm">
                                        <label for="file-upload" class="relative cursor-pointer rounded-md font-black text-[#780116] hover:text-[#c32f27] transition-colors mb-2">
                                            <span class="text-xs uppercase tracking-[0.2em]">Select Infrastructure File</span>
                                            <input id="file-upload" name="file-upload" type="file" class="sr-only" @change="e => form.media = e.target.files[0]">
                                        </label>
                                        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">PNG, JPG, PDF, MP4 • 20MB Architecture Limit</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response Feedback -->
                        <div v-if="error || success" class="mb-10 animate-fade-in">
                            <div v-if="error" class="bg-red-50 border border-red-100 rounded-[1.5rem] p-6 flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-[#780116] font-black uppercase tracking-widest">{{ error }}</p>
                            </div>

                            <div v-if="success" class="bg-orange-50 border border-orange-100 rounded-[1.5rem] p-6 flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                                    <svg class="w-6 h-6 text-[#db7c26]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-[#780116] font-black uppercase tracking-widest">{{ success }}</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-10 mt-6 border-t border-slate-50">
                            <button 
                                type="button"
                                @click="clearForm"
                                class="w-full sm:w-auto px-10 py-5 border border-slate-200 text-slate-400 rounded-3xl hover:bg-slate-50 transition font-black text-[11px] uppercase tracking-[0.2em]"
                            >
                                Clear Payload
                            </button>
                            <button 
                                type="submit"
                                :disabled="sending"
                                class="w-full sm:w-auto px-12 py-5 bg-[#780116] text-white rounded-[2rem] hover:bg-[#c32f27] transition font-black text-[11px] uppercase tracking-[0.2em] shadow-2xl shadow-red-900/40 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-4 transform active:scale-95"
                            >
                                <svg v-if="sending" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>{{ sending ? 'Transmitting...' : 'Initialize Uplink' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    whatsappNumbers: Array,
});

const form = reactive({
    phone: '',
    whatsapp_number_id: props.whatsappNumbers.length > 0 ? props.whatsappNumbers[0].id : '',
    message: '',
    media: null,
});

const sending = ref(false);
const error = ref(null);
const success = ref(null);

const sendMessage = async () => {
    sending.value = true;
    error.value = null;
    success.value = null;

    router.post(route('test-message.send'), form, {
        preserveScroll: true,
        onSuccess: () => {
            success.value = 'Message sent successfully! ✅';
            clearForm();
            setTimeout(() => {
                success.value = null;
            }, 5000);
        },
        onError: (errors) => {
            error.value = errors.message || 'Failed to send message. Please try again.';
        },
        onFinish: () => {
            sending.value = false;
        },
    });
};

const clearForm = () => {
    form.phone = '';
    form.message = '';
    form.media = null;
    error.value = null;
    success.value = null;
};
</script>
