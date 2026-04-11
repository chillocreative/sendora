<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    hasApiAccess: Boolean,
    baseUrl: String,
});

const activeEndpoint = ref('profile');

const endpoints = [
    {
        id: 'profile',
        name: 'Get Profile',
        method: 'GET',
        path: '/api/v1/profile',
        description: 'Retrieve your account profile and active subscription details.',
        params: [],
        response: `{
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
}`
    },
    {
        id: 'usage',
        name: 'Get Usage',
        method: 'GET',
        path: '/api/v1/usage',
        description: 'View your current usage counters and remaining plan limits for this billing period.',
        params: [],
        response: `{
  "success": true,
  "data": {
    "devices": { "used": 1, "limit": 5 },
    "reminders": { "used": 12, "limit": 0 },
    "features": {
      "api_access": true,
      "auto_reply": true,
      "google_calendar": true,
      "ai_command_parsing": true
    }
  }
}`
    },
    {
        id: 'devices',
        name: 'List Devices',
        method: 'GET',
        path: '/api/v1/devices',
        description: 'List all WhatsApp devices linked to your account. Use the returned "id" field as the device_id when sending messages.',
        params: [],
        response: `{
  "success": true,
  "data": [
    {
      "id": 30,
      "user_id": 7,
      "phone_number": "60148885659:42@s.whatsapp.net",
      "status": "connected",
      "phone_info": {
        "id": "60148885659:42@s.whatsapp.net",
        "lid": "164940266635464:42@lid",
        "name": "Your Business Name"
      },
      "created_at": "2026-02-05T17:20:08.000000Z",
      "updated_at": "2026-02-05T18:08:40.000000Z"
    }
  ]
}`
    },
    {
        id: 'send-message',
        name: 'Send Message',
        method: 'POST',
        path: '/api/v1/send-message',
        description: 'Send a WhatsApp text message from one of your connected devices.',
        params: [
            { name: 'device_id', type: 'integer', required: true, description: 'The ID of your WhatsApp device (from the List Devices endpoint).' },
            { name: 'to', type: 'string', required: true, description: 'Recipient phone number in international format, e.g. 60148885659.' },
            { name: 'message', type: 'string', required: true, description: 'Text body to deliver to the recipient.' },
        ],
        response: `{
  "success": true,
  "message_id": "BAE5F2C7A8B3D1E0"
}`
    },
    {
        id: 'send-file',
        name: 'Send File',
        method: 'POST',
        path: '/api/v1/send-file',
        description: 'Send a document, image, or other file attachment via base64 payload, with an optional caption.',
        params: [
            { name: 'device_id', type: 'integer', required: true, description: 'The ID of your WhatsApp device (from the List Devices endpoint).' },
            { name: 'to', type: 'string', required: true, description: 'Recipient phone number in international format, e.g. 60148885659.' },
            { name: 'message', type: 'string', required: false, description: 'Optional caption to accompany the file.' },
            { name: 'file_base64', type: 'string', required: true, description: 'Base64-encoded file content (without the data URI prefix).' },
            { name: 'filename', type: 'string', required: false, description: 'File name shown to the recipient. Defaults to document.pdf.' },
            { name: 'mimetype', type: 'string', required: false, description: 'MIME type of the file. Defaults to application/pdf.' },
        ],
        response: `{
  "success": true,
  "message_id": "BAE5F2C7A8B3D1E1"
}`
    },
];

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
};

const getMethodColor = (method) => {
    switch (method) {
        case 'GET': return 'bg-orange-50 text-[#db7c26] border-orange-100';
        case 'POST': return 'bg-red-50 text-[#780116] border-red-100';
        case 'PUT': return 'bg-amber-50 text-[#f7b538] border-amber-100';
        case 'DELETE': return 'bg-rose-50 text-rose-600 border-rose-100';
        default: return 'bg-slate-50 text-slate-500 border-slate-100';
    }
};

const selectedEndpoint = () => endpoints.find(e => e.id === activeEndpoint.value);
</script>

<template>
    <AppLayout title="API Documentation">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                API Documentation
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <!-- API Access Check -->
                <div v-if="!hasApiAccess" class="bg-gradient-to-br from-red-50 to-orange-50 border border-red-100 rounded-[2.5rem] p-10 text-center mb-12 shadow-xl shadow-red-100/50">
                    <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <svg class="w-10 h-10 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">API Access Locked</h3>
                    <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8">The REST API is available exclusively on the Business plan</p>
                    <a href="/subscription" class="inline-flex items-center px-10 py-5 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-2xl shadow-red-200 transform active:scale-95">
                        Upgrade To Business Plan
                    </a>
                </div>

                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-[#780116] via-[#c32f27] to-[#780116] rounded-[3rem] p-12 mb-12 text-white relative overflow-hidden shadow-2xl shadow-red-900/20">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAzNHYtMkgyNHYyaDEyeiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-10 text-center md:text-left">
                        <div class="flex-grow">
                            <div class="flex items-center justify-center md:justify-start gap-4 mb-6">
                                <div class="bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20">
                                    <svg class="w-8 h-8 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                </div>
                                <span class="text-[#f7b538] font-black uppercase tracking-[0.3em] text-xs">Sendora REST API</span>
                            </div>
                            <h1 class="text-4xl md:text-5xl font-black mb-6 tracking-tighter leading-none">Sendora API</h1>
                            <p class="text-white/80 font-bold text-sm md:text-base max-w-xl leading-relaxed mb-10">
                                Send WhatsApp messages and files directly from your own systems. A simple REST API that lets you
                                integrate Sendora's messaging, reminders, and automation features into any application.
                            </p>
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-6">
                                <div class="bg-black/20 backdrop-blur-md border border-white/10 rounded-2xl px-6 py-4 flex items-center gap-4">
                                    <span class="text-white/40 font-black text-[10px] uppercase tracking-widest">Base URL:</span>
                                    <code class="text-[#f7b538] font-mono font-bold text-sm">{{ baseUrl }}/api/v1</code>
                                </div>
                                <a href="/user/api-tokens" class="bg-white text-[#780116] px-8 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-[#f7b538] hover:text-white transition-all shadow-xl shadow-black/20 flex items-center gap-3">
                                    <svg class="w-5 h-5 font-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    Manage API Tokens
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Getting Started Section -->
                <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 p-10 mb-12 border border-slate-50">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-4">
                        <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center border border-orange-100">
                            <svg class="w-6 h-6 text-[#db7c26]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        Getting Started
                    </h2>
                    <ol class="space-y-4 text-slate-600 font-semibold text-sm leading-relaxed max-w-3xl">
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-red-50 text-[#780116] rounded-lg flex items-center justify-center font-black text-xs border border-red-100">1</span>
                            <span>Connect a WhatsApp number from <span class="font-bold text-slate-800">WhatsApp Manager</span> and wait until its status becomes <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">connected</code>.</span>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-red-50 text-[#780116] rounded-lg flex items-center justify-center font-black text-xs border border-red-100">2</span>
                            <span>Generate a personal access token from the <a href="/user/api-tokens" class="text-[#780116] font-bold underline decoration-2 underline-offset-2">API Tokens</a> page.</span>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-red-50 text-[#780116] rounded-lg flex items-center justify-center font-black text-xs border border-red-100">3</span>
                            <span>Call <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">GET /api/v1/devices</code> to fetch the device ID you'll use when sending messages.</span>
                        </li>
                        <li class="flex gap-4">
                            <span class="flex-shrink-0 w-7 h-7 bg-red-50 text-[#780116] rounded-lg flex items-center justify-center font-black text-xs border border-red-100">4</span>
                            <span>Use <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">POST /api/v1/send-message</code> or <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">POST /api/v1/send-file</code> to deliver content to any WhatsApp number.</span>
                        </li>
                    </ol>
                </div>

                <!-- Authentication Section -->
                <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 p-10 mb-12 border border-slate-50">
                    <h2 class="text-2xl font-black text-slate-900 mb-6 flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center border border-red-100">
                            <svg class="w-6 h-6 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        Authentication
                    </h2>
                    <p class="text-slate-500 font-bold text-sm mb-8 max-w-2xl leading-relaxed">
                        All API requests require a Bearer token in the <code class="text-[#780116] bg-red-50 px-1.5 py-0.5 rounded font-mono text-xs">Authorization</code> header.
                        Tokens are tied to your account and can be created or revoked at any time from the API Tokens page.
                    </p>
                    <div class="bg-slate-900 rounded-[1.5rem] p-6 font-mono text-sm overflow-x-auto shadow-2xl shadow-red-900/10 border-t-2 border-[#780116]">
                        <div class="text-slate-500 mb-3 font-bold uppercase text-[10px] tracking-widest">// Request header</div>
                        <div class="text-white font-bold tracking-tight">Authorization: <span class="text-[#f7b538]">Bearer</span> <span class="bg-white/10 px-3 py-1 rounded-lg text-white">your_api_token</span></div>
                    </div>
                </div>

                <!-- Endpoints Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-xl p-4 sticky top-4">
                            <h3 class="font-bold text-slate-900 mb-4 px-2">Endpoints</h3>
                            <nav class="space-y-1">
                                <button
                                    v-for="endpoint in endpoints"
                                    :key="endpoint.id"
                                    @click="activeEndpoint = endpoint.id"
                                    class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-left transition-all border border-transparent"
                                    :class="activeEndpoint === endpoint.id
                                        ? 'bg-red-50 text-[#780116] border-red-100 shadow-sm'
                                        : 'hover:bg-slate-50 text-slate-400 font-bold'"
                                >
                                    <span
                                        class="w-12 text-center py-1 rounded-lg text-[10px] font-black border uppercase tracking-wider"
                                        :class="getMethodColor(endpoint.method)"
                                    >
                                        {{ endpoint.method }}
                                    </span>
                                    <span class="font-black text-[11px] uppercase tracking-widest">{{ endpoint.name }}</span>
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                            <!-- Endpoint Header -->
                            <div class="p-10 border-b border-slate-50 bg-white">
                                <div class="flex items-center gap-4 mb-6">
                                    <span
                                        class="px-5 py-1.5 rounded-xl text-xs font-black border uppercase tracking-[0.2em]"
                                        :class="getMethodColor(selectedEndpoint().method)"
                                    >
                                        {{ selectedEndpoint().method }}
                                    </span>
                                    <code class="text-[#db7c26] font-mono font-bold bg-orange-50 px-4 py-1.5 rounded-xl border border-orange-100 text-sm">{{ selectedEndpoint().path }}</code>
                                </div>
                                <h2 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">{{ selectedEndpoint().name }}</h2>
                                <p class="text-slate-500 font-bold leading-relaxed">{{ selectedEndpoint().description }}</p>
                            </div>

                            <!-- Parameters -->
                            <div v-if="selectedEndpoint().params.length > 0" class="p-10 border-b border-slate-50">
                                <h3 class="font-black text-[10px] text-slate-400 uppercase tracking-[0.2em] mb-8">Request Parameters</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="text-[10px] text-slate-400 uppercase tracking-widest border-b border-slate-50">
                                                <th class="text-left font-black pb-4">Field</th>
                                                <th class="text-left font-black pb-4">Type</th>
                                                <th class="text-left font-black pb-4">Required</th>
                                                <th class="text-left font-black pb-4">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="param in selectedEndpoint().params" :key="param.name" class="border-b border-slate-50/50 last:border-0 hover:bg-slate-50/30 transition-colors">
                                                <td class="py-5"><code class="text-[#780116] font-mono font-bold">{{ param.name }}</code></td>
                                                <td class="py-5 font-bold text-slate-500">{{ param.type }}</td>
                                                <td class="py-5">
                                                    <span :class="param.required ? 'text-[#780116] bg-red-50 border-red-100' : 'text-slate-400 bg-slate-50 border-slate-100'" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border">
                                                        {{ param.required ? 'Required' : 'Optional' }}
                                                    </span>
                                                </td>
                                                <td class="py-5 text-slate-500 font-bold max-w-xs">{{ param.description }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Example Response -->
                            <div class="p-10 bg-slate-50/20">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="font-black text-[10px] text-slate-400 uppercase tracking-[0.2em]">Example Response</h3>
                                    <button
                                        @click="copyToClipboard(selectedEndpoint().response)"
                                        class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-[#780116] flex items-center gap-2 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Copy
                                    </button>
                                </div>
                                <div class="bg-slate-900 rounded-[2rem] p-8 font-mono text-sm overflow-x-auto shadow-2xl shadow-red-950/10 border-t-4 border-[#780116]">
                                    <pre class="text-[#f7b538] whitespace-pre-wrap leading-relaxed">{{ selectedEndpoint().response }}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Error Codes -->
                        <div class="bg-white rounded-2xl shadow-xl p-6 mt-8">
                            <h3 class="font-bold text-slate-900 mb-4">Error Codes</h3>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded font-mono text-sm font-bold">401</span>
                                    <span class="text-red-700">Unauthorized - Invalid or missing API token</span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-lg">
                                    <span class="bg-amber-100 text-amber-700 px-2 py-1 rounded font-mono text-sm font-bold">403</span>
                                    <span class="text-amber-700">Forbidden - API access is not enabled on your plan</span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
                                    <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded font-mono text-sm font-bold">404</span>
                                    <span class="text-orange-700">Not Found - Device not found or not owned by you</span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded font-mono text-sm font-bold">422</span>
                                    <span class="text-yellow-700">Validation Error - Check your request parameters or device status</span>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-slate-100 rounded-lg">
                                    <span class="bg-slate-200 text-slate-700 px-2 py-1 rounded font-mono text-sm font-bold">500</span>
                                    <span class="text-slate-700">Server Error - Something went wrong on our end</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
