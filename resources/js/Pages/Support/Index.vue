<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Support Center">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                Support Center
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight mb-4">How can we help?</h1>
                    <p class="text-lg sm:text-xl text-slate-500 font-medium">Browse our resources or submit a support ticket.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
                    <Link :href="route('tickets.create')" class="bg-white p-10 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 hover:border-[#780116] transition-colors group block">
                        <div class="size-16 bg-red-50 text-[#780116] rounded-2xl flex items-center justify-center mb-6 group-hover:bg-[#780116] group-hover:text-white transition-colors shadow-lg shadow-red-100">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-2">Submit a Ticket</h3>
                        <p class="text-slate-500 font-medium leading-relaxed">Have an issue or question? Create a support ticket and our team will get back to you.</p>
                        <span class="inline-flex items-center mt-6 text-[#780116] font-bold group-hover:underline">Create Ticket <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></span>
                    </Link>

                    <Link :href="route('tickets.index')" class="bg-white p-10 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 hover:border-purple-500 transition-colors group block">
                        <div class="size-16 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-600 group-hover:text-white transition-colors shadow-lg shadow-purple-100">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-2">My Tickets</h3>
                        <p class="text-slate-500 font-medium leading-relaxed">View and track the status of your existing support tickets and replies.</p>
                        <span class="inline-flex items-center mt-6 text-purple-600 font-bold group-hover:underline">View Tickets <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></span>
                    </Link>

                    <Link :href="route('faq')" class="bg-white p-10 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-50 hover:border-blue-500 transition-colors group block">
                        <div class="size-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-lg shadow-blue-100">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-2">Common FAQs</h3>
                        <p class="text-slate-500 font-medium leading-relaxed">Find quick answers to the most frequently asked questions about Sendora.</p>
                        <span class="inline-flex items-center mt-6 text-blue-600 font-bold group-hover:underline">View FAQs <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg></span>
                    </Link>
                </div>

                <div class="bg-slate-900 rounded-[2rem] sm:rounded-[3rem] p-10 md:p-20 text-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#780116] rounded-full blur-[100px] opacity-20 transform translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>

                    <h2 class="text-2xl sm:text-3xl font-black text-white mb-6 relative z-10">Can't find what you're looking for?</h2>
                    <p class="text-slate-400 font-medium mb-10 max-w-2xl mx-auto relative z-10">Our team is always ready to assist you with any technical or billing questions you might have. We typically respond within 24 hours.</p>
                    <Link :href="route('tickets.create')" class="inline-flex px-10 py-5 bg-[#780116] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#c32f27] transition shadow-2xl shadow-red-500/20 relative z-10">
                        Contact Support Team
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
