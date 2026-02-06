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
    <Head title="Sendora - WhatsApp Bulk Messaging & Marketing Automation Platform">
        <meta name="description" content="Professional WhatsApp marketing automation platform. Send bulk messages, automate campaigns, manage contacts, schedule messages, and track performance. Perfect for Malaysian SMEs." />
        <meta name="keywords" content="WhatsApp bulk messaging, WhatsApp marketing, WhatsApp automation, WhatsApp CRM, bulk WhatsApp sender, WhatsApp campaign, WhatsApp business API, Malaysia WhatsApp marketing" />
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
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
                                <Link v-if="canRegister" :href="route('register')" class="bg-[#780116] text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-[#c32f27] transition shadow-md">Start Free</Link>
                            </template>
                        </template>
                    </div>

                    <!-- Mobile CTA Button -->
                    <div class="flex lg:hidden items-center">
                        <Link v-if="canLogin && !$page.props.auth.user && canRegister" :href="route('register')" class="bg-[#780116] text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-[#c32f27] transition shadow-md">Start Free</Link>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                </div>
                <button @click="showMobileMenu = false" class="size-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 transition-all active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                <Link :href="'/'" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-[#780116] bg-red-50" @click="showMobileMenu = false">
                    Home
                </Link>
                <Link :href="route('pricing')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                    Pricing
                </Link>
                <Link :href="route('faq')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                    FAQ
                </Link>
                <template v-if="canLogin">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl bg-[#780116] text-white hover:bg-[#c32f27]" @click="showMobileMenu = false">
                            Start Free
                        </Link>
                    </template>
                </template>
            </nav>
        </div>

        <!-- Hero Section -->
        <section class="pt-32 sm:pt-40 pb-16 sm:pb-20 px-4">
            <div class="max-w-7xl mx-auto text-center">

                <div class="relative inline-block">
                    <h1 class="relative text-4xl sm:text-5xl md:text-7xl font-black text-slate-900 mb-6 sm:mb-8 tracking-tight max-w-4xl mx-auto leading-[1.1]">
                        WhatsApp Marketing <br class="hidden sm:block"/>
                        <span class="text-[#780116]">Made Simple & Powerful</span>
                    </h1>
                </div>

                <p class="text-base sm:text-xl text-slate-500 mb-10 sm:mb-12 max-w-3xl mx-auto leading-relaxed relative">
                    <span class="absolute -left-8 top-0 text-red-200 opacity-20 hidden md:block">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                    </span>
                    Send bulk WhatsApp messages, automate campaigns, manage unlimited contacts, and track performance in real-time. The complete WhatsApp marketing automation platform for businesses in Malaysia and beyond.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-black flex items-center justify-center shadow-2xl shadow-red-900/40 hover:bg-[#c32f27] transition">
                        Connect WhatsApp — It's Free
                    </Link>
                    <Link :href="route('pricing')" class="w-full sm:w-auto bg-slate-900 text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold flex items-center justify-center">
                        View Pricing
                    </Link>
                </div>

                <!-- App Preview Mockup -->
                <div class="mt-20 relative max-w-4xl mx-auto px-4">
                    <div class="relative rounded-[2.5rem] p-1.5 bg-slate-100 shadow-2xl shadow-slate-200/50">
                        <div class="relative bg-white rounded-[2rem] overflow-hidden border border-slate-100 shadow-inner aspect-video">
                            <img src="/images/sendora_hero.png" alt="Sendora Dashboard Preview" class="w-full h-full object-cover object-center" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Trust & Value -->
        <section class="py-16 sm:py-24 bg-slate-900 text-white overflow-hidden relative">
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid md:grid-cols-2 gap-10 sm:gap-16 items-center">
                    <div>
                        <h2 class="text-3xl sm:text-4xl md:text-5xl font-black mb-6 sm:mb-8 leading-tight">Complete WhatsApp Marketing Solution <br class="hidden sm:block"/><span class="text-[#f7b538]">for Modern Businesses</span></h2>
                        <p class="text-slate-400 text-base sm:text-xl mb-8 sm:mb-10 leading-relaxed">
                            Reach customers directly on WhatsApp with bulk messaging, automated replies, and advanced contact management. Send promotional campaigns, customer updates, and personalized messages at scale while maintaining high engagement rates.
                        </p>
                        <ul class="space-y-4 text-slate-300">
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Multi-account WhatsApp management (up to 5 accounts)
                            </li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Smart contact segmentation with unlimited tags
                            </li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Advanced scheduling & automated campaigns
                            </li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Real-time analytics & performance tracking
                            </li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                            <div class="text-3xl font-bold text-[#f7b538] mb-2">98%</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Open Rate</div>
                        </div>
                        <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm sm:translate-y-8">
                            <div class="text-3xl font-bold text-[#db7c26] mb-2">45%</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">CTR</div>
                        </div>
                        <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                            <div class="text-3xl font-bold text-[#d8572a] mb-2">3.5x</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Conversion</div>
                        </div>
                        <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm sm:translate-y-8">
                            <div class="text-3xl font-bold text-[#c32f27] mb-2">24h</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-20">
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-3 sm:mb-4">Powerful WhatsApp Marketing Features</h2>
                    <p class="text-base sm:text-lg text-slate-500">Everything you need to grow your business on WhatsApp</p>
                </div>

                <div class="grid md:grid-cols-3 gap-12">
                    <div class="group">
                        <div class="w-16 h-16 bg-red-50 rounded-2xl mb-6 flex items-center justify-center text-[#780116] group-hover:bg-[#780116] group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Bulk WhatsApp Campaigns</h3>
                        <p class="text-slate-500 leading-relaxed">Send thousands of personalized messages with images, PDFs, and links. Track delivery, opens, and clicks in real-time with detailed analytics.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl mb-6 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Smart Contact Management</h3>
                        <p class="text-slate-500 leading-relaxed">Import unlimited contacts, organize with custom tags, segment audiences by behavior, and export data anytime. Built-in CRM for WhatsApp.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-orange-50 rounded-2xl mb-6 flex items-center justify-center text-[#db7c26] group-hover:bg-[#db7c26] group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Campaign Scheduling</h3>
                        <p class="text-slate-500 leading-relaxed">Schedule messages for optimal delivery times. Set up recurring campaigns, automate follow-ups, and run promotions on autopilot.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-green-50 rounded-2xl mb-6 flex items-center justify-center text-green-600 group-hover:bg-green-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Auto-Reply System</h3>
                        <p class="text-slate-500 leading-relaxed">Set up keyword-based automatic responses. Answer common questions 24/7, qualify leads instantly, and never miss a customer inquiry.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-purple-50 rounded-2xl mb-6 flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Advanced Analytics</h3>
                        <p class="text-slate-500 leading-relaxed">Track delivery rates, open rates, click rates, and engagement metrics. Export reports and optimize campaigns based on real data.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-red-50 rounded-2xl mb-6 flex items-center justify-center text-[#c32f27] group-hover:bg-[#c32f27] group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">WhatsApp Warmer</h3>
                        <p class="text-slate-500 leading-relaxed">AI-powered account warming to build sender reputation. Prevent blocks and bans with smart sending patterns and gradual volume increase.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-indigo-50 rounded-2xl mb-6 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Developer API & Webhooks</h3>
                        <p class="text-slate-500 leading-relaxed">Integrate with your existing systems via REST API. Receive real-time webhooks for message events and build custom workflows.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-yellow-50 rounded-2xl mb-6 flex items-center justify-center text-yellow-600 group-hover:bg-yellow-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Rich Media Support</h3>
                        <p class="text-slate-500 leading-relaxed">Send images, PDFs, documents, and files up to 5MB. Include link previews, file attachments, and message templates for better engagement.</p>
                    </div>

                    <div class="group">
                        <div class="w-16 h-16 bg-teal-50 rounded-2xl mb-6 flex items-center justify-center text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Multi-Account Support</h3>
                        <p class="text-slate-500 leading-relaxed">Manage up to 5 WhatsApp accounts from one dashboard. Perfect for agencies, multiple brands, or different business units.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="py-20 sm:py-32 bg-slate-50 border-y border-slate-200 relative overflow-hidden">
            <div class="absolute inset-0 opacity-40 bg-[url('/images/steps_bg.png')] bg-cover bg-center"></div>
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="text-center mb-16 sm:mb-24">
                    <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 mb-4 sm:mb-6 tracking-tight">Get Started in 3 Simple Steps</h2>
                    <p class="text-[10px] sm:text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">Launch your first WhatsApp campaign in minutes</p>
                </div>

                <div class="relative">
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -translate-y-1/2 z-0"></div>

                    <div class="grid md:grid-cols-3 gap-12 relative z-10">
                        <!-- Step 1 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-slate-900 text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-slate-900/20 group-hover:scale-110 transition-transform">1</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Connect Your WhatsApp</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Scan QR code to link your WhatsApp account. Takes less than 30 seconds. Support for multiple accounts.</p>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-[#db7c26] text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-orange-600/20 group-hover:scale-110 transition-transform">2</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Import & Organize Contacts</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Upload contacts via CSV or add manually. Tag and segment for targeted campaigns.</p>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-[#780116] text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-red-900/20 group-hover:scale-110 transition-transform">3</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Launch & Track Campaigns</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Send bulk messages with media. Schedule for later. Track opens, clicks, and engagement in real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="py-16 sm:py-24 bg-white px-4">
            <div class="max-w-5xl mx-auto rounded-[2rem] sm:rounded-[3rem] bg-slate-900 p-8 sm:p-12 md:p-20 text-center relative overflow-hidden">

                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-6 sm:mb-8">Start Your WhatsApp Marketing Journey Today</h2>
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-[#c32f27] transition shadow-2xl shadow-red-900/40">
                        Start for Free Now
                    </Link>
                    <Link :href="route('pricing')" class="w-full sm:w-auto bg-white/10 text-white border border-white/20 px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-white/20 transition">
                        Compare Plans
                    </Link>
                </div>
                <p class="mt-6 sm:mt-8 text-slate-400 font-medium text-sm sm:text-base tracking-wide flex items-center justify-center flex-wrap">
                    <svg class="w-5 h-5 text-[#f7b538] mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    No credit card required. Cancel anytime.
                </p>
            </div>
        </section>

        <!-- Stunning Footer -->
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
