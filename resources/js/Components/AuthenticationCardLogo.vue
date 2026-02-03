<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'" class="flex flex-col items-center group">
        <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mb-6 shadow-2xl shadow-black/20 group-hover:scale-110 transition-transform duration-500">
            <svg class="w-10 h-10 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
        </div>
        <span class="text-4xl font-black tracking-tighter text-white">Sendora</span>
    </Link>
</template>
