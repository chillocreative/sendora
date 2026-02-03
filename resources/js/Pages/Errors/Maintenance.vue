<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <Head title="Maintenance Mode" />

    <div class="relative min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6 text-center">
        <!-- Background Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-teal-50 rounded-full blur-[120px] opacity-60"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-purple-50 rounded-full blur-[120px] opacity-60"></div>
        </div>

        <div class="relative z-10 max-w-lg w-full">
            <div class="mb-10 inline-flex p-6 rounded-[2.5rem] bg-white shadow-2xl shadow-teal-100/50">
                <svg class="w-20 h-20 text-teal-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>

            <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-6 tracking-tight leading-tight">
                Under Maintenance
            </h1>
            
            <p class="text-lg text-slate-500 font-medium mb-10 leading-relaxed">
                We're fine-tuning the gears to give you a better experience. We'll be back online in a few minutes. Thanks for your patience!
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/" class="px-8 py-4 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-800 transition shadow-2xl shadow-slate-200">
                    Refresh Page
                </a>
                <Link v-if="$page.props.auth?.user?.is_admin" :href="route('admin.dashboard')" class="px-8 py-4 bg-white border border-slate-200 text-slate-600 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-slate-50 transition">
                    Admin Access
                </Link>
            </div>
            
            <p class="mt-12 text-[10px] font-black uppercase tracking-[0.3em] text-slate-300">
                &copy; {{ new Date().getFullYear() }} Sendora All Rights Reserved
            </p>
        </div>
    </div>
</template>
