<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();

const props = defineProps({
    tokens: Array,
    hasApiAccess: Boolean,
});

const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const showTokenModal = ref(false);
const newToken = ref('');
const deleteTokenId = ref(null);

const createForm = reactive({
    name: '',
    abilities: ['*'],
    processing: false,
});

const abilities = [
    { value: '*', label: 'Full Access', description: 'Access to all API endpoints' },
    { value: 'contacts:read', label: 'Read Contacts', description: 'View contacts list' },
    { value: 'contacts:write', label: 'Write Contacts', description: 'Create and update contacts' },
    { value: 'messages:send', label: 'Send Messages', description: 'Send WhatsApp messages' },
    { value: 'devices:read', label: 'Read Devices', description: 'View connected devices' },
    { value: 'campaigns:read', label: 'Read Campaigns', description: 'View campaigns' },
];

const openCreateModal = () => {
    createForm.name = '';
    createForm.abilities = ['*'];
    showCreateModal.value = true;
};

const createToken = () => {
    createForm.processing = true;
    router.post(route('api-tokens.store'), {
        name: createForm.name,
        abilities: createForm.abilities,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            // The new token is passed via flash
            setTimeout(() => {
                const token = page.props.flash?.token;
                if (token) {
                    newToken.value = token;
                    showTokenModal.value = true;
                }
            }, 100);
        },
        onFinish: () => {
            createForm.processing = false;
        },
    });
};

const confirmDelete = (tokenId) => {
    deleteTokenId.value = tokenId;
    showDeleteModal.value = true;
};

const deleteToken = () => {
    router.delete(route('api-tokens.destroy', deleteTokenId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteTokenId.value = null;
        },
    });
};

const copyToken = () => {
    navigator.clipboard.writeText(newToken.value);
};

const copyTokenId = (tokenId) => {
    const format = `${tokenId}|••••••••••••••••••••`;
    navigator.clipboard.writeText(format);
    alert('Token format copied! Note: This is just the format. The actual token was shown once during creation. If lost, please regenerate.');
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AppLayout title="API Tokens">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                Security Credentials
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                
                <!-- No API Access Warning -->
                <div v-if="!hasApiAccess" class="bg-gradient-to-br from-red-50 to-orange-50 border border-red-100 rounded-[2.5rem] p-10 text-center mb-12 shadow-xl shadow-red-100/50">
                    <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <svg class="w-10 h-10 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">API Interface Locked</h3>
                    <p class="text-slate-500 font-bold text-xs uppercase tracking-widest mb-8">Synchronized API access is exclusive to Business Tier members</p>
                    <a href="/subscription" class="inline-flex items-center px-10 py-5 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-2xl shadow-red-200 transform active:scale-95">
                        Upgrade To Business Tier
                    </a>
                </div>

                <!-- Header Card -->
                <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 p-10 mb-10 border border-slate-50">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                        <div>
                            <div class="flex items-center gap-5 mb-4">
                                <div class="bg-gradient-to-br from-[#780116] to-[#c32f27] w-14 h-14 rounded-2xl flex items-center justify-center shadow-2xl shadow-red-900/20 border border-white/10">
                                    <svg class="w-7 h-7 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-black text-slate-900 tracking-tight leading-none">Access Matrix</h3>
                            </div>
                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest leading-relaxed max-w-lg">Initialize and manage your protocol tokens for high-speed integration. Maintain strict custody of your credentials.</p>
                        </div>
                        <button 
                            v-if="hasApiAccess"
                            @click="openCreateModal"
                            class="w-full lg:w-auto bg-[#780116] text-white px-10 py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition-all shadow-2xl shadow-red-200/50 flex items-center justify-center gap-3 active:scale-95"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Generate New Token
                        </button>
                    </div>
                </div>

                <!-- Tokens List -->
                <div v-if="hasApiAccess" class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div v-if="tokens.length === 0" class="p-20 text-center bg-slate-50/30">
                        <div class="w-24 h-24 bg-white rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl shadow-slate-200">
                            <svg class="w-12 h-12 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h4 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">No Active Credentials</h4>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mb-10">Initial protocol synchronization required</p>
                        <button 
                            @click="openCreateModal"
                            class="bg-[#780116] text-white px-10 py-5 rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] shadow-2xl shadow-red-200/50 transition-all hover:bg-[#c32f27] active:scale-95"
                        >
                            Generate Initial Access
                        </button>
                    </div>

                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="token in tokens" :key="token.id" class="p-8 hover:bg-slate-50/50 transition-all group">
                            <div class="flex items-start justify-between">
                                <div class="flex-grow">
                                    <div class="flex items-center gap-5 mb-4">
                                        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center border border-red-100 group-hover:bg-white group-hover:shadow-lg transition-all">
                                            <svg class="w-6 h-6 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </div>
                                        <div class="flex-grow">
                                            <h4 class="font-black text-slate-900 text-lg tracking-tight uppercase">{{ token.name }}</h4>
                                            <div class="flex items-center gap-3 mt-1">
                                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Protocol Established {{ formatDate(token.created_at) }}</p>
                                                <span v-if="token.last_used_at" class="size-1 rounded-full bg-slate-200"></span>
                                                <p v-if="token.last_used_at" class="text-[10px] font-black text-[#db7c26] uppercase tracking-widest">Last Activity {{ formatDate(token.last_used_at) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Token Preview -->
                                    <div class="bg-slate-900 rounded-xl p-4 mb-4 ml-1 flex items-center justify-between gap-4">
                                        <div class="flex items-center gap-3 flex-grow min-w-0">
                                            <svg class="w-4 h-4 text-[#f7b538] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                            <code class="text-slate-400 font-mono text-xs truncate">Token ID: {{ token.id }} | ••••••••••••••••••••</code>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <button
                                                @click="copyTokenId(token.id)"
                                                class="p-2 bg-white/5 hover:bg-white/10 rounded-lg transition-all group/copy"
                                                title="Copy token format (actual token not stored)"
                                            >
                                                <svg class="w-4 h-4 text-slate-400 group-hover/copy:text-[#f7b538] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                            <div class="px-3 py-1 bg-red-500/10 text-red-400 rounded-lg text-[9px] font-black uppercase tracking-wider">Hidden</div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-2 mt-4 ml-1">
                                        <span
                                            v-for="ability in token.abilities"
                                            :key="ability"
                                            class="px-3 py-1 bg-[#780116]/5 text-[#780116] border border-[#780116]/10 rounded-lg text-[10px] font-black uppercase tracking-widest"
                                        >
                                            {{ ability === '*' ? 'Full Spectrum' : ability.replace(':', ' • ') }}
                                        </span>
                                    </div>

                                    <div class="mt-4 ml-1 flex items-start gap-2 p-3 bg-amber-50 border border-amber-100 rounded-xl">
                                        <svg class="w-4 h-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-[9px] font-bold text-amber-700 leading-relaxed">Full token was displayed once during creation. If lost, delete and regenerate.</p>
                                    </div>
                                </div>
                                <button
                                    @click="confirmDelete(token.id)"
                                    class="p-3 text-slate-300 hover:text-red-700 hover:bg-red-50 rounded-2xl transition-all"
                                    title="Terminate Token"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tips -->
                <div v-if="hasApiAccess" class="bg-gradient-to-br from-[#780116]/5 to-[#db7c26]/5 rounded-[2.5rem] p-10 mt-12 border border-red-50">
                    <h4 class="font-black text-slate-900 text-lg mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-2xl flex items-center justify-center shadow-lg shadow-red-100">
                            <svg class="w-5 h-5 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        Security Integrity Protocol
                    </h4>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-slate-50 shadow-sm transition-all hover:shadow-md">
                            <div class="size-6 rounded-lg bg-red-50 text-[#780116] flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500 leading-relaxed">Protect your protocol tokens; never commit to public repositories.</span>
                        </li>
                        <li class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-slate-50 shadow-sm transition-all hover:shadow-md">
                            <div class="size-6 rounded-lg bg-orange-50 text-[#db7c26] flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500 leading-relaxed">Utilize environment-level variables for secure server-side storage.</span>
                        </li>
                        <li class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-slate-50 shadow-sm transition-all hover:shadow-md">
                            <div class="size-6 rounded-lg bg-red-50 text-[#780116] flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs font-black uppercase tracking-widest text-slate-500 leading-relaxed">Isolate access by generating unique tokens for distinct environments.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Create Token Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-fade-in">
            <div class="bg-white rounded-[3rem] shadow-2xl max-w-lg w-full border border-slate-100 max-h-[85vh] overflow-y-auto custom-scrollbar">
                <div class="p-10 border-b border-slate-50">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight leading-none mb-3">Protocol Initiation</h3>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Establish a new high-speed uplink authentication key</p>
                </div>
                <form @submit.prevent="createToken" class="p-10 space-y-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Identifier Label</label>
                        <input 
                            v-model="createForm.name" 
                            type="text" 
                            required 
                            placeholder="e.g., Nexus Core Server"
                            class="w-full px-6 py-5 border-slate-100 rounded-[1.5rem] focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all bg-slate-50/50 placeholder:text-slate-300 font-bold"
                        />
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Authorization Scopes</label>
                        <div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                            <label 
                                v-for="ability in abilities" 
                                :key="ability.value"
                                class="flex items-start gap-4 p-5 rounded-[1.5rem] border border-slate-100/50 hover:bg-red-50 transition-all cursor-pointer group"
                            >
                                <input 
                                    type="checkbox" 
                                    :value="ability.value" 
                                    v-model="createForm.abilities"
                                    class="mt-1 w-5 h-5 text-[#780116] rounded-md border-slate-200 focus:ring-[#780116]"
                                />
                                <div>
                                    <div class="font-black text-slate-900 uppercase text-[11px] tracking-widest group-hover:text-[#780116] transition-colors">{{ ability.label }}</div>
                                    <div class="text-[10px] text-slate-400 font-bold uppercase mt-1">{{ ability.description }}</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 pt-6">
                        <button type="button" @click="showCreateModal = false" class="flex-1 px-8 py-5 rounded-2xl border border-slate-200 text-slate-400 font-black text-[11px] uppercase tracking-[0.2em] hover:bg-slate-50 transition active:scale-95">Cancel</button>
                        <button type="submit" :disabled="createForm.processing || !createForm.name" class="flex-[1.5] px-10 py-5 rounded-2xl bg-[#780116] text-white font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-2xl shadow-red-200/50 disabled:opacity-50 active:scale-95">
                            {{ createForm.processing ? 'Transmitting...' : 'Initialize Key' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Token Created Modal -->
        <div v-if="showTokenModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-50 p-4 animate-scale-in">
            <div class="bg-white rounded-[3rem] shadow-2xl max-w-xl w-full border border-slate-100">
                <div class="p-12 border-b border-slate-50">
                    <div class="flex items-center gap-6 mb-6">
                        <div class="w-16 h-16 bg-red-50 rounded-3xl flex items-center justify-center border border-red-100 shadow-xl shadow-red-100">
                            <svg class="w-8 h-8 text-[#780116]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight leading-none">Transmission Key Issued</h3>
                    </div>
                    <p class="text-slate-500 font-bold text-sm leading-relaxed">Credential initialized successfully. Secure this authentication vector immediately; it will not be re-transmitted for security integrity.</p>
                </div>
                <div class="p-12">
                    <div class="bg-slate-900 rounded-[2rem] p-8 mb-8 border-t-4 border-[#780116] shadow-2xl shadow-red-950/20">
                        <div class="flex flex-col gap-6">
                            <code class="text-[#f7b538] font-mono font-bold text-sm break-all leading-loose">{{ newToken }}</code>
                            <button 
                                @click="copyToken"
                                class="bg-[#780116] text-white px-8 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-[#c32f27] transition flex items-center justify-center gap-3 active:scale-95"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Copy Credential Buffer
                            </button>
                        </div>
                    </div>
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-6 text-xs text-[#780116] font-black uppercase tracking-widest leading-loose flex items-start gap-4">
                        <svg class="w-6 h-6 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Store the credential in a secure environment; visibility is permanent only until this session is terminated.</span>
                    </div>
                </div>
                <div class="p-12 border-t border-slate-50">
                    <button 
                        @click="showTokenModal = false; newToken = ''"
                        class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-black transition shadow-xl active:scale-95"
                    >
                        I Have Secured The Credential
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-50 p-4 animate-scale-in">
            <div class="bg-white rounded-[3rem] shadow-2xl max-w-md w-full border border-slate-100 p-10">
                <div class="text-center">
                    <div class="flex items-center justify-center w-20 h-20 rounded-3xl bg-red-50 mx-auto mb-8 shadow-xl shadow-red-100 border border-red-100">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-3xl font-black text-slate-900 mb-3 tracking-tight">Terminate Token?</h3>
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest leading-loose mb-10">Any external infrastructure using this authentication vector will lose uplink connectivity permanently.</p>
                    <div class="flex flex-col gap-4">
                        <button @click="deleteToken" class="w-full py-5 rounded-2xl bg-red-600 text-white font-black text-[11px] uppercase tracking-[0.2em] hover:bg-red-700 transition shadow-2xl shadow-red-200 active:scale-95">Deauthorize Credential</button>
                        <button @click="showDeleteModal = false" class="w-full py-5 rounded-2xl border border-slate-200 text-slate-400 font-black text-[11px] uppercase tracking-[0.2em] hover:bg-slate-50 transition active:scale-95">Retain Active Uplink</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
