<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});
</script>

<template>
    <Head title="Sendora - Engage Your WhatsApp Customers" />

    <div class="min-h-screen bg-white text-slate-900 font-sans selection:bg-red-100 selection:text-[#780116] overflow-x-hidden relative">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center group cursor-pointer">
                            <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </div>
                            <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-6">
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
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="pt-40 pb-20 px-4">
            <div class="max-w-7xl mx-auto text-center">
                
                <div class="relative inline-block">
                    <h1 class="relative text-5xl md:text-7xl font-black text-slate-900 mb-8 tracking-tight max-w-4xl mx-auto leading-[1.1]">
                        Engage Your Customers <br/>
                        <span class="text-[#780116]">Where They Live.</span>
                    </h1>
                </div>
                
                <p class="text-xl text-slate-500 mb-12 max-w-2xl mx-auto leading-relaxed relative">
                    <span class="absolute -left-8 top-0 text-red-200 opacity-20 hidden md:block">
                        <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                    </span>
                    The professional WhatsApp engagement platform for Malaysian SMEs. Automate campaigns, manage contacts, and grow your sales with precision.
                </p>
                
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-5 sm:space-y-0 sm:space-x-6">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-12 py-5 rounded-2xl text-xl font-black flex items-center justify-center shadow-2xl shadow-red-900/40 hover:bg-[#c32f27] transition">
                        Connect WhatsApp — It's Free
                    </Link>
                    <Link :href="route('pricing')" class="w-full sm:w-auto bg-slate-900 text-white px-12 py-5 rounded-2xl text-xl font-bold flex items-center justify-center">
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
        <section class="py-24 bg-slate-900 text-white overflow-hidden relative">
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-4xl md:text-5xl font-black mb-8 leading-tight">Scale your growth, <br/><span class="text-[#f7b538]">keep the human touch.</span></h2>
                        <p class="text-slate-400 text-xl mb-10 leading-relaxed">
                            Stop fighting for attention in crowded inboxes. Sendora turns the world's most popular messaging app into your highest-converting sales tool, allowing you to automate engagement without losing the personal connection your customers love.
                        </p>
                        <ul class="space-y-4 text-slate-300">
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Verified WhatsApp Business API usage
                            </li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Data-driven segmentation for precision
                            </li>
                            <li class="flex items-center">
                                <svg class="w-6 h-6 text-[#f7b538] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                24/7 Automation that sounds human
                            </li>
                        </ul>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                            <div class="text-3xl font-bold text-[#f7b538] mb-2">98%</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Open Rate</div>
                        </div>
                        <div class="p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm translate-y-8">
                            <div class="text-3xl font-bold text-[#db7c26] mb-2">45%</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">CTR</div>
                        </div>
                        <div class="p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm">
                            <div class="text-3xl font-bold text-[#d8572a] mb-2">3.5x</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Conversion</div>
                        </div>
                        <div class="p-8 bg-white/5 border border-white/10 rounded-3xl backdrop-blur-sm translate-y-8">
                            <div class="text-3xl font-bold text-[#c32f27] mb-2">24h</div>
                            <div class="text-sm text-slate-400 uppercase tracking-wider font-bold">Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4">
                <div class="text-center mb-20">
                    <h2 class="text-4xl font-black text-slate-900 mb-4">Everything you need to scale.</h2>
                    <p class="text-lg text-slate-500">Powerful features without the complexity.</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-12">
                    <div class="group">
                        <div class="w-16 h-16 bg-red-50 rounded-2xl mb-6 flex items-center justify-center text-[#780116] group-hover:bg-[#780116] group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Smart Campaigns</h3>
                        <p class="text-slate-500 leading-relaxed">Send personalized broadcasts without the ban-risk. Our Human-Stagger technology ensures your number remains safe.</p>
                    </div>
                    
                    <div class="group">
                        <div class="w-16 h-16 bg-blue-50 rounded-2xl mb-6 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Intelligent Tagging</h3>
                        <p class="text-slate-500 leading-relaxed">Segment your contacts by interest or purchase history. Send the right message to the right person, every time.</p>
                    </div>
                    
                    <div class="group">
                        <div class="w-16 h-16 bg-red-50 rounded-2xl mb-6 flex items-center justify-center text-[#d8572a] group-hover:bg-[#d8572a] group-hover:text-white transition-all duration-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-3">Scheduled Delivery</h3>
                        <p class="text-slate-500 leading-relaxed">Set your promos to go live while you sleep. Sendora manages your schedule so you can focus on building your brand.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="py-32 bg-slate-50 border-y border-slate-200 relative overflow-hidden">
            <div class="absolute inset-0 opacity-40 bg-[url('/images/steps_bg.png')] bg-cover bg-center"></div>
            <div class="max-w-7xl mx-auto px-4 relative z-10">
                <div class="text-center mb-24">
                    <h2 class="text-5xl font-black text-slate-900 mb-6 tracking-tight">Start engaging in minutes.</h2>
                    <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em]">Three simple steps to launch your first campaign.</p>
                </div>

                <div class="relative">
                    <!-- Connector Line (Desktop) -->
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-0.5 bg-slate-200 -translate-y-1/2 z-0"></div>

                    <div class="grid md:grid-cols-3 gap-12 relative z-10">
                        <!-- Step 1 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-slate-900 text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-slate-900/20 group-hover:scale-110 transition-transform">1</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Connect WhatsApp</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Scan the QR code to link your device or connect via Cloud API for enterprise limits.</p>
                        </div>

                        <!-- Step 2 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-[#db7c26] text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-orange-600/20 group-hover:scale-110 transition-transform">2</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Create Campaign</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Draft your message, add media, and select your target audience segment.</p>
                        </div>

                        <!-- Step 3 -->
                        <div class="bg-white/90 backdrop-blur-md p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-white text-center relative group hover:-translate-y-2 transition-all duration-500">
                            <div class="w-20 h-20 bg-[#780116] text-white rounded-3xl flex items-center justify-center text-3xl font-black mx-auto mb-8 shadow-2xl shadow-red-900/20 group-hover:scale-110 transition-transform">3</div>
                            <h3 class="text-2xl font-black mb-4 text-slate-900 tracking-tight leading-none">Engage Customers</h3>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed">Launch instantly or schedule for later. Watch open rates soar in real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="py-24 bg-white px-4">
            <div class="max-w-5xl mx-auto rounded-[3rem] bg-slate-900 p-12 md:p-20 text-center relative overflow-hidden">
                
                <h2 class="text-4xl md:text-5xl font-black text-white mb-8">Ready to grow your customer engagement?</h2>
                <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#780116] text-white px-10 py-5 rounded-2xl text-xl font-bold hover:bg-[#c32f27] transition shadow-2xl shadow-red-900/40">
                        Start for Free Now
                    </Link>
                    <Link :href="route('pricing')" class="w-full sm:w-auto bg-white/10 text-white border border-white/20 px-10 py-5 rounded-2xl text-xl font-bold hover:bg-white/20 transition">
                        Compare Plans
                    </Link>
                </div>
                <p class="mt-8 text-slate-400 font-medium tracking-wide flex items-center justify-center">
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
