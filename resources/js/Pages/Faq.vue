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
        answer: "Sendora is your personal WhatsApp reminder and calendar assistant. It helps you stay on top of your schedule by sending you WhatsApp notifications for upcoming events, tasks, and deadlines. Connect your Google Calendar, set reminders via the web dashboard or simple /sendora commands, and never miss an important moment again.",
        open: true
    },
    {
        question: "How do reminders work?",
        answer: "Setting reminders with Sendora is simple. You can create them through the web dashboard by filling in the details (title, date, time, recurrence), or by sending a /sendora command directly to your own WhatsApp number. Once set, Sendora will send you a WhatsApp notification at the scheduled time. You can configure how far in advance you want to be reminded (e.g., 5 minutes, 30 minutes, or 1 hour before).",
        open: false
    },
    {
        question: "How do I connect Google Calendar?",
        answer: "Connecting Google Calendar is quick and secure. Go to your Sendora dashboard, click 'Connect Google Calendar', and sign in with your Google account via the standard OAuth flow. Once authorized, Sendora will automatically sync your calendar events every 15 minutes and send you WhatsApp reminders for upcoming events based on your notification preferences.",
        open: false
    },
    {
        question: "What are /sendora commands?",
        answer: "The /sendora command is the fastest way to create reminders. Simply send a message to your own WhatsApp number starting with /sendora followed by your reminder details in natural language. For example: \"/sendora Meeting tomorrow at 3pm in my office\" or \"/sendora Call dentist on Friday at 10am\". Sendora will parse your message, create the reminder, and confirm it back to you instantly.",
        open: false
    },
    {
        question: "What reminder formats are supported?",
        answer: "Sendora supports a wide range of reminder formats to fit your schedule. You can create one-time reminders for specific dates and times, or set up recurring reminders on a daily, weekly, monthly, or yearly basis. For example, you can set a daily standup reminder at 9am, a weekly report reminder every Friday, or a monthly bill payment reminder on the 1st of each month.",
        open: false
    },
    {
        question: "How does timezone handling work?",
        answer: "Sendora uses the timezone configured in your account settings to ensure all reminders fire at the correct local time. When you first sign up, your timezone is automatically detected. You can update it anytime from your profile settings. All reminder times displayed in the dashboard and sent via WhatsApp will reflect your chosen timezone.",
        open: false
    },
    {
        question: "What plans are available?",
        answer: "Sendora offers three plans to match your needs. The Basic plan includes up to 50 active reminders and Google Calendar sync. The Pro plan supports up to 500 active reminders and adds AI-powered natural language parsing for smarter /sendora commands. The Business plan gives you unlimited reminders, priority support, and advanced features for power users. All plans include WhatsApp notifications and the web dashboard.",
        open: false
    },
    {
        question: "What are AI Playbooks?",
        answer: "AI Playbooks are automated WhatsApp response scripts powered by AI. You create a Playbook document containing instructions, FAQs, or workflows, and when someone messages your connected WhatsApp number, the AI reads the Playbook and responds on your behalf. This is useful for handling common questions, providing information, or automating repetitive conversations while you focus on what matters. You can pause AI for any specific conversation by sending /stopchat in that chat, and resume it with /startchat.",
        open: false
    },
    {
        question: "How do I pause or resume AI in a conversation?",
        answer: "You can control the AI assistant per conversation using simple WhatsApp commands. Send /stopchat in any conversation to pause the AI for that specific chat — the AI will stop replying to messages in that conversation while continuing to work normally in all other conversations. When you want the AI to start responding again, send /startchat in the same conversation. This gives you full control to take over conversations manually whenever needed, without affecting other chats.",
        open: false
    },
    {
        question: "Can I cancel or edit reminders?",
        answer: "Yes, you have full control over your reminders. From the web dashboard, you can edit any reminder's title, date, time, or recurrence pattern. You can also delete reminders you no longer need. Changes take effect immediately, so you will always receive notifications based on your latest settings.",
        open: false
    },
    {
        question: "Is my data secure?",
        answer: "Absolutely. Security is a top priority at Sendora. Your Google Calendar tokens are encrypted at rest using industry-standard encryption. WhatsApp communication uses end-to-end encryption provided by the WhatsApp protocol. We never store your message content longer than necessary, and your personal data is never shared with third parties. You can revoke Google Calendar access at any time from your Google account settings.",
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
                    Everything you need to know about staying on top of your schedule with Sendora. Your personal WhatsApp reminder and Google Calendar assistant.
                </p>
            </div>
        </section>

        <!-- Content & Copywriting -->
        <section class="py-16 sm:py-20 bg-white">
            <div class="max-w-4xl mx-auto px-4">
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-[2rem] sm:rounded-[2.5rem] p-8 sm:p-10 md:p-14 text-white shadow-2xl relative overflow-hidden mb-12 sm:mb-20">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#780116] rounded-full blur-[100px] opacity-20 transform translate-x-1/2 -translate-y-1/2"></div>

                    <h2 class="text-2xl sm:text-3xl font-black mb-4 sm:mb-6 leading-tight relative z-10">Why thousands of people trust Sendora?</h2>
                    <p class="text-slate-300 text-base sm:text-lg leading-relaxed mb-6 sm:mb-8 relative z-10">
                        Sendora turns WhatsApp into your personal reminder system. No more forgotten meetings, missed deadlines, or overlooked tasks. With Google Calendar integration and natural language commands, staying organized has never been easier.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 sm:gap-8 relative z-10">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#f7b538] mb-1">Instant Reminders</h4>
                                <p class="text-sm text-slate-400">Get WhatsApp notifications right when you need them. No app to open, no alarm to dismiss.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                             <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#f7b538] mb-1">Effortless Setup</h4>
                                <p class="text-sm text-slate-400">Connect Google Calendar in one click. Create reminders with simple /sendora commands.</p>
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
                <Link :href="route('tickets.create')" class="w-full sm:w-auto px-6 sm:px-8 py-4 bg-white border border-slate-200 text-slate-700 font-bold rounded-2xl hover:bg-slate-50 transition">
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
