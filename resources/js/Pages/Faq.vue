<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const showMobileMenu = ref(false);

const faqs = ref([
    {
        question: "What is Sendora?",
        answer: "Sendora is a powerful WhatsApp engagement platform designed for businesses. It allows you to send bulk messages, manage contacts, automate customer interactions, and integrate with your applications via REST API - all directly from your web browser.",
        open: true
    },
    {
        question: "Is my WhatsApp number safe from being banned?",
        answer: "Safety is our top priority. Sendora uses 'Human-Stagger' technology to simulate natural typing and sending patterns with variable time delays. Additionally, our AI Warmer Pool helps you build a strong reputation before sending large campaigns. Sendora also automatically inserts Interval Pauses — random breaks of 1–7 minutes after every few messages — further mimicking human behaviour and significantly reducing the risk of bans.",
        open: false
    },
    {
        question: "How does the AI Warmer Pool work?",
        answer: "The AI Warmer Pool is a community-powered feature. When enabled, your number will automatically exchange casual, realistic messages with other numbers in the Sendora community using OpenAI. This simulates natural human interaction, building trust with WhatsApp and reducing ban risks. If you see casual messages from 'strangers' appearing in your chat, that's our AI helping you warm up!",
        open: false
    },
    {
        question: "Do I need the official WhatsApp Business API?",
        answer: "No! Sendora works by scanning the QR code of your existing WhatsApp or WhatsApp Business app, just like WhatsApp Web. This means you can get started instantly without complex approvals, per-message fees, or waiting for Meta's approval process.",
        open: false
    },
    {
        question: "Can I send media files like images or PDFs?",
        answer: "Absolutely. Sendora supports multiple media types including images (JPG, PNG), documents (PDF), text messages, and emojis. You can attach these to your campaigns to create rich, engaging messages. All media uploads are handled securely through our platform.",
        open: false
    },
    {
        question: "What is AI Customer Support and how does it work?",
        answer: "AI Customer Support lets each WhatsApp number have its own AI Playbook — a document containing your business SOPs, pricing, FAQs, and objection-handling scripts. When a customer messages you first, Sendora's AI reads the Playbook and replies automatically on your behalf, 24/7. You stay in control: pause or resume AI for any individual conversation at any time, and review the full AI reply audit trail in the conversation log.",
        open: false
    },
    {
        question: "What is an AI Playbook?",
        answer: "An AI Playbook is a document you write for each WhatsApp number. It contains your business's SOPs — pricing, FAQs, objection handling, greetings, and anything else your team would normally say to a customer. When a customer messages you first, Sendora's AI reads the Playbook and replies on your behalf, 24/7. You stay in control: pause AI for any conversation, review every AI reply in the audit log, and update the Playbook anytime.",
        open: false
    },
    {
        question: "Does Sendora have an API for developers?",
        answer: "Yes! Our Business plan includes full REST API access. You can programmatically manage contacts, send messages, check usage stats, and more. API tokens are secured with encryption and can be copied anytime from your dashboard. We provide comprehensive documentation, test scripts, and interactive API docs at /api-docs.",
        open: false
    },
    {
        question: "How do I manage my API tokens?",
        answer: "Go to the API Tokens page in your dashboard. You can create new tokens with specific permissions (Full Access or granular abilities), view all your tokens, and copy the actual token anytime using the copy button. Tokens are encrypted and stored securely. If you lose a token, simply delete it and create a new one.",
        open: false
    },
    {
        question: "Can I schedule campaigns?",
        answer: "Yes! Sendora supports scheduled campaigns. You can set a specific date and time for your campaign to be sent. You can also enable drip campaigns with delay intervals between messages to avoid spam detection and maintain natural sending patterns. Sendora also automatically inserts random batch pauses (1–7 minutes) after every few messages during sending, to further mimic human behaviour and reduce ban risk.",
        open: false
    },
    {
        question: "How does contact management work?",
        answer: "Sendora provides a comprehensive contact management system. Import contacts individually or in bulk, organise them with tags, and search by name or phone number. You can also organise contacts into named Contact Books for easy targeting — send a campaign to one or multiple Contact Books in a single click.",
        open: false
    },
    {
        question: "Can multiple team members use one account?",
        answer: "Yes! Our Business plan supports multi-user access, allowing your team to collaborate on campaigns, manage contacts, and track results together. Each team member can access the dashboard from their own browser with secure authentication.",
        open: false
    },
    {
        question: "How does the pricing work?",
        answer: "Sendora offers monthly plans — Basic, Pro, and Business — each with increasing limits on WhatsApp numbers, contacts, and features. We also offer a Lifetime plan: a one-time payment that gives you Pro-level features forever with no recurring charges. There are no hidden per-message costs and no Meta API fees.",
        open: false
    },
    {
        question: "What happens if I exceed my message limit?",
        answer: "When you reach your monthly message limit, you won't be able to send new messages until the next billing cycle or until you upgrade your plan. You can always check your usage stats in real-time from the dashboard or via the API (/api/v1/usage endpoint).",
        open: false
    },
    {
        question: "How do I connect my WhatsApp number?",
        answer: "Simply go to WhatsApp Manager, click 'Add Number', and scan the QR code that appears with your WhatsApp mobile app (like connecting to WhatsApp Web). Your number will be connected instantly and you can start sending messages. The number of WhatsApp numbers you can connect depends on your plan.",
        open: false
    },
    {
        question: "What if I need to cancel my subscription?",
        answer: "You are free to cancel anytime directly from your subscription dashboard. There are no lock-in contracts or cancellation fees. Your access will continue until the end of your current billing period, and you can export all your data before canceling.",
        open: false
    }
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};
</script>

<template>
    <Head title="FAQ - Frequently Asked Questions" />

    <div class="min-h-screen bg-white text-slate-900 font-sans selection:bg-red-100 selection:text-[#780116] overflow-x-hidden relative flex flex-col">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-white/80 backdrop-blur-md border-b border-slate-100">
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
                        <Link :href="route('pricing')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Pricing</Link>
                        <Link :href="route('faq')" class="text-sm font-bold text-[#780116] transition">FAQ</Link>
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
                <Link :href="route('pricing')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">
                    Pricing
                </Link>
                <Link :href="route('faq')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-[#780116] bg-red-50" @click="showMobileMenu = false">
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
                            Get Started
                        </Link>
                    </template>
                </template>
            </nav>
        </div>

        <!-- Hero Section -->
        <section class="pt-32 sm:pt-40 pb-16 sm:pb-20 px-4 bg-slate-50 relative overflow-hidden">
             <div class="absolute inset-0 opacity-40 bg-[url('/images/steps_bg.png')] bg-cover bg-center"></div>
            <div class="max-w-7xl mx-auto text-center relative z-10">
                <h1 class="text-3xl sm:text-4xl md:text-6xl font-black text-slate-900 mb-4 sm:mb-6 tracking-tight">
                    Got Questions? <br class="hidden sm:block"/>
                    <span class="text-[#780116]">We've Got Answers.</span>
                </h1>
                <p class="text-base sm:text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed">
                    Everything you need to know about blasting your sales with Sendora. Your success is our priority, so we've compiled the essentials to get you started.
                </p>
            </div>
        </section>

        <!-- Content & Copywriting -->
        <section class="py-16 sm:py-20 bg-white">
            <div class="max-w-4xl mx-auto px-4">
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] sm:rounded-[2.5rem] p-8 sm:p-10 md:p-14 text-white shadow-2xl relative overflow-hidden mb-12 sm:mb-20">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#780116] rounded-full blur-[100px] opacity-20 transform translate-x-1/2 -translate-y-1/2"></div>

                    <h2 class="text-2xl sm:text-3xl font-black mb-4 sm:mb-6 leading-tight relative z-10">Why over 1,000+ businesses trust us?</h2>
                    <p class="text-slate-300 text-base sm:text-lg leading-relaxed mb-6 sm:mb-8 relative z-10">
                        At Sendora, we believe that customer engagement shouldn't be complicated. In a world where emails go unread and social media algorithms hide your posts, WhatsApp remains the single most effective channel to reach your audience.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8 relative z-10">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#f7b538] mb-1">Instant Impact</h4>
                                <p class="text-sm text-slate-400">98% open rates mean your message gets seen within minutes, not days.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                             <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#f7b538] mb-1">Simplicity First</h4>
                                <p class="text-sm text-slate-400">No coding required. If you can use WhatsApp, you can use Sendora.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Accordion -->
                <div class="space-y-4">
                    <div v-for="(faq, index) in faqs" :key="index" 
                         class="border border-slate-100 rounded-3xl bg-white overflow-hidden transition-all duration-300 hover:shadow-lg hover:shadow-slate-100"
                         :class="{'ring-2 ring-slate-50': faq.open}">
                        <button @click="toggleFaq(index)" class="w-full flex items-center justify-between p-6 md:p-8 text-left focus:outline-none group">
                            <span class="text-lg md:text-xl font-bold text-slate-900 group-hover:text-[#780116] transition-colors">{{ faq.question }}</span>
                            <span class="w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300 shrink-0 ml-4"
                                  :class="faq.open ? 'bg-[#780116] text-white rotate-180' : 'bg-slate-100 text-slate-400 group-hover:bg-slate-200'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <div v-show="faq.open" class="px-6 md:px-8 pb-8 animate-fade-in-down">
                            <p class="text-slate-500 leading-relaxed border-t border-slate-50 pt-4">
                                {{ faq.answer }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-20 bg-slate-50 text-center px-4">
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-6">Still have questions?</h3>
            <div class="flex flex-col sm:flex-row justify-center gap-4 max-w-md mx-auto">
                <Link :href="route('support.index')" class="w-full sm:w-auto px-6 sm:px-8 py-4 bg-white border border-slate-200 text-slate-700 font-bold rounded-2xl hover:bg-slate-50 transition">
                    Contact Support
                </Link>
                <Link :href="route('register')" class="w-full sm:w-auto px-6 sm:px-8 py-4 bg-[#780116] text-white font-bold rounded-2xl hover:bg-[#c32f27] shadow-xl shadow-red-100 transition">
                    Get Started
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <Footer />
    </div>
</template>

<style scoped>
.animate-fade-in-down {
    animation: fadeInDown 0.3s ease-out;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
