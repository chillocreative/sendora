<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
    plans: Array,
    currency: String,
    currentPlanId: Number,
});

const billingCycle = ref('monthly');
const showMobileMenu = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({
    plan_id: null,
    billing_cycle: 'monthly',
});

const featureNames = {
    'text_support': 'Text Messages',
    'image_support': 'Image Support',
    'file_support': 'File Attachments',
    'scheduling': 'Message Scheduling',
    'pdf_support': 'PDF Support',
    'link_preview': 'Link Previews',
    'auto_reply': 'AI Customer Support',
    'message_preview': 'Message Previews',
    'multi_user': 'Multi-User Support',
    'webhooks': 'Webhook Integration',
    'api_access': 'API Access',
};

const getActiveFeatures = (plan) => {
    const features = plan.limits.features || {};
    return Object.keys(features)
        .filter(key => features[key])
        .map(key => featureNames[key] || key);
};

const isDowngrade = (plan) => {
    if (!user.value || !props.currentPlanId) return false;
    const currentPlan = props.plans.find(p => p.id === props.currentPlanId);
    if (!currentPlan) return false;
    return Number(plan.monthly_price) < Number(currentPlan.monthly_price);
};

const selectPlan = (plan, forceCycle = null) => {
    if (props.currentPlanId === plan.id) return;
    if (isDowngrade(plan)) return;

    const cycle = plan.is_lifetime ? 'lifetime' : (forceCycle ?? billingCycle.value);

    if (user.value) {
        form.plan_id = plan.id;
        form.billing_cycle = cycle;
        form.post(route('payments.initiate'));
    } else {
        window.location.href = route('register', {
            plan_id: plan.id,
            billing_cycle: cycle,
        });
    }
};
</script>

<template>
    <Head title="Sendora - Flexible Plans for Every Business" />

    <div class="min-h-screen bg-slate-50 flex flex-col font-sans selection:bg-red-100 selection:text-red-900">
        <!-- Navigation (Same as Welcome) -->
        <nav class="fixed w-full z-50 bg-white/95 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <button @click="showMobileMenu = true" class="lg:hidden mr-4 text-slate-500 hover:text-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <Link :href="'/'" class="flex-shrink-0 flex items-center group cursor-pointer">
                            <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                        </Link>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden lg:flex items-center space-x-6">
                        <Link :href="'/'" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Home</Link>
                        <Link :href="route('pricing')" class="text-sm font-semibold text-[#780116] transition">Pricing</Link>
                        <Link :href="route('faq')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">FAQ</Link>
                        <template v-if="user">
                            <Link :href="route('dashboard')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Log in</Link>
                            <Link :href="route('register')" class="bg-slate-900 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-slate-800 transition shadow-md">Start Free</Link>
                        </template>
                    </div>

                    <!-- Mobile CTA Button -->
                    <div class="flex lg:hidden items-center">
                        <Link v-if="!user" :href="route('register')" class="bg-slate-900 text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-slate-800 transition shadow-md">Start Free</Link>
                        <Link v-else :href="route('dashboard')" class="bg-slate-900 text-white px-4 py-2 rounded-full text-xs font-bold hover:bg-slate-800 transition shadow-md">Dashboard</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-slate-900/90 backdrop-blur-sm lg:hidden" @click="showMobileMenu = false"></div>

        <!-- Mobile Menu Sidebar -->
        <div :class="showMobileMenu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl transition-transform duration-500 lg:hidden flex flex-col">
            <div class="h-20 px-4 flex items-center justify-between border-b border-slate-100">
                <Link :href="'/'" class="flex items-center">
                    <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                </Link>
                <button @click="showMobileMenu = false" class="size-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 transition-all active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                <Link :href="'/'" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                    Home
                </Link>
                <Link :href="route('pricing')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-[#780116] bg-red-50" @click="showMobileMenu = false">
                    Pricing
                </Link>
                <Link :href="route('faq')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                    FAQ
                </Link>
                <template v-if="user">
                    <Link :href="route('dashboard')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                        Log in
                    </Link>
                    <Link :href="route('register')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800" @click="showMobileMenu = false">
                        Start Free
                    </Link>
                </template>
            </nav>
        </div>

        <main class="flex-grow pt-32 pb-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Copywriting Section -->
                <div class="text-center mb-24 px-4">
                    <h1 class="text-4xl sm:text-5xl md:text-7xl font-black text-slate-900 mb-6 sm:mb-8 tracking-tight max-w-4xl mx-auto leading-[1.1]">
                        Scale Your Customer <br class="hidden sm:block"/>
                        <span class="text-[#780116]">Engagement Effortlessly.</span>
                    </h1>
                    <p class="text-base sm:text-xl text-slate-500 max-w-2xl mx-auto leading-relaxed">
                        Stop overpaying for features you don't use. Choose a plan that grows with your business, whether you're a solopreneur or a high-volume enterprise.
                    </p>

                    <!-- Enhanced Switcher -->
                    <div class="mt-12 flex flex-col items-center px-4">
                        <div class="flex items-center space-x-2 sm:space-x-4 bg-white p-2 sm:p-2.5 rounded-[1.75rem] border border-slate-100 shadow-xl shadow-slate-200/50">
                            <button
                                @click="billingCycle = 'monthly'"
                                :class="billingCycle === 'monthly' ? 'bg-[#780116] text-white shadow-xl shadow-red-200' : 'text-slate-400 hover:text-slate-600'"
                                class="px-5 sm:px-10 py-3 sm:py-5 rounded-[1.25rem] text-[10px] sm:text-[11px] font-black uppercase tracking-wider sm:tracking-widest transition-all duration-300 transform active:scale-95"
                            >
                                <span class="hidden sm:inline">Regular Billing</span>
                                <span class="sm:hidden">Monthly</span>
                            </button>
                            <button
                                @click="billingCycle = 'yearly'"
                                :class="billingCycle === 'yearly' ? 'bg-[#780116] text-white shadow-xl shadow-red-200' : 'text-slate-400 hover:text-slate-600'"
                                class="px-5 sm:px-10 py-3 sm:py-5 rounded-[1.25rem] text-[10px] sm:text-[11px] font-black uppercase tracking-wider sm:tracking-widest transition-all duration-300 flex items-center transform active:scale-95"
                            >
                                <span class="hidden sm:inline">Annual Plan</span>
                                <span class="sm:hidden">Yearly</span>
                                <span class="ml-2 sm:ml-3 px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-lg bg-[#f7b538] text-[9px] text-[#780116] font-black tracking-normal" :class="billingCycle === 'yearly' ? 'animate-pulse' : ''">
                                    - 20% OFF
                                </span>
                            </button>
                        </div>
                        <p class="mt-8 text-[9px] sm:text-[11px] font-black text-slate-300 uppercase tracking-[0.15em] sm:tracking-[0.2em] text-center">Secure Checkout • Instant Deployment • Enterprise Grade</p>
                    </div>
                </div>

                <!-- Pricing Cards -->
                <!-- Main Paid Plans -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-20">
                    <div v-for="plan in plans.filter(p => !['Starter', 'Free'].includes(p.name) && !p.is_lifetime)" :key="plan.id"
                         class="relative group rounded-[3.5rem] border border-slate-100 p-12 flex flex-col transition-all duration-500 hover:shadow-[0_48px_80px_-16px_rgba(120,1,22,0.12)] hover:-translate-y-4"
                         :class="{
                            'ring-2 ring-[#780116] ring-offset-8': plan.name === 'Pro',
                            'bg-white': plan.name === 'Basic',
                            'bg-gradient-to-br from-white via-red-50/20 to-slate-50': plan.name === 'Pro',
                            'bg-gradient-to-br from-[#780116] via-[#c32f27] to-[#db7c26] text-white': plan.name === 'Business'
                         }">
                        
                        <div v-if="plan.name === 'Pro'" class="absolute -top-5 left-1/2 -translate-x-1/2 px-8 py-2.5 bg-[#780116] text-white text-[10px] font-black tracking-[0.25em] rounded-full shadow-2xl shadow-red-300 whitespace-nowrap z-30 transform hover:scale-110 transition-transform">
                            ELITE SELECTION
                        </div>

                        <!-- Content wrapper to ensure text stays readable over gradients -->
                        <div class="relative z-10 flex flex-col h-full pt-2">
                            <div class="mb-12">
                                <h4 class="text-2xl font-black mb-4 tracking-tight" :class="plan.name === 'Business' ? 'text-white' : 'text-slate-900'">{{ plan.name }}</h4>
                                <div class="mb-6">
                                    <div class="flex items-baseline mb-1">
                                        <span class="text-2xl font-bold mr-2 lg:block hidden" :class="plan.name === 'Business' ? 'text-white/40' : 'text-slate-300'">{{ currency }}</span>
                                        <span class="text-6xl font-black tracking-tighter" :class="plan.name === 'Business' ? 'text-white' : 'text-slate-900'">{{ billingCycle === 'monthly' ? Number(plan.monthly_price).toFixed(2) : (plan.yearly_price / 12).toFixed(2) }}</span>
                                    </div>
                                    <div class="font-black text-xs uppercase tracking-widest" :class="plan.name === 'Business' ? 'text-white/40' : 'text-slate-400'">
                                        per month
                                    </div>
                                </div>
                                <div v-if="billingCycle === 'yearly' && plan.yearly_price > 0" class="inline-block px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest" :class="plan.name === 'Business' ? 'bg-white/10 text-white' : 'bg-slate-50 text-slate-400 border border-slate-100'">
                                    TOTAL {{ currency }} {{ Number(plan.yearly_price).toFixed(2) }} / YEAR
                                </div>
                            </div>

                            <ul class="space-y-6 mb-16 flex-grow">
                                <li class="flex items-center text-sm font-bold" :class="plan.name === 'Business' ? 'text-white/80' : 'text-slate-600'">
                                    <span class="w-8 h-8 rounded-[0.75rem] flex items-center justify-center mr-4 shrink-0 transition-colors" :class="plan.name === 'Business' ? 'bg-white/10 text-[#f7b538]' : 'bg-red-50 text-[#780116]'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ plan.limits.whatsapp_nos }} WhatsApp Number</span>
                                </li>
                                <li class="flex items-center text-sm font-bold" :class="plan.name === 'Business' ? 'text-white/80' : 'text-slate-600'">
                                    <span class="w-8 h-8 rounded-[0.75rem] flex items-center justify-center mr-4 shrink-0 transition-colors" :class="plan.name === 'Business' ? 'bg-white/10 text-[#f7b538]' : 'bg-red-50 text-[#780116]'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ plan.limits.contacts }} Contacts</span>
                                </li>
                                <li class="flex items-center text-sm font-bold" :class="plan.name === 'Business' ? 'text-white/80' : 'text-slate-600'">
                                    <span class="w-8 h-8 rounded-[0.75rem] flex items-center justify-center mr-4 shrink-0 transition-colors" :class="plan.name === 'Business' ? 'bg-white/10 text-[#f7b538]' : 'bg-red-50 text-[#780116]'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ plan.limits.messages }} Messages</span>
                                </li>
                                <li v-for="feature in getActiveFeatures(plan)" :key="feature" class="flex items-center text-sm font-bold" :class="plan.name === 'Business' ? 'text-white/80' : 'text-slate-600'">
                                    <span class="w-8 h-8 rounded-[0.75rem] flex items-center justify-center mr-4 shrink-0 transition-colors" :class="plan.name === 'Business' ? 'bg-white/10 text-[#f7b538]' : 'bg-red-50 text-[#780116]'">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ feature }}</span>
                                </li>
                            </ul>

                            <button 
                                @click="selectPlan(plan)"
                                :disabled="currentPlanId === plan.id || isDowngrade(plan)"
                                :class="[
                                    currentPlanId === plan.id ? (plan.name === 'Business' ? 'bg-white/5 text-slate-400 cursor-not-allowed shadow-none' : 'bg-slate-50 text-slate-300 cursor-not-allowed border border-slate-100 shadow-none') :
                                    isDowngrade(plan) ? (plan.name === 'Business' ? 'bg-white/5 text-slate-400 cursor-not-allowed shadow-none' : 'bg-slate-50 text-slate-200 cursor-not-allowed border border-slate-50 shadow-none') :
                                    (plan.name === 'Pro' ? 'bg-[#780116] text-white hover:bg-[#c32f27] shadow-xl shadow-red-300/30' : 
                                     (plan.name === 'Business' ? 'bg-[#f7b538] text-[#780116] hover:bg-white hover:text-[#780116] shadow-xl shadow-orange-900/40' : 'bg-slate-900 text-white hover:bg-slate-800 shadow-xl shadow-slate-200')),
                                    'w-full py-6 rounded-2xl font-black text-xs tracking-[0.2em] uppercase transition-all duration-300 transform active:scale-95'
                                ]"
                            >
                                {{ 
                                    currentPlanId === plan.id ? 'Already Active' : 
                                    isDowngrade(plan) ? 'Tier Restricted' : ('Acquire ' + plan.name) 
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lifetime Plan (Horizontal) -->
                <div v-for="plan in plans.filter(p => p.is_lifetime)" :key="plan.id"
                     class="max-w-7xl mx-auto mb-12 bg-gradient-to-r from-amber-50 via-white to-amber-50 rounded-[3rem] p-8 sm:p-12 border-2 border-amber-200 shadow-xl shadow-amber-100/60 flex flex-col lg:flex-row items-center justify-between gap-10 relative overflow-hidden group">

                    <div class="absolute -right-20 -top-20 w-96 h-96 bg-amber-400/10 rounded-full blur-[80px] group-hover:bg-amber-400/20 transition-colors duration-700"></div>

                    <div class="relative z-10 flex-1 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-400 text-amber-900 text-[10px] font-black uppercase tracking-widest mb-6 shadow-lg shadow-amber-200">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            Limited Offer — One-Time Payment
                        </div>
                        <h4 class="text-4xl font-black text-slate-900 mb-4 tracking-tight">{{ plan.name }} Access</h4>
                        <p class="text-slate-500 font-medium max-w-md leading-relaxed text-lg">
                            Pay once. Use forever. Get Pro-level features with no recurring charges, ever.
                        </p>
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-12 lg:gap-16">
                        <div class="text-center">
                            <div class="flex items-baseline justify-center">
                                <span class="text-xl font-bold text-amber-500 mr-1">{{ currency }}</span>
                                <span class="text-6xl font-black text-slate-900">{{ Number(plan.monthly_price).toFixed(2) }}</span>
                            </div>
                            <p class="mt-2 text-[10px] font-black text-amber-700 uppercase tracking-widest bg-amber-100 px-3 py-1 rounded-full inline-block">One-Time · No Renewals</p>
                        </div>

                        <div class="grid grid-cols-1 gap-y-4">
                            <div class="flex items-center gap-4 text-slate-700 font-bold text-lg">
                                <div class="size-6 rounded-full bg-amber-400 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span>{{ plan.limits.whatsapp_nos }} WhatsApp Numbers</span>
                            </div>
                            <div class="flex flex-col gap-1 ml-10">
                                <div class="text-slate-500 text-sm font-bold flex items-center gap-2">
                                    <div class="w-1 h-4 bg-amber-400 rounded-full"></div>
                                    {{ plan.limits.messages }} Messages / month
                                </div>
                                <div class="text-slate-400 text-xs font-bold pl-3 flex items-center gap-2">
                                    <div class="w-1 h-3 bg-slate-200 rounded-full"></div>
                                    {{ plan.limits.contacts }} Contacts Allowed
                                </div>
                            </div>
                        </div>

                        <button
                            @click="selectPlan(plan)"
                            :disabled="currentPlanId === plan.id"
                            class="px-12 py-6 bg-amber-400 text-amber-900 rounded-[2rem] font-black text-sm tracking-widest uppercase hover:bg-amber-500 transition-all shadow-2xl shadow-amber-200 hover:scale-105 active:scale-95 disabled:opacity-50 disabled:bg-slate-100 disabled:text-slate-400 disabled:shadow-none disabled:scale-100"
                        >
                            {{ currentPlanId === plan.id ? 'Already Active' : 'Get Lifetime Access' }}
                        </button>
                    </div>
                </div>

                <!-- Starter Plan (Horizontal) -->
                <div v-for="plan in plans.filter(p => ['Starter', 'Free'].includes(p.name))" :key="plan.id"
                     class="max-w-7xl mx-auto bg-gradient-to-r from-white via-slate-50 to-white rounded-[3rem] p-8 sm:p-12 border border-slate-100 shadow-sm hover:shadow-2xl transition-all duration-700 flex flex-col lg:flex-row items-center justify-between gap-10 relative overflow-hidden group">
                    
                    <!-- Interactive Blobs -->
                    <div class="absolute -right-20 -top-20 w-96 h-96 bg-red-500/5 rounded-full blur-[80px] group-hover:bg-red-500/10 transition-colors duration-700 animate-pulse"></div>
                    <div class="absolute -left-20 -bottom-20 w-80 h-80 bg-slate-200/20 rounded-full blur-[60px] group-hover:bg-slate-300/30 transition-colors duration-700"></div>
                    
                    <div class="relative z-10 flex-1 text-center lg:text-left">
                        <div class="inline-flex items-center px-4 py-1.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest mb-6">
                            Free forever module
                        </div>
                        <h4 class="text-4xl font-black text-slate-900 mb-4 tracking-tight">{{ plan.name }}</h4>
                        <p class="text-slate-500 font-medium max-w-md leading-relaxed text-lg">
                            Experience the core power of Sendora. Simple, elegant, and completely free for personal exploration.
                        </p>
                    </div>

                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-12 lg:gap-24">
                        <div class="text-center">
                            <div class="flex items-baseline justify-center">
                                <span class="text-xl font-bold text-slate-400 mr-1">{{ currency }}</span>
                                <span class="text-6xl font-black text-slate-900">0.00</span>
                                <span class="ml-1 text-slate-400 font-bold text-lg">/mo</span>
                            </div>
                            <p class="mt-2 text-[10px] font-black text-[#780116] uppercase tracking-widest bg-red-50 px-3 py-1 rounded-full inline-block">Zero Commitments</p>
                        </div>

                        <div class="grid grid-cols-1 gap-y-4">
                            <div class="flex items-center gap-4 text-slate-700 font-bold text-lg">
                                <div class="size-6 rounded-full bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-lg shadow-slate-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span>{{ plan.limits.whatsapp_nos }} WhatsApp Number</span>
                            </div>
                            <div class="flex flex-col gap-1 ml-10">
                                <div class="text-slate-500 text-sm font-bold flex items-center gap-2">
                                     <div class="w-1 h-4 bg-[#f7b538] rounded-full"></div>
                                     {{ plan.limits.messages }} Messages / month
                                </div>
                                <div class="text-slate-400 text-xs font-bold pl-3 flex items-center gap-2">
                                     <div class="w-1 h-3 bg-slate-200 rounded-full"></div>
                                     {{ plan.limits.contacts }} Contacts Allowed
                                </div>
                            </div>
                        </div>

                        <button 
                            @click="selectPlan(plan)"
                            :disabled="currentPlanId === plan.id || isDowngrade(plan)"
                            class="px-12 py-6 bg-slate-900 text-white rounded-[2rem] font-black text-sm tracking-widest uppercase hover:bg-slate-800 transition-all shadow-2xl hover:scale-105 active:scale-95 disabled:opacity-50 disabled:bg-slate-100 disabled:text-slate-400 disabled:shadow-none disabled:scale-100"
                        >
                            {{ 
                                currentPlanId === plan.id ? 'Already Active' : 
                                isDowngrade(plan) ? 'Unavailable' : 'Start Free' 
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
