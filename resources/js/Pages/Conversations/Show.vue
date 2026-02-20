<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted } from 'vue';

const props = defineProps({
    conversation: Object,
});

const messagesContainer = ref(null);

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

onMounted(scrollToBottom);

const toggleMode = (newStatus) => {
    router.put(route('conversations.toggle-mode', props.conversation.id), {
        status: newStatus,
    }, { preserveScroll: true });
};

const timeAgo = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' — ' + d.toLocaleDateString();
};

const confidenceColor = (score) => {
    if (score >= 0.8) return 'text-green-500';
    if (score >= 0.5) return 'text-yellow-500';
    return 'text-red-500';
};

const statusConfig = (status) => {
    switch (status) {
        case 'active':  return { label: 'AI Active',  dot: 'bg-green-400 animate-pulse',  badge: 'bg-green-50 text-green-600 border-green-100' };
        case 'paused':  return { label: 'Paused',     dot: 'bg-yellow-400',               badge: 'bg-yellow-50 text-yellow-600 border-yellow-100' };
        case 'closed':  return { label: 'Closed',     dot: 'bg-slate-300',                badge: 'bg-slate-100 text-slate-400 border-slate-200' };
        default:        return { label: status,       dot: 'bg-slate-300',                badge: 'bg-slate-100 text-slate-400 border-slate-200' };
    }
};
</script>

<template>
    <AppLayout title="Conversation">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('conversations.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <div>
                    <h2 class="font-black text-xl text-slate-800 leading-tight tracking-tight">
                        {{ conversation.contact_name || conversation.contact_phone }}
                    </h2>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">{{ conversation.contact_phone }}</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

                    <!-- Chat Thread -->
                    <div class="lg:col-span-3">
                        <div class="bg-white shadow-xl sm:rounded-[2rem] border border-slate-100 overflow-hidden flex flex-col" style="height: 70vh;">

                            <!-- Chat Header -->
                            <div class="px-8 py-5 border-b border-slate-50 flex items-center justify-between bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-black text-sm">
                                        {{ (conversation.contact_name || conversation.contact_phone || '?')[0].toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-900 text-sm">{{ conversation.contact_name || 'Unknown Contact' }}</div>
                                        <div class="text-[10px] font-bold text-slate-400">{{ conversation.whatsapp_number?.phone_number || '' }}</div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="statusConfig(conversation.status).badge">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="statusConfig(conversation.status).dot"></span>
                                    {{ statusConfig(conversation.status).label }}
                                </span>
                            </div>

                            <!-- Messages -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/30">
                                <div
                                    v-for="msg in conversation.messages"
                                    :key="msg.id"
                                    class="flex"
                                    :class="msg.direction === 'inbound' ? 'justify-start' : 'justify-end'"
                                >
                                    <div class="max-w-[75%]">
                                        <div
                                            class="rounded-2xl px-5 py-3 shadow-sm"
                                            :class="msg.direction === 'inbound'
                                                ? 'bg-white border border-slate-100 text-slate-800'
                                                : 'bg-blue-600 text-white'"
                                        >
                                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.body }}</p>
                                        </div>
                                        <div
                                            class="flex items-center gap-2 mt-1 px-2"
                                            :class="msg.direction === 'inbound' ? '' : 'justify-end'"
                                        >
                                            <span class="text-[9px] font-bold text-slate-300">{{ timeAgo(msg.created_at) }}</span>
                                            <span v-if="msg.sender_type === 'ai'" class="text-[9px] font-black uppercase tracking-widest text-blue-400">AI</span>
                                            <span
                                                v-if="msg.confidence_score"
                                                class="text-[9px] font-black"
                                                :class="confidenceColor(msg.confidence_score)"
                                            >
                                                {{ (msg.confidence_score * 100).toFixed(0) }}%
                                            </span>
                                            <span
                                                v-if="msg.reasoning_source && msg.reasoning_source !== 'unknown' && msg.reasoning_source !== 'not_specified'"
                                                class="text-[9px] font-bold text-slate-300 truncate max-w-[120px]"
                                                :title="msg.reasoning_source"
                                            >
                                                {{ msg.reasoning_source }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!conversation.messages || conversation.messages.length === 0" class="text-center py-12">
                                    <p class="text-slate-400 text-sm font-bold">No messages yet.</p>
                                </div>
                            </div>

                            <!-- Status Bar -->
                            <div
                                v-if="conversation.status === 'active'"
                                class="px-6 py-4 border-t border-slate-100 bg-green-50/50"
                            >
                                <p class="text-[10px] font-black text-green-600 uppercase tracking-widest text-center">
                                    AI is handling this conversation automatically.
                                </p>
                            </div>
                            <div
                                v-else-if="conversation.status === 'paused'"
                                class="px-6 py-4 border-t border-slate-100 bg-yellow-50"
                            >
                                <p class="text-[10px] font-black text-yellow-600 uppercase tracking-widest text-center">
                                    AI is paused. Resume to let AI handle replies automatically.
                                </p>
                            </div>
                            <div
                                v-else
                                class="px-6 py-4 border-t border-slate-100 bg-slate-50"
                            >
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">
                                    Conversation closed.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-6">

                        <!-- Controls -->
                        <div class="bg-white shadow-xl sm:rounded-[2rem] p-6 border border-slate-100">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-5">Controls</h3>
                            <div class="space-y-3">
                                <button
                                    v-if="conversation.status === 'active'"
                                    @click="toggleMode('paused')"
                                    class="w-full py-3 bg-yellow-50 text-yellow-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-yellow-100 border border-yellow-100 transition-all"
                                >
                                    Pause AI
                                </button>
                                <button
                                    v-if="conversation.status === 'paused'"
                                    @click="toggleMode('active')"
                                    class="w-full py-3 bg-green-50 text-green-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-green-100 border border-green-100 transition-all"
                                >
                                    Resume AI
                                </button>
                                <button
                                    v-if="conversation.status !== 'closed'"
                                    @click="toggleMode('closed')"
                                    class="w-full py-3 bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-100 border border-slate-100 transition-all"
                                >
                                    Close Conversation
                                </button>
                                <button
                                    v-if="conversation.status === 'closed'"
                                    @click="toggleMode('active')"
                                    class="w-full py-3 bg-green-50 text-green-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-green-100 border border-green-100 transition-all"
                                >
                                    Reopen &amp; Resume AI
                                </button>
                            </div>
                        </div>

                        <!-- Conversation Info -->
                        <div class="bg-white shadow-xl sm:rounded-[2rem] p-6 border border-slate-100">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-5">Details</h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Messages</div>
                                    <div class="font-black text-slate-900">{{ conversation.message_count }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Last Customer Message</div>
                                    <div class="font-bold text-slate-600 text-xs">
                                        {{ conversation.last_customer_message_at
                                            ? new Date(conversation.last_customer_message_at).toLocaleString()
                                            : 'Never' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Last AI Reply</div>
                                    <div class="font-bold text-slate-600 text-xs">
                                        {{ conversation.last_ai_reply_at
                                            ? new Date(conversation.last_ai_reply_at).toLocaleString()
                                            : 'None yet' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">WhatsApp Number</div>
                                    <div class="font-bold text-slate-600 text-xs">{{ conversation.whatsapp_number?.phone_number || '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
