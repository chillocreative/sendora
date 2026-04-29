<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    baseUrl: String,
});

const showMobileMenu = ref(false);
const activeSection = ref('introduction');
const showSidebar = ref(false);

const sections = [
    { id: 'introduction', label: 'Introduction', group: 'Overview' },
    { id: 'platform-overview', label: 'Platform Overview', group: 'Overview' },
    { id: 'tech-stack', label: 'Tech Stack', group: 'Overview' },
    { id: 'features-whatsapp', label: 'WhatsApp Numbers', group: 'Features' },
    { id: 'features-reminders', label: 'Reminders', group: 'Features' },
    { id: 'features-google-calendar', label: 'Google Calendar Sync', group: 'Features' },
    { id: 'features-sendora-commands', label: '/sendora Commands', group: 'Features' },
    { id: 'features-playbooks', label: 'AI Playbooks', group: 'Features' },
    { id: 'features-conversations', label: 'Conversations', group: 'Features' },
    { id: 'features-subscriptions', label: 'Subscription Plans', group: 'Features' },
    { id: 'api-overview', label: 'API Overview', group: 'REST API' },
    { id: 'api-authentication', label: 'Authentication', group: 'REST API' },
    { id: 'api-getting-started', label: 'Getting Started', group: 'REST API' },
    { id: 'api-profile', label: 'GET /profile', group: 'REST API' },
    { id: 'api-usage', label: 'GET /usage', group: 'REST API' },
    { id: 'api-devices', label: 'GET /devices', group: 'REST API' },
    { id: 'api-send-message', label: 'POST /send-message', group: 'REST API' },
    { id: 'api-send-file', label: 'POST /send-file', group: 'REST API' },
    { id: 'api-errors', label: 'Error Codes', group: 'REST API' },
    { id: 'api-rate-limits', label: 'Rate Limits & Best Practices', group: 'REST API' },
    { id: 'webhooks', label: 'Webhooks', group: 'Integrations' },
    { id: 'integration-examples', label: 'Code Examples', group: 'Integrations' },
    { id: 'support', label: 'Support', group: 'Help' },
];

const groupedSections = computed(() => {
    const groups = {};
    for (const s of sections) {
        if (!groups[s.group]) groups[s.group] = [];
        groups[s.group].push(s);
    }
    return groups;
});

const apiBase = computed(() => `${props.baseUrl || 'https://sendora.cc'}/api/v1`);

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        const offset = 100;
        const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top, behavior: 'smooth' });
        activeSection.value = id;
        showSidebar.value = false;
    }
};

const copied = ref('');
const copy = (text, key) => {
    navigator.clipboard.writeText(text);
    copied.value = key;
    setTimeout(() => (copied.value = ''), 1500);
};

let observer = null;
onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) activeSection.value = entry.target.id;
            });
        },
        { rootMargin: '-100px 0px -65% 0px', threshold: 0 }
    );
    sections.forEach((s) => {
        const el = document.getElementById(s.id);
        if (el) observer.observe(el);
    });
});
onUnmounted(() => {
    if (observer) observer.disconnect();
});

const curlSendMessage = computed(() => `curl -X POST ${apiBase.value}/send-message \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json" \\
  -H "Content-Type: application/json" \\
  -d '{
    "device_id": 30,
    "to": "60148885659",
    "message": "Hello from Sendora!"
  }'`);

const curlSendFile = computed(() => `curl -X POST ${apiBase.value}/send-file \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json" \\
  -H "Content-Type: application/json" \\
  -d '{
    "device_id": 30,
    "to": "60148885659",
    "message": "Your invoice is attached.",
    "file_base64": "JVBERi0xLjQKJaqr...",
    "filename": "invoice.pdf",
    "mimetype": "application/pdf"
  }'`);

const curlDevices = computed(() => `curl ${apiBase.value}/devices \\
  -H "Authorization: Bearer YOUR_API_TOKEN" \\
  -H "Accept: application/json"`);

const nodeExample = computed(() => `import axios from 'axios';

const sendora = axios.create({
  baseURL: '${apiBase.value}',
  headers: {
    Authorization: \`Bearer \${process.env.SENDORA_TOKEN}\`,
    Accept: 'application/json',
  },
});

// 1. List your connected devices
const { data: devices } = await sendora.get('/devices');
const deviceId = devices.data[0].id;

// 2. Send a WhatsApp message
await sendora.post('/send-message', {
  device_id: deviceId,
  to: '60148885659',
  message: 'Hello from my app!',
});`);

const phpExample = computed(() => `use Illuminate\\Support\\Facades\\Http;

$response = Http::withToken(env('SENDORA_TOKEN'))
    ->acceptJson()
    ->post('${apiBase.value}/send-message', [
        'device_id' => 30,
        'to'        => '60148885659',
        'message'   => 'Hello from Laravel!',
    ]);

if ($response->successful()) {
    $messageId = $response->json('message_id');
}`);

const pythonExample = computed(() => `import os, requests

token = os.environ['SENDORA_TOKEN']
headers = {
    'Authorization': f'Bearer {token}',
    'Accept': 'application/json',
}

resp = requests.post(
    '${apiBase.value}/send-message',
    headers=headers,
    json={
        'device_id': 30,
        'to': '60148885659',
        'message': 'Hello from Python!',
    },
)
resp.raise_for_status()
print(resp.json()['message_id'])`);
</script>

<template>
    <Head title="Developer Documentation - Sendora">
        <meta name="description" content="Official Sendora developer documentation. Learn how to integrate the Sendora WhatsApp & Calendar platform with your application using the REST API." />
    </Head>

    <div class="min-h-screen bg-white text-slate-900 font-sans selection:bg-red-100 selection:text-[#780116] flex flex-col">
        <!-- Navigation -->
        <nav class="fixed w-full z-50 bg-white/90 backdrop-blur-md border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <button @click="showMobileMenu = true" class="lg:hidden mr-4 text-slate-500 hover:text-slate-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <Link :href="'/'" class="flex-shrink-0 flex items-center group cursor-pointer">
                            <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                        </Link>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden lg:flex items-center space-x-6">
                        <Link :href="'/'" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Home</Link>
                        <Link :href="route('pricing')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Pricing</Link>
                        <Link :href="route('faq')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">FAQ</Link>
                        <Link :href="route('docs')" class="text-sm font-bold text-[#780116] transition">Docs</Link>
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Dashboard</Link>
                            <template v-else>
                                <Link :href="route('login')" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Log in</Link>
                                <Link v-if="canRegister" :href="route('register')" class="bg-[#780116] text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-[#c32f27] transition shadow-md">Get Started</Link>
                            </template>
                        </template>
                    </div>

                    <!-- Mobile CTA -->
                    <div class="flex lg:hidden items-center gap-2">
                        <button @click="showSidebar = true" class="px-3 py-2 text-xs font-bold text-slate-600 border border-slate-200 rounded-full">On this page</button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Header Menu -->
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-slate-900/90 backdrop-blur-sm lg:hidden" @click="showMobileMenu = false"></div>
        <div :class="showMobileMenu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-2xl transition-transform duration-500 lg:hidden flex flex-col">
            <div class="h-20 px-4 flex items-center justify-between border-b border-slate-100">
                <Link :href="'/'" class="flex items-center">
                    <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                </Link>
                <button @click="showMobileMenu = false" class="size-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600 hover:text-slate-900 transition-all active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                <Link :href="'/'" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Home</Link>
                <Link :href="route('pricing')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Pricing</Link>
                <Link :href="route('faq')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">FAQ</Link>
                <Link :href="route('docs')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-[#780116] bg-red-50" @click="showMobileMenu = false">Docs</Link>
                <template v-if="canLogin">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Dashboard</Link>
                    <template v-else>
                        <Link :href="route('login')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50" @click="showMobileMenu = false">Log in</Link>
                        <Link v-if="canRegister" :href="route('register')" class="flex items-center px-4 py-4 text-sm font-bold rounded-xl bg-[#780116] text-white hover:bg-[#c32f27]" @click="showMobileMenu = false">Get Started</Link>
                    </template>
                </template>
            </nav>
        </div>

        <!-- Mobile Sidebar (TOC) -->
        <div v-if="showSidebar" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden" @click="showSidebar = false"></div>
        <div :class="showSidebar ? 'translate-x-0' : 'translate-x-full'" class="fixed inset-y-0 right-0 z-50 w-80 bg-white shadow-2xl transition-transform duration-300 lg:hidden flex flex-col">
            <div class="h-20 px-5 flex items-center justify-between border-b border-slate-100">
                <span class="text-sm font-black uppercase tracking-widest text-slate-700">On this page</span>
                <button @click="showSidebar = false" class="size-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-5 py-6 space-y-6">
                <div v-for="(items, group) in groupedSections" :key="group">
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-2">{{ group }}</p>
                    <ul class="space-y-1">
                        <li v-for="item in items" :key="item.id">
                            <button @click="scrollTo(item.id)" :class="activeSection === item.id ? 'text-[#780116] font-bold' : 'text-slate-600 font-medium'" class="w-full text-left text-sm py-1.5 hover:text-[#780116] transition">{{ item.label }}</button>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <!-- Hero -->
        <section class="pt-32 sm:pt-40 pb-12 sm:pb-16 px-4 bg-gradient-to-b from-slate-50 via-white to-white border-b border-slate-100 relative overflow-hidden">
            <div class="absolute inset-0 opacity-30 bg-[url('/images/steps_bg.png')] bg-cover bg-center"></div>
            <div class="max-w-7xl mx-auto relative z-10">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <span class="bg-red-50 text-[#780116] border border-red-100 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">Developer Documentation</span>
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-black text-slate-900 mb-5 tracking-tight text-center max-w-4xl mx-auto leading-[1.1]">
                    Build with <span class="text-[#780116]">Sendora</span>.
                </h1>
                <p class="text-base sm:text-lg text-slate-500 max-w-2xl mx-auto text-center leading-relaxed">
                    Everything you need to integrate Sendora's WhatsApp messaging, reminders, and Google Calendar features into your own product. From a single curl request to a full automation workflow.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <button @click="scrollTo('api-getting-started')" class="bg-[#780116] text-white px-6 py-3 rounded-full text-sm font-bold hover:bg-[#c32f27] transition shadow-md">Quickstart</button>
                    <button @click="scrollTo('api-overview')" class="bg-white border border-slate-200 text-slate-700 px-6 py-3 rounded-full text-sm font-bold hover:bg-slate-50 transition">API Reference</button>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <main class="flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                <div class="lg:grid lg:grid-cols-12 lg:gap-12">
                    <!-- Desktop Sidebar -->
                    <aside class="hidden lg:block lg:col-span-3">
                        <div class="sticky top-28 max-h-[calc(100vh-8rem)] overflow-y-auto pr-4">
                            <div v-for="(items, group) in groupedSections" :key="group" class="mb-8">
                                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">{{ group }}</p>
                                <ul class="space-y-0.5 border-l border-slate-100">
                                    <li v-for="item in items" :key="item.id">
                                        <button @click="scrollTo(item.id)"
                                            :class="activeSection === item.id ? 'text-[#780116] font-bold border-[#780116]' : 'text-slate-500 font-medium border-transparent hover:text-slate-900 hover:border-slate-300'"
                                            class="w-full text-left text-[13px] py-1.5 pl-4 -ml-px border-l-2 transition">
                                            {{ item.label }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </aside>

                    <!-- Article Content -->
                    <article class="lg:col-span-9 prose-doc">
                        <!-- Introduction -->
                        <section id="introduction" class="scroll-mt-28 mb-16">
                            <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#780116] mb-3">Overview</p>
                            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 mb-5 tracking-tight">Introduction</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Sendora is a WhatsApp-first scheduling and engagement platform. It connects a personal WhatsApp number to a Google Calendar, fires reminders at the moments that matter, parses natural-language commands with AI, and lets developers send messages or files programmatically through a REST API.
                            </p>
                            <p class="text-slate-600 leading-relaxed">
                                This guide describes every feature exposed to end users and every endpoint exposed to developers, so you can decide where Sendora fits in your stack and how to integrate it.
                            </p>
                        </section>

                        <!-- Platform Overview -->
                        <section id="platform-overview" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Platform Overview</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">
                                Sendora is composed of two cooperating services. A Laravel application handles users, subscriptions, scheduling, and the public dashboard. A Node.js companion service manages WhatsApp sessions through the Baileys library and forwards events back through webhooks. Developers only need to think about the public REST API — the rest is internal infrastructure.
                            </p>
                            <div class="grid sm:grid-cols-3 gap-4 mb-4">
                                <div class="border border-slate-100 rounded-2xl p-5 bg-slate-50/50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Laravel App</p>
                                    <p class="text-sm font-bold text-slate-900 mb-1">Business Logic</p>
                                    <p class="text-xs text-slate-500 leading-relaxed">Users, subscriptions, reminders, calendar sync, and the REST API surface.</p>
                                </div>
                                <div class="border border-slate-100 rounded-2xl p-5 bg-slate-50/50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Node.js Service</p>
                                    <p class="text-sm font-bold text-slate-900 mb-1">WhatsApp Engine</p>
                                    <p class="text-xs text-slate-500 leading-relaxed">Persistent multi-tenant WhatsApp sessions powered by Baileys.</p>
                                </div>
                                <div class="border border-slate-100 rounded-2xl p-5 bg-slate-50/50">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Queue Worker</p>
                                    <p class="text-sm font-bold text-slate-900 mb-1">Background Jobs</p>
                                    <p class="text-xs text-slate-500 leading-relaxed">Sends scheduled reminders, processes calendar syncs, dispatches webhooks.</p>
                                </div>
                            </div>
                        </section>

                        <!-- Tech Stack -->
                        <section id="tech-stack" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Tech Stack</h2>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Backend</span> — Laravel 12 (PHP 8.2+) with Jetstream and Sanctum.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Frontend</span> — Vue 3, Inertia.js, Tailwind CSS 4, Vite.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">WhatsApp</span> — Node.js + Express + @whiskeysockets/baileys with persistent sessions.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">AI</span> — OpenAI for /sendora command parsing and Playbook conversation engines.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Calendar</span> — Google Calendar API via OAuth 2.0.</span></li>
                            </ul>
                        </section>

                        <!-- Features divider -->
                        <div class="my-16 flex items-center gap-4">
                            <span class="h-px flex-1 bg-slate-100"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Features</span>
                            <span class="h-px flex-1 bg-slate-100"></span>
                        </div>

                        <!-- WhatsApp Numbers -->
                        <section id="features-whatsapp" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">WhatsApp Numbers</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Each user can connect one or more WhatsApp numbers (subject to plan limits). The connection is a multi-file Baileys session, persisted on disk so that reboots and redeploys do not require re-scanning a QR code.
                            </p>
                            <div class="rounded-2xl border border-slate-100 overflow-hidden mb-4">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <tr><th class="text-left py-3 px-4">Status</th><th class="text-left py-3 px-4">Meaning</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">disconnected</code></td><td class="py-3 px-4 text-slate-600">Not paired yet, or session was logged out.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">connecting</code></td><td class="py-3 px-4 text-slate-600">Initiating the WhatsApp Web socket.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">qr_ready</code></td><td class="py-3 px-4 text-slate-600">QR generated and ready to scan from a phone.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">connected</code></td><td class="py-3 px-4 text-slate-600">Authenticated and able to send and receive messages.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-slate-600 leading-relaxed">
                                You can only call the messaging API with a device whose status is <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">connected</code>. Use <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">GET /devices</code> to retrieve the device ID and current status.
                            </p>
                        </section>

                        <!-- Reminders -->
                        <section id="features-reminders" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Reminders</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Reminders are scheduled WhatsApp nudges. They are created from the dashboard, automatically generated from Google Calendar events, or parsed from a <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/sendora</code> message.
                            </p>
                            <ul class="space-y-2 text-sm text-slate-600 mb-4">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">One-time and recurring</span> — daily, weekly, monthly, yearly cadences.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Lead time</span> — pick how early you receive the nudge (5 min, 30 min, 1 hr, 1 day before).</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Timezone-aware</span> — reminders fire in the user's local timezone.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Audit trail</span> — every dispatch is logged with delivery status.</span></li>
                            </ul>
                            <p class="text-slate-600 leading-relaxed">
                                Reminders are user-facing today. A future API release will expose the same CRUD over REST. To stay informed, subscribe to product updates from your account dashboard.
                            </p>
                        </section>

                        <!-- Google Calendar Sync -->
                        <section id="features-google-calendar" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Google Calendar Sync</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Once a user authorises Google via OAuth 2.0, Sendora performs a periodic sync (every 15 minutes) of the primary calendar. Events become reminders on the user's chosen lead time, and reminders created via /sendora can optionally be written back to Google Calendar.
                            </p>
                            <ul class="space-y-2 text-sm text-slate-600 mb-4">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Two-way sync</span> — events created in either system show up in both.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Encrypted tokens</span> — refresh tokens are stored encrypted at rest.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Revocable</span> — disconnect at any time from the dashboard or your Google Account.</span></li>
                            </ul>
                            <p class="text-slate-500 text-xs">Note: Google Calendar sync is gated by subscription plan. See the Subscription Plans section.</p>
                        </section>

                        <!-- /sendora Commands -->
                        <section id="features-sendora-commands" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">/sendora Commands</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                The fastest way for end users to create reminders. Send a message starting with <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/sendora</code> to your own connected WhatsApp number, and the AI parses the request into a structured reminder.
                            </p>
                            <div class="bg-slate-900 rounded-2xl p-6 mb-4 border-t-2 border-[#780116]">
                                <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-3">Examples</div>
                                <pre class="text-[#f7b538] font-mono text-sm whitespace-pre-wrap leading-relaxed">/sendora Meeting tomorrow at 3pm with John
/sendora Pay rent every 1st of the month at 9am
/sendora Standup daily at 9am except weekends
/sendora list today
/sendora cancel last reminder</pre>
                            </div>
                            <p class="text-slate-500 text-xs">AI command parsing is included on Pro and Business plans.</p>
                        </section>

                        <!-- AI Playbooks -->
                        <section id="features-playbooks" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">AI Playbooks</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                A Playbook is a long-form document of instructions, FAQs, or tone-of-voice rules. Assign a Playbook to a connected WhatsApp number and the AI will reply on your behalf using the document as context. Useful for handling routine customer questions or qualifying leads while a human is unavailable.
                            </p>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Versioned</span> — every edit is saved; restore any prior revision instantly.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Per-conversation override</span> — pause AI in a specific chat with <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/stopchat</code>, resume with <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/startchat</code>.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span><span class="font-bold text-slate-800">Per-device assignment</span> — different numbers can run different Playbooks.</span></li>
                            </ul>
                        </section>

                        <!-- Conversations -->
                        <section id="features-conversations" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Conversations</h2>
                            <p class="text-slate-600 leading-relaxed">
                                Every inbound and outbound WhatsApp message tied to a connected number is captured into a Conversation. Conversations are searchable, taggable, and can be flipped between AI-assisted and manual mode. Reset a conversation to clear stale AI context using the dashboard.
                            </p>
                        </section>

                        <!-- Subscription Plans -->
                        <section id="features-subscriptions" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Subscription Plans</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Features are gated by plan. The current tiers are summarised below; visit the <Link :href="route('pricing')" class="text-[#780116] font-bold underline decoration-2 underline-offset-2">Pricing</Link> page for live limits and prices.
                            </p>
                            <div class="rounded-2xl border border-slate-100 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <tr>
                                            <th class="text-left py-3 px-4">Feature</th>
                                            <th class="text-left py-3 px-4">Basic</th>
                                            <th class="text-left py-3 px-4">Pro</th>
                                            <th class="text-left py-3 px-4">Business</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-600">
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">WhatsApp Numbers</td><td class="py-3 px-4">1</td><td class="py-3 px-4">3</td><td class="py-3 px-4">5</td></tr>
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">Active Reminders</td><td class="py-3 px-4">50</td><td class="py-3 px-4">500</td><td class="py-3 px-4">Unlimited</td></tr>
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">Google Calendar</td><td class="py-3 px-4">✓</td><td class="py-3 px-4">✓</td><td class="py-3 px-4">✓</td></tr>
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">/sendora AI Parsing</td><td class="py-3 px-4 text-slate-400">—</td><td class="py-3 px-4">✓</td><td class="py-3 px-4">✓</td></tr>
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">AI Playbooks</td><td class="py-3 px-4 text-slate-400">—</td><td class="py-3 px-4">✓</td><td class="py-3 px-4">✓</td></tr>
                                        <tr><td class="py-3 px-4 font-bold text-slate-800">REST API Access</td><td class="py-3 px-4 text-slate-400">—</td><td class="py-3 px-4 text-slate-400">—</td><td class="py-3 px-4">✓</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <!-- API divider -->
                        <div class="my-16 flex items-center gap-4">
                            <span class="h-px flex-1 bg-slate-100"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">REST API</span>
                            <span class="h-px flex-1 bg-slate-100"></span>
                        </div>

                        <!-- API Overview -->
                        <section id="api-overview" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">API Overview</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">
                                The Sendora REST API is a small, stable surface for sending WhatsApp messages and reading account state from your own systems. It is JSON in, JSON out, and authenticated with a Bearer token. The API is available exclusively on the Business plan.
                            </p>
                            <div class="bg-slate-900 rounded-2xl p-6 border-t-2 border-[#780116] flex items-center gap-6 flex-wrap">
                                <div>
                                    <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Base URL</div>
                                    <code class="text-[#f7b538] font-mono font-bold text-sm">{{ apiBase }}</code>
                                </div>
                                <div class="h-10 w-px bg-white/10 hidden sm:block"></div>
                                <div>
                                    <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Format</div>
                                    <code class="text-[#f7b538] font-mono font-bold text-sm">application/json</code>
                                </div>
                                <div class="h-10 w-px bg-white/10 hidden sm:block"></div>
                                <div>
                                    <div class="text-slate-400 text-[10px] font-black uppercase tracking-widest mb-1">Auth</div>
                                    <code class="text-[#f7b538] font-mono font-bold text-sm">Bearer Token</code>
                                </div>
                            </div>
                        </section>

                        <!-- Authentication -->
                        <section id="api-authentication" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Authentication</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Every request must carry a Sanctum personal access token in the <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">Authorization</code> header. Tokens are created from your dashboard at <span class="font-mono text-xs text-slate-700">/user/api-tokens</span> and scoped to your account. Treat them like passwords.
                            </p>
                            <div class="bg-slate-900 rounded-2xl p-6 font-mono text-sm border-t-2 border-[#780116] mb-4">
                                <div class="text-slate-500 mb-2 text-[10px] font-black uppercase tracking-widest">// Required header</div>
                                <div class="text-white">Authorization: <span class="text-[#f7b538]">Bearer</span> <span class="bg-white/10 px-2 py-0.5 rounded">your_api_token</span></div>
                            </div>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Tokens never expire automatically — revoke them when no longer needed.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Always use HTTPS in production. Plain HTTP requests are rejected.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Each request is also subject to your account's API access flag — losing the Business plan revokes API access.</span></li>
                            </ul>
                        </section>

                        <!-- Getting Started -->
                        <section id="api-getting-started" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Getting Started</h2>
                            <ol class="space-y-3 text-sm text-slate-600 mb-6">
                                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-red-50 text-[#780116] rounded-md flex items-center justify-center font-black text-[11px] border border-red-100">1</span><span>Subscribe to the Business plan from <Link :href="route('pricing')" class="text-[#780116] font-bold underline">Pricing</Link>.</span></li>
                                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-red-50 text-[#780116] rounded-md flex items-center justify-center font-black text-[11px] border border-red-100">2</span><span>Connect a WhatsApp number from the dashboard and confirm its status reads <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">connected</code>.</span></li>
                                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-red-50 text-[#780116] rounded-md flex items-center justify-center font-black text-[11px] border border-red-100">3</span><span>Generate a personal access token at <span class="font-mono text-xs text-slate-700">/user/api-tokens</span>.</span></li>
                                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-red-50 text-[#780116] rounded-md flex items-center justify-center font-black text-[11px] border border-red-100">4</span><span>Call <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">GET /devices</code> to fetch the <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">device_id</code> you'll send messages from.</span></li>
                                <li class="flex gap-3"><span class="flex-shrink-0 w-6 h-6 bg-red-50 text-[#780116] rounded-md flex items-center justify-center font-black text-[11px] border border-red-100">5</span><span>POST to <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/send-message</code>.</span></li>
                            </ol>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Smoke test</p>
                            <div class="relative">
                                <button @click="copy(curlDevices, 'qs-curl')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition flex items-center gap-1">{{ copied === 'qs-curl' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ curlDevices }}</code></pre>
                            </div>
                        </section>

                        <!-- GET /profile -->
                        <section id="api-profile" class="scroll-mt-28 mb-16">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-orange-50 text-[#db7c26] border border-orange-100">GET</span>
                                <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 text-sm">/api/v1/profile</code>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3 tracking-tight">Get Profile</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Returns the authenticated user's account info and active subscription, including resolved feature flags.</p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Example response</p>
                            <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{
  "success": true,
  "data": {
    "id": 7,
    "name": "Your Name",
    "email": "user@example.com",
    "subscription": {
      "plan": "Business",
      "status": "active",
      "limits": {
        "whatsapp_nos": 5,
        "reminders_per_month": 0,
        "features": {
          "api_access": true,
          "auto_reply": true,
          "google_calendar": true,
          "ai_command_parsing": true
        }
      }
    }
  }
}</code></pre>
                        </section>

                        <!-- GET /usage -->
                        <section id="api-usage" class="scroll-mt-28 mb-16">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-orange-50 text-[#db7c26] border border-orange-100">GET</span>
                                <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 text-sm">/api/v1/usage</code>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3 tracking-tight">Get Usage</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Returns current usage versus plan limits for the active billing period. A <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">limit</code> of <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">0</code> means unlimited.</p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Example response</p>
                            <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{
  "success": true,
  "data": {
    "devices":   { "used": 1,  "limit": 5  },
    "reminders": { "used": 12, "limit": 0  },
    "features": {
      "api_access": true,
      "auto_reply": true,
      "google_calendar": true,
      "ai_command_parsing": true
    }
  }
}</code></pre>
                        </section>

                        <!-- GET /devices -->
                        <section id="api-devices" class="scroll-mt-28 mb-16">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-orange-50 text-[#db7c26] border border-orange-100">GET</span>
                                <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 text-sm">/api/v1/devices</code>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3 tracking-tight">List Devices</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Lists every WhatsApp device tied to your account. The <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">id</code> field is what you pass as <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">device_id</code> when sending. Only devices with status <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">connected</code> can send messages.</p>
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Example response</p>
                            <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{
  "success": true,
  "data": [
    {
      "id": 30,
      "user_id": 7,
      "phone_number": "60148885659:42@s.whatsapp.net",
      "status": "connected",
      "phone_info": {
        "id": "60148885659:42@s.whatsapp.net",
        "name": "Your Business Name"
      },
      "created_at": "2026-02-05T17:20:08.000000Z",
      "updated_at": "2026-02-05T18:08:40.000000Z"
    }
  ]
}</code></pre>
                        </section>

                        <!-- POST /send-message -->
                        <section id="api-send-message" class="scroll-mt-28 mb-16">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">POST</span>
                                <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 text-sm">/api/v1/send-message</code>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3 tracking-tight">Send Message</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Sends a WhatsApp text message from one of your connected devices. Phone numbers must be in international format with no leading <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">+</code> or spaces.</p>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Request body</p>
                            <div class="rounded-2xl border border-slate-100 overflow-hidden mb-6">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <tr><th class="text-left py-3 px-4">Field</th><th class="text-left py-3 px-4">Type</th><th class="text-left py-3 px-4">Required</th><th class="text-left py-3 px-4">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-600">
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">device_id</code></td><td class="py-3 px-4">integer</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Device ID from <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/devices</code>.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">to</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Recipient in international format, e.g. <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">60148885659</code>.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">message</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Plain-text body delivered to the recipient.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Request example</p>
                            <div class="relative mb-6">
                                <button @click="copy(curlSendMessage, 'curl-send-msg')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition">{{ copied === 'curl-send-msg' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ curlSendMessage }}</code></pre>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Successful response</p>
                            <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{
  "success": true,
  "message_id": "BAE5F2C7A8B3D1E0"
}</code></pre>
                        </section>

                        <!-- POST /send-file -->
                        <section id="api-send-file" class="scroll-mt-28 mb-16">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">POST</span>
                                <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-3 py-1 rounded-lg border border-orange-100 text-sm">/api/v1/send-file</code>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-3 tracking-tight">Send File</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Sends a document, image, or other file as a base64 payload, with an optional caption. Use this for invoices, receipts, contracts, or media.</p>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Request body</p>
                            <div class="rounded-2xl border border-slate-100 overflow-hidden mb-6">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <tr><th class="text-left py-3 px-4">Field</th><th class="text-left py-3 px-4">Type</th><th class="text-left py-3 px-4">Required</th><th class="text-left py-3 px-4">Description</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-slate-600">
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">device_id</code></td><td class="py-3 px-4">integer</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Device ID from <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/devices</code>.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">to</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Recipient phone number, international format.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">file_base64</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-red-50 text-[#780116] border border-red-100">Required</span></td><td class="py-3 px-4">Base64-encoded file content (no data URI prefix).</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">message</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 border border-slate-100">Optional</span></td><td class="py-3 px-4">Caption shown alongside the file.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">filename</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 border border-slate-100">Optional</span></td><td class="py-3 px-4">File name shown to the recipient. Defaults to <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">document.pdf</code>.</td></tr>
                                        <tr><td class="py-3 px-4"><code class="text-[#780116] font-mono text-xs">mimetype</code></td><td class="py-3 px-4">string</td><td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest bg-slate-50 text-slate-500 border border-slate-100">Optional</span></td><td class="py-3 px-4">MIME type. Defaults to <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">application/pdf</code>.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Request example</p>
                            <div class="relative mb-6">
                                <button @click="copy(curlSendFile, 'curl-send-file')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition">{{ copied === 'curl-send-file' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ curlSendFile }}</code></pre>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Successful response</p>
                            <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{
  "success": true,
  "message_id": "BAE5F2C7A8B3D1E1"
}</code></pre>
                        </section>

                        <!-- Errors -->
                        <section id="api-errors" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Error Codes</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">All error responses follow the same shape: <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">{ "success": false, "message": "..." }</code>. Validation errors include an <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">errors</code> object keyed by field.</p>
                            <div class="space-y-3">
                                <div class="flex items-start gap-4 p-4 bg-red-50 border border-red-100 rounded-2xl">
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-lg font-mono text-sm font-bold">401</span>
                                    <div><p class="font-bold text-red-700">Unauthorized</p><p class="text-sm text-red-600">Missing or invalid API token.</p></div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-amber-50 border border-amber-100 rounded-2xl">
                                    <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded-lg font-mono text-sm font-bold">403</span>
                                    <div><p class="font-bold text-amber-700">Forbidden</p><p class="text-sm text-amber-600">API access is not enabled on your plan. Upgrade to Business.</p></div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-orange-50 border border-orange-100 rounded-2xl">
                                    <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-lg font-mono text-sm font-bold">404</span>
                                    <div><p class="font-bold text-orange-700">Not Found</p><p class="text-sm text-orange-600">Device not found or not owned by you.</p></div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-yellow-50 border border-yellow-100 rounded-2xl">
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-lg font-mono text-sm font-bold">422</span>
                                    <div><p class="font-bold text-yellow-700">Validation / Device State</p><p class="text-sm text-yellow-600">Invalid request body or device is not in <code class="text-yellow-800 bg-yellow-100 px-1 rounded font-mono text-xs">connected</code> state.</p></div>
                                </div>
                                <div class="flex items-start gap-4 p-4 bg-slate-100 border border-slate-200 rounded-2xl">
                                    <span class="bg-slate-200 text-slate-700 px-2 py-1 rounded-lg font-mono text-sm font-bold">500</span>
                                    <div><p class="font-bold text-slate-700">Server Error</p><p class="text-sm text-slate-600">Unexpected error. Retry with exponential backoff and contact support if it persists.</p></div>
                                </div>
                            </div>
                        </section>

                        <!-- Rate Limits -->
                        <section id="api-rate-limits" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Rate Limits & Best Practices</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                The platform enforces a baseline of 60 requests per minute per token. WhatsApp itself imposes additional throttling that becomes stricter with high-volume sending — Sendora honours its own internal cooldowns to keep your number safe.
                            </p>
                            <ul class="space-y-2 text-sm text-slate-600">
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Always check device status before sending. A 422 means the socket dropped — fall back to the dashboard's reconnect flow.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Space out bulk sends. WhatsApp may flag aggressive blasts on a fresh number.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Retry on 5xx with exponential backoff. Do not retry on 4xx without changing the request.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Phone numbers must be digits only, with country code, no <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">+</code> or spaces.</span></li>
                                <li class="flex gap-3"><span class="text-[#780116] font-black">•</span><span>Cache <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">/devices</code> — the device ID is stable for the life of the connection.</span></li>
                            </ul>
                        </section>

                        <!-- Webhooks -->
                        <div class="my-16 flex items-center gap-4">
                            <span class="h-px flex-1 bg-slate-100"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400">Integrations</span>
                            <span class="h-px flex-1 bg-slate-100"></span>
                        </div>

                        <section id="webhooks" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Webhooks</h2>
                            <p class="text-slate-600 leading-relaxed mb-4">
                                Outbound webhooks (Sendora → your server) are on the roadmap and will mirror the existing internal hooks — incoming-message, message-receipt, qr-update, status-update — over a signed HTTPS POST. If you need this today, get in touch via Support and we can enable an early-access endpoint scoped to your Business account.
                            </p>
                            <p class="text-slate-500 text-xs">Currently the webhook surface is reserved for the internal Node.js engine and is not externally configurable.</p>
                        </section>

                        <!-- Code Examples -->
                        <section id="integration-examples" class="scroll-mt-28 mb-16">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Code Examples</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">Drop-in snippets to get from zero to first message in your stack of choice.</p>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Node.js (axios)</p>
                            <div class="relative mb-8">
                                <button @click="copy(nodeExample, 'node')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition">{{ copied === 'node' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ nodeExample }}</code></pre>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">PHP (Laravel HTTP client)</p>
                            <div class="relative mb-8">
                                <button @click="copy(phpExample, 'php')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition">{{ copied === 'php' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ phpExample }}</code></pre>
                            </div>

                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">Python (requests)</p>
                            <div class="relative">
                                <button @click="copy(pythonExample, 'py')" class="absolute top-4 right-4 z-10 text-[10px] font-black text-slate-300 uppercase tracking-widest hover:text-[#f7b538] transition">{{ copied === 'py' ? 'Copied' : 'Copy' }}</button>
                                <pre class="bg-slate-900 rounded-2xl p-6 text-sm overflow-x-auto border-t-2 border-[#780116]"><code class="text-[#f7b538] font-mono whitespace-pre">{{ pythonExample }}</code></pre>
                            </div>
                        </section>

                        <!-- Support -->
                        <section id="support" class="scroll-mt-28 mb-8">
                            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 mb-5 tracking-tight">Support</h2>
                            <p class="text-slate-600 leading-relaxed mb-6">
                                Hit a wall, found a bug, or want a feature? Open a ticket from your dashboard and a human will get back to you. Business plan tickets are prioritised.
                            </p>
                            <div class="rounded-3xl bg-gradient-to-br from-[#780116] via-[#c32f27] to-[#780116] p-8 sm:p-10 text-white shadow-2xl shadow-red-900/20 relative overflow-hidden">
                                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                                    <div>
                                        <h3 class="text-xl sm:text-2xl font-black mb-2">Ready to integrate?</h3>
                                        <p class="text-white/80 text-sm max-w-md">Generate your first API token and start sending in under five minutes.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-3">
                                        <Link :href="route('register')" class="bg-white text-[#780116] px-6 py-3 rounded-full text-sm font-black hover:bg-[#f7b538] hover:text-white transition shadow-lg">Get Started</Link>
                                        <Link :href="route('pricing')" class="bg-white/10 border border-white/20 text-white px-6 py-3 rounded-full text-sm font-bold hover:bg-white/20 transition">View Pricing</Link>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </article>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<style scoped>
.prose-doc :deep(code) {
    font-feature-settings: 'liga' 0;
}
</style>
