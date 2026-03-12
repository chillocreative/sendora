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
    <Head title="Sendora - WhatsApp Campaign Blaster & AI Auto-Reply Platform">
        <meta name="description" content="Sendora lets you blast WhatsApp campaigns to thousands of contacts and auto-reply with AI trained on your business. Manage contacts, track delivery, and run drip sequences — all from one dashboard." />
        <meta name="keywords" content="WhatsApp blast, WhatsApp campaign, WhatsApp auto reply, WhatsApp AI assistant, bulk WhatsApp sender, drip campaign WhatsApp, contact book WhatsApp, WhatsApp marketing" />
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
                    Blast Campaigns.<br class="hidden sm:block"/>
                    <span class="text-[#780116]">Let AI Handle the Replies.</span>
                </h1>

                <p class="text-base sm:text-xl text-slate-500 mb-10 sm:mb-12 max-w-3xl mx-auto leading-relaxed">
                    Sendora is your WhatsApp marketing engine. Send bulk campaigns with images, videos, and documents to organised contact lists — then let AI trained on your business SOPs handle every reply, 24/7.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-black flex items-center justify-center shadow-2xl shadow-red-900/40 hover:bg-[#c32f27] transition">
                        Get Started
                    </Link>
                    <a href="https://wa.me/60148885659?text=Saya%20nak%20tahu%20pasal%20sendora" target="_blank" rel="noopener" class="w-full sm:w-auto bg-slate-900 text-white px-8 sm:px-12 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Request a Demo
                    </a>
                </div>

                <!-- Feature Badges -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4 sm:gap-6">
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Bulk campaigns with media
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        AI replies 24/7
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Pause AI anytime
                    </div>
                    <div class="flex items-center gap-2 text-slate-400 text-xs font-bold">
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Delivery & click tracking
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Two Pillars Section -->
        <section class="py-16 sm:py-20 bg-slate-50 border-y border-slate-100">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Everything you need to dominate WhatsApp</p>
                </div>
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Pillar 1: Campaigns -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-8 sm:p-10 shadow-sm hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#780116] rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-red-200">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 mb-3">WhatsApp Campaign Blaster</h3>
                        <p class="text-slate-500 leading-relaxed mb-6">Send personalised messages to thousands of contacts at once. Organise them into Contact Books, attach images, videos, documents, and schedule or drip-send over time.</p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Bulk send to Contact Books or individual contacts
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Images, videos, audio & document attachments
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Schedule or drip-send with variable delays
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Delivery, open & link click tracking
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                                <div class="w-5 h-5 bg-red-50 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Import contacts via CSV into named Contact Books
                            </li>
                        </ul>
                    </div>

                    <!-- Pillar 2: AI Auto-Reply -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-sm hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-3">AI Auto-Reply Engine</h3>
                        <p class="text-slate-400 leading-relaxed mb-6">Write a Playbook — your business SOPs, product details, pricing, tone — and the AI replies to every incoming WhatsApp message like a trained human agent, around the clock.</p>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Markdown playbook per WhatsApp number
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                AI only replies after customer messages you first
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Pause or resume AI per conversation anytime
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Full conversation log & AI reply audit trail
                            </li>
                            <li class="flex items-center gap-3 text-sm text-slate-300 font-medium">
                                <div class="w-5 h-5 bg-emerald-500/20 rounded-full flex items-center justify-center shrink-0"><svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                Handles all scenarios — prices, objections, FAQs
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
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-6 leading-tight tracking-tight">Manual WhatsApp<br/>Is Killing Your Productivity</h2>
                        <p class="text-slate-500 text-base sm:text-lg leading-relaxed mb-8">
                            You're copy-pasting messages one by one, sending the same answers all day, and missing leads that messaged at 2am. Your team is a human broadcast machine and it doesn't scale.
                        </p>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">Sending campaigns one contact at a time</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">Slow reply times lose leads to competitors</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">After-hours messages go unanswered until morning</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                <span class="text-slate-600 font-medium">No idea which messages were delivered or read</span>
                            </li>
                        </ul>
                    </div>
                    <div class="space-y-4">
                        <div class="p-6 sm:p-8 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                            <div class="text-4xl font-black text-slate-900 mb-1">78%</div>
                            <div class="text-sm text-slate-500 font-medium">of customers buy from the business that responds first</div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                                <div class="text-3xl font-black text-red-600 mb-1">5 min</div>
                                <div class="text-xs text-slate-500 font-medium">Optimal response window before a lead goes cold</div>
                            </div>
                            <div class="p-6 bg-slate-50 border border-slate-200 rounded-3xl shadow-sm">
                                <div class="text-3xl font-black text-slate-900 mb-1">24/7</div>
                                <div class="text-xs text-slate-500 font-medium">When your customers expect a reply</div>
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
                    <p class="text-[10px] sm:text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">From setup to first campaign or AI reply in under 15 minutes</p>
                </div>

                <div class="relative">
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -translate-y-1/2 z-0"></div>

                    <div class="grid md:grid-cols-4 gap-8 relative z-10">
                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-slate-900 text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-slate-900/20 group-hover:scale-110 transition-transform">1</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Connect WhatsApp</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Scan a QR code to link your WhatsApp number. Multiple numbers supported per account.</p>
                        </div>

                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-[#db7c26] text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-orange-600/20 group-hover:scale-110 transition-transform">2</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Import Contacts</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Import your leads via CSV into Contact Books. Group them by campaign, product, or region.</p>
                        </div>

                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-[#780116] text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-red-900/20 group-hover:scale-110 transition-transform">3</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">Blast Campaigns</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Create a campaign with text, images, or video. Select Contact Books and send — or schedule for later.</p>
                        </div>

                        <div class="bg-white/90 backdrop-blur-md p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-16 h-16 bg-green-600 text-white rounded-2xl flex items-center justify-center text-2xl font-black mx-auto mb-6 shadow-2xl shadow-green-600/20 group-hover:scale-110 transition-transform">4</div>
                            <h3 class="text-lg font-black mb-3 text-slate-900 tracking-tight leading-tight">AI Handles Replies</h3>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-relaxed">Assign an AI Playbook to your number. Every incoming reply is handled automatically, 24/7.</p>
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
                        No patchwork of tools. Campaigns, contacts, AI replies, and analytics — all built together.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-[#780116]/40 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Contact Books</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Organise contacts into named lists — "Leads Feb", "Clients KL", "VIP Buyers". Import via CSV directly into any book. Select books when blasting campaigns.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Drip Campaigns</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Set a delay between each message in a campaign. Space out your blast over hours or days to mimic natural pacing and avoid detection as bulk sender.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Delivery & Click Analytics</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Track every message: sent, delivered, read, and clicked. Links in your campaigns are auto-wrapped for click tracking. Export reports from your dashboard.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">AI Playbooks</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Write your business knowledge in plain markdown. Products, pricing, FAQs, tone of voice. The AI reads your playbook and answers every customer question accordingly.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">Live Conversation Monitor</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">See all active AI conversations in real-time. Review the full message history, pause or resume AI per chat, and close conversations when done.</p>
                    </div>

                    <div class="p-6 sm:p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                        <div class="w-12 h-12 bg-[#f7b538]/20 rounded-2xl flex items-center justify-center mb-5">
                            <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>
                        </div>
                        <h3 class="text-lg font-black mb-2">WhatsApp Warmer</h3>
                        <p class="text-slate-400 text-sm leading-relaxed">Keep your WhatsApp numbers healthy. The Warmer mode applies extended human-stagger delays between campaign messages to maintain your number's trust score.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Who It's For -->
        <section class="py-16 sm:py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-4 tracking-tight">Built for WhatsApp-Driven Businesses</h2>
                    <p class="text-base sm:text-lg text-slate-500 max-w-2xl mx-auto">If your sales, marketing, or support runs through WhatsApp, Sendora was built for you.</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#780116] rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">Digital Agencies</h3>
                        <p class="text-slate-500 leading-relaxed">Manage campaigns and AI playbooks across multiple client WhatsApp numbers from a single account. Each client gets their own contacts, campaigns, and conversation history.</p>
                    </div>

                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-[#db7c26] rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">Lead-Driven Businesses</h3>
                        <p class="text-slate-500 leading-relaxed">Real estate, education, insurance, automotive. Blast your lead list with a campaign, then let AI qualify and respond to every interested inquiry instantly.</p>
                    </div>

                    <div class="p-8 bg-slate-50 border border-slate-100 rounded-3xl hover:shadow-xl transition-shadow duration-300">
                        <div class="w-14 h-14 bg-slate-900 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-3">E-Commerce & Retail</h3>
                        <p class="text-slate-500 leading-relaxed">Send product launches, promotions, and restock alerts to customer groups. Track delivery and clicks. AI handles "where's my order?" replies automatically.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Final CTA -->
        <section class="py-16 sm:py-24 bg-white px-4">
            <div class="max-w-5xl mx-auto rounded-[2rem] sm:rounded-[3rem] bg-slate-900 p-8 sm:p-12 md:p-20 text-center relative overflow-hidden">
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-white mb-4 sm:mb-6">Start Blasting Smarter Today</h2>
                <p class="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto mb-8 sm:mb-10">
                    Set up your first campaign or AI Playbook in under 15 minutes.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-[#c32f27] transition shadow-2xl shadow-red-900/40">
                        Get Started
                    </Link>
                    <a href="https://wa.me/60148885659?text=Saya%20nak%20tahu%20pasal%20sendora" target="_blank" rel="noopener" class="w-full sm:w-auto bg-white/10 text-white border border-white/20 px-8 sm:px-10 py-4 sm:py-5 rounded-2xl text-base sm:text-xl font-bold hover:bg-white/20 transition flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        WhatsApp Us for a Demo
                    </a>
                </div>
                <p class="mt-6 sm:mt-8 text-slate-400 font-medium text-sm sm:text-base tracking-wide flex items-center justify-center flex-wrap gap-4">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#f7b538]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Cancel anytime
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#f7b538]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                        Setup in 15 minutes
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
