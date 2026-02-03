<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    number: Object,
});

const refreshInterval = ref(null);

const refreshQr = () => {
    router.reload({ only: ['number'] });
};

onMounted(() => {
    // Auto-refresh every 5 seconds if not connected
    if (props.number.status !== 'connected') {
        refreshInterval.value = setInterval(() => {
            refreshQr();
        }, 5000);
    }
});

onUnmounted(() => {
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});
</script>

<template>
    <AppLayout title="Link Device">
        <template #header>
            <div class="flex items-center">
                <Link :href="route('whatsapp.index')" class="mr-4 text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Link Device via QR Code
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-12">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-6">How to connect?</h3>
                            <ol class="space-y-6">
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-4 shrink-0">1</span>
                                    <p class="text-gray-700">Open WhatsApp on your phone</p>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-4 shrink-0">2</span>
                                    <p class="text-gray-700">Tap <strong>Menu</strong> or <strong>Settings</strong> and select <strong>Linked Devices</strong></p>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 font-bold mr-4 shrink-0">3</span>
                                    <p class="text-gray-700">Point your phone to this screen to capture the code</p>
                                </li>
                            </ol>

                            <div v-if="number.status === 'connected'" class="mt-12 p-4 bg-green-50 rounded-xl border border-green-100 flex items-center">
                                <div class="w-10 h-10 rounded-full bg-green-200 flex items-center justify-center text-green-700 mr-4">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-green-800">Device Connected</div>
                                    <div class="text-sm text-green-700 text-opacity-80">Ready to send messages.</div>
                                </div>
                            </div>

                            <div v-else-if="number.status === 'connecting'" class="mt-12 p-4 bg-blue-50 rounded-xl border border-blue-100 flex items-center">
                                <div class="w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 mr-4">
                                    <svg class="w-6 h-6 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-blue-800">Connecting...</div>
                                    <div class="text-sm text-blue-700 text-opacity-80">Please wait while we generate your QR code.</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center justify-center p-8 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                            <!-- Connected State -->
                            <div v-if="number.status === 'connected'" class="text-center">
                                <div class="w-64 h-64 flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="w-24 h-24 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-lg font-bold text-gray-700">Connected!</p>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Display -->
                            <div v-else-if="number.qr_code" class="bg-white p-4 rounded-xl shadow-lg mb-6">
                                <img :src="number.qr_code" alt="QR Code" class="w-64 h-64 object-contain" />
                            </div>

                            <!-- Loading State -->
                            <div v-else class="bg-white p-4 rounded-xl shadow-lg mb-6">
                                <div class="w-64 h-64 bg-gray-100 flex items-center justify-center border border-gray-100 rounded">
                                    <div class="text-center p-4">
                                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <span class="text-sm text-gray-400">Generating QR Code...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <p v-if="number.status !== 'connected'" class="text-sm text-gray-500 text-center">
                                The QR code will refresh automatically. <br>
                                <button @click="refreshQr" class="font-bold text-indigo-600 hover:text-indigo-800 underline cursor-pointer mt-2">
                                    Manual Refresh
                                </button>
                            </p>

                            <!-- Status Badge -->
                            <div class="mt-4">
                                <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
                                      :class="{
                                        'bg-green-100 text-green-700': number.status === 'connected',
                                        'bg-blue-100 text-blue-700': number.status === 'connecting',
                                        'bg-yellow-100 text-yellow-700': number.status === 'qr_ready',
                                        'bg-gray-100 text-gray-700': number.status === 'disconnected'
                                      }">
                                    {{ number.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

