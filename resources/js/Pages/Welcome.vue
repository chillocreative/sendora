<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const showMobileMenu = ref(false);
</script>

<template>
    <Head title="Sendora - Your Personal WhatsApp Reminder & Google Calendar Assistant">
        <meta name="description" content="Sendora turns your WhatsApp into a personal reminder system synced with Google Calendar. Set reminders, manage your schedule, and run AI Playbooks — all through simple /sendora commands." />
        <meta name="keywords" content="WhatsApp reminder, Google Calendar sync, WhatsApp calendar, personal assistant WhatsApp, AI scheduling, WhatsApp commands, reminder bot, calendar assistant" />
    </Head>

    <div class="min-h-screen bg-white text-slate-900 font-sans selection:bg-red-100 selection:text-[#780116] overflow-x-hidden relative">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <button @click="showMobileMenu = true" class="lg:hidden mr-4 text-slate-500 hover:text-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="flex-shrink-0 flex items-center group cursor-pointer">
                            <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                        </div>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden lg:flex items-center space-x-6">
                        <Link :href="'/'" class="text-sm font-semibold text-[#780116] transition">Home</Link>
                        <Link :href="route('pricing')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Pricing</Link>
                        <Link :href="route('faq')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">FAQ</Link>
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Dashboard</Link>
                            <template v-else>
                                <Link :href="route('login')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Log in</Link>
                                <Link v-if="canRegister" :href="route('register')" class="bg-[#780116] text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-[#c32f27] transition shadow-md">Get Started</Link>
                            </template>
                        </template>
                    </div>

                    <!-- Mobile CTA Button -->
                    <div class="flex lg:hidden items-center">
                        <Link v-if="canLogin && !$page.props.auth.user && canRegister" :href="route('register')" class="bg-[#780116] text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-[#c32f27] transition shadow-md">Get Started</Link>
                        <Link v-else-if="canLogin && $page.props.auth.user" :href="route('dashboard')" class="bg-[#780116] text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-[#c32f27] transition shadow-md">Dashboard</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-slate-900/90 backdrop-blur-sm lg:hidden" @click="showMobileMenu = false"></div>

        <!-- Mobile Menu Sidebar -->
        <div :class="showMobileMenu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl transition-transform duration-500 lg:hidden flex flex-col">
            <div class="h-20 px-4 flex items-center justify-between border-b border-slate-100">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                </div>
                <button @click="showMobileMenu = false" class="size-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 transition-all active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                <Link :href="'/'" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-[#780116] bg-red-50" @click="showMobileMenu = false">Home</Link>
                <Link :href="route('pricing')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Pricing</Link>
                <Link :href="route('faq')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">FAQ</Link>
                <template v-if="canLogin">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl bg-[#780116] text-white hover:bg-[#c32f27]" @click="showMobileMenu = false">Get Started</Link>
                    </template>
                </template>
            </nav>
        </div>

        <!-- 1. Hero Section -->
        <section class="pt-32 sm:pt-40 pb-16 sm:pb-20 px-4">
            <div class="max-w-7xl mx-auto text-center">
                <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-slate-900 mb-6 sm:mb-8 tracking-tight max-w-5xl mx-auto leading-[1.1]">
                    Your Personal WhatsApp<br class="hidden sm:block"/>
                    <span class="text-[#780116]">Reminder & Calendar Assistant.</span>
                </h1>

                <p class="text-base sm:text-xl text-slate-500 mb-10 sm:mb-12 max-w-3xl mx-auto leading-relaxed">
                    Sendora syncs your Google Calendar with WhatsApp so you never miss a meeting, deadline, or task again. Set reminders with simple commands, let AI manage your schedule, and get timely nudges right where you already chat.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-black flex items-center justify-center shadow-2xl shadow-red-900/40 hover:bg-[#c32f27] transition">
                        Get Started
                    </Link>
                    <Link :href="route('faq')" class="w-full sm:w-auto bg-slate-900 text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Learn More
                    </Link>
                </div>

                <!-- Feature Badges -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4 sm:gap-6">
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        WhatsApp reminders
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Google Calendar sync
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        /sendora commands
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        AI-powered scheduling
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Two Pillars Section -->
        <section class="py-16 sm:py-20 bg-slate-50 border-y border-slate-100">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Two powerful systems working together for you</p>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Pillar 1: WhatsApp Reminders -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 sm:p-10 shadow-sm hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#780116] rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-red-200">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 mb-3">WhatsApp Reminders</h3>
                        <p class="text-slate-500 leading-relaxed mb-6">Get timely reminders delivered straight to your WhatsApp. Never forget a meeting, deadline, or task again. Set them from your phone or dashboard -- they arrive right in your chat.</p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Reminders sent directly to your WhatsApp
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Set one-time or recurring reminders
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Use /sendora commands right from your chat
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Smart natural language scheduling
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Morning briefing of your day ahead
                            </li>
                        </ul>
                    </div>

                    <!-- Pillar 2: Google Calendar Sync -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-sm hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-3">Google Calendar Sync</h3>
                        <p class="text-slate-400 leading-relaxed mb-6">Link your Google Calendar and Sendora keeps everything in sync. New events become WhatsApp reminders automatically. Add events from WhatsApp and they appear in your calendar.</p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Two-way sync with Google Calendar
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Auto-create reminders from calendar events
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Add events via WhatsApp, see them in Calendar
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Customisable reminder timing (5 min, 1 hr, 1 day before)
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Works with personal and work calendars
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Problem Section -->
        <section class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid md:grid-cols-2 gap-10 sm:gap-16 items-center">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-6 leading-tight tracking-tight">Forgetting Things<br/>Is Costing You</h2>
                        <p class="text-slate-500 text-base sm:text-lg leading-relaxed mb-8">
                            You set calendar events but ignore the notifications. You write to-do lists that go stale. Important tasks slip through the cracks because your reminders live in apps you don't check. WhatsApp is the one app you never ignore.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">Calendar notifications that get swiped away</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">Missed deadlines and forgotten follow-ups</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">No easy way to add events from your phone quickly</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">Too many apps for tasks, reminders, and calendars</span>
                            </li>
                        </ul>
                    </div>
                    <div class="space-y-4">
                        <div class="p-6 sm:p-8 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                            <div class="text-4xl font-black text-slate-900 mb-1">91%</div>
                            <div class="text-sm text-slate-500 font-medium">of WhatsApp messages are read within 3 minutes</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                                <div class="text-3xl font-black text-red-600 mb-1">23%</div>
                                <div class="text-xs text-slate-500 font-medium">of calendar alerts are dismissed without reading</div>
                            </div>
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                                <div class="text-3xl font-black text-slate-900 mb-1">0</div>
                                <div class="text-xs text-slate-500 font-medium">Missed reminders when they come via WhatsApp</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. How It Works -->
        <section class="py-20 sm:py-32 bg-slate-50 border-y border-slate-200 relative overflow-hidden">
            <div class="absolute inset-0 opacity-40 bg-[url('/images/steps_bg.png')] bg-cover bg-center"></div>
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="text-center mb-16 sm:mb-24">
                    <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 mb-4 sm:mb-6 tracking-tight">How Sendora Works</h2>
                    <p class="text-[10px] sm:text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">From setup to your first reminder in under 5 minutes</p>
                </div>

                <div class="relative">
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -translate-y-1/2 z-0"></div>

                    <div class="grid md:grid-cols-3 gap-8 relative z-10">
                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-slate-900 text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-slate-900/20 group-hover:scale-110 transition-transform">1</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Connect WhatsApp</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Scan a QR code to link your WhatsApp number. Sendora becomes your personal assistant, ready to receive commands.</p>
                        </div>

                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-[#780116] text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-red-900/20 group-hover:scale-110 transition-transform">2</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Link Google Calendar</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Connect your Google Calendar with one click. Existing events automatically become WhatsApp reminders. Everything stays in sync.</p>
                        </div>

                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-green-600 text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-green-600/20 group-hover:scale-110 transition-transform">3</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Get Reminders & Use /sendora</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Receive timely reminders on WhatsApp. Use /sendora commands to add events, check your schedule, or ask AI to organise your day.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Features Grid -->
        <section class="py-16 sm:py-24 bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-3xl sm:text-4xl md:text-5xl font-black mb-4 sm:mb-6 tracking-tight">Everything in One Platform</h2>
                    <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto leading-relaxed">
                        Reminders, calendar, AI scheduling, and smart commands -- all built together, delivered through WhatsApp.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-[#780116]/40 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Smart Reminders</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Set reminders using natural language. "Remind me to call John tomorrow at 3pm" just works. One-time or recurring -- daily, weekly, monthly. Delivered to your WhatsApp on the dot.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Google Calendar Sync</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Two-way sync keeps your Google Calendar and Sendora in lockstep. Events created anywhere show up everywhere. Reminders fire based on your calendar automatically.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">/sendora Commands</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Type commands right in WhatsApp. /sendora add, /sendora list, /sendora today -- manage your entire schedule without leaving your chat. Quick, intuitive, no app switching.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">AI-Powered Scheduling</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Just tell Sendora what you need in plain language. The AI understands context, finds the right time, and schedules it. "Set up a weekly standup every Monday at 9am" -- done.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">AI Playbooks</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Define your routines, habits, and workflows in a Playbook. The AI follows your rules to suggest schedules, send check-ins, and keep you accountable throughout the day.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-[#f7b538]/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Morning Briefing</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Start every day with a WhatsApp summary of your schedule. See meetings, tasks, and reminders at a glance. Customise the briefing time and format to suit your routine.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Who It's For -->
        <section class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4 tracking-tight">Built for Busy People</h2>
                    <p class="text-base sm:text-lg text-slate-500 max-w-2xl mx-auto">If you live on WhatsApp and need to stay on top of your schedule, Sendora was built for you.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#780116] rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">Professionals & Freelancers</h3>
                        <p class="text-slate-500 leading-relaxed">Juggling client calls, deadlines, and meetings. Get reminders for every commitment right in WhatsApp. Use /sendora commands between meetings to stay organised without context-switching.</p>
                    </div>

                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#db7c26] rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">Students & Academics</h3>
                        <p class="text-slate-500 leading-relaxed">Track assignment deadlines, exam dates, and study sessions. Set recurring reminders for revision blocks. Sendora helps you build consistent habits without another app to check.</p>
                    </div>

                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">Teams & Small Businesses</h3>
                        <p class="text-slate-500 leading-relaxed">Keep your team aligned with shared calendar reminders. Never miss a standup, client call, or project milestone. Everyone gets the nudge they need, right in WhatsApp.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Final CTA -->
        <section class="py-16 sm:py-24 bg-white px-4">
            <div class="max-w-5xl mx-auto rounded-[2rem] sm:rounded-[3rem] bg-slate-900 p-8 sm:p-12 md:p-20 text-center relative overflow-hidden">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-4 sm:mb-6">Never Miss a Thing Again</h2>
                <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto mb-8 sm:mb-10">
                    Connect your WhatsApp and Google Calendar in under 5 minutes. Your first reminder is just a /sendora command away.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-[#c32f27] transition shadow-2xl shadow-red-900/40">
                        Get Started
                    </Link>
                    <Link :href="route('faq')" class="w-full sm:w-auto bg-white/10 text-white border border-white/20 px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-white/20 transition flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Frequently Asked Questions
                    </Link>
                </div>
                <p class="mt-6 sm:mt-8 text-slate-400 font-medium text-sm sm:text-base tracking-wide flex items-center justify-center flex-wrap gap-4">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#f7b538]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Free to start
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#f7b538]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Setup in 5 minutes
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#f7b538]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Cancel anytime
                    </span>
                </p>
            </div>
        </section>

        <!-- Footer -->
        <Footer />
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.8s ease-out forwards;
}
</style>
