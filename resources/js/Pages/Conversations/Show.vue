<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { ref, nextTick, onMounted } from 'vue';

const props = defineProps({
    conversation: Object,
});

const messagesContainer = ref(null);

const replyForm = useForm({
    message: '',
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

onMounted(scrollToBottom);

const sendReply = () => {
    replyForm.post(route('conversations.reply', props.conversation.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            scrollToBottom();
        },
    });
};

const toggleMode = (newStatus) => {
    router.put(route('conversations.toggle-mode', props.conversation.id), {
        status: newStatus,
    }, {
        preserveScroll: true,
    });
};

const timeAgo = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' - ' + d.toLocaleDateString();
};

const confidenceColor = (score) => {
    if (score >= 0.8) return 'text-green-500';
    if (score >= 0.5) return 'text-yellow-500';
    return 'text-red-500';
};

const isWithin24h = () => {
    if (!props.conversation.last_customer_message_at) return false;
    const diff = new Date() - new Date(props.conversation.last_customer_message_at);
    return diff < 24 * 60 * 60 * 1000;
};
</script>

<template>
    <AppLayout title="Conversation">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('conversations.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
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
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border" :class="conversation.status === 'active' ? 'bg-green-50 text-green-600 border-green-100' : conversation.status === 'escalated' ? 'bg-orange-50 text-orange-600 border-orange-100' : 'bg-slate-100 text-slate-400 border-slate-200'">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="conversation.status === 'active' ? 'bg-green-400 animate-pulse' : conversation.status === 'escalated' ? 'bg-orange-400' : 'bg-slate-300'"></span>
                                        {{ conversation.status === 'active' ? 'AI Active' : conversation.status === 'escalated' ? 'Human Mode' : 'Closed' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Messages -->
                            <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/30">
                                <div v-for="msg in conversation.messages" :key="msg.id" class="flex" :class="msg.direction === 'inbound' ? 'justify-start' : 'justify-end'">
                                    <div class="max-w-[75%]">
                                        <div class="rounded-2xl px-5 py-3 shadow-sm" :class="msg.direction === 'inbound' ? 'bg-white border border-slate-100 text-slate-800' : msg.sender_type === 'ai' ? 'bg-blue-600 text-white' : 'bg-[#780116] text-white'">
                                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.body }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1 px-2" :class="msg.direction === 'inbound' ? '' : 'justify-end'">
                                            <span class="text-[9px] font-bold text-slate-300">{{ timeAgo(msg.created_at) }}</span>
                                            <span v-if="msg.sender_type === 'ai'" class="text-[9px] font-black uppercase tracking-widest text-blue-400">AI</span>
                                            <span v-if="msg.sender_type === 'human'" class="text-[9px] font-black uppercase tracking-widest text-[#780116]">Human</span>
                                            <!-- AI Metadata -->
                                            <span v-if="msg.confidence_score" class="text-[9px] font-black" :class="confidenceColor(msg.confidence_score)">
                                                {{ (msg.confidence_score * 100).toFixed(0) }}%
                                            </span>
                                            <span v-if="msg.reasoning_source && msg.reasoning_source !== 'unknown'" class="text-[9px] font-bold text-slate-300 truncate max-w-[120px]" :title="msg.reasoning_source">
                                                {{ msg.reasoning_source }}
                                            </span>
                                        </div>
                                        <!-- Escalation Banner -->
                                        <div v-if="msg.escalation_reason" class="mt-1 px-3 py-1.5 bg-orange-50 border border-orange-100 rounded-xl text-[10px] font-black text-orange-600 uppercase tracking-wider">
                                            Escalated: {{ msg.escalation_reason }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!conversation.messages || conversation.messages.length === 0" class="text-center py-12">
                                    <p class="text-slate-400 text-sm font-bold">No messages yet.</p>
                                </div>
                            </div>

                            <!-- Reply Input (only when escalated and within 24h window) -->
                            <div v-if="conversation.status === 'escalated' && isWithin24h()" class="px-6 py-4 border-t border-slate-100 bg-white">
                                <form @submit.prevent="sendReply" class="flex gap-3">
                                    <input v-model="replyForm.message" type="text" placeholder="Type your reply..." class="flex-1 px-5 py-3 bg-slate-50 border-slate-100 rounded-2xl font-bold text-sm focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none" @keydown.enter.prevent="sendReply">
                                    <button type="submit" :disabled="replyForm.processing || !replyForm.message.trim()" class="px-6 py-3 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-[#c32f27] transition-all disabled:opacity-50 shadow-lg shadow-red-200">
                                        Send
                                    </button>
                                </form>
                            </div>
                            <div v-else-if="conversation.status === 'escalated' && !isWithin24h()" class="px-6 py-4 border-t border-slate-100 bg-orange-50">
                                <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest text-center">24-hour window expired. Cannot reply until customer messages again.</p>
                            </div>
                            <div v-else-if="conversation.status === 'active'" class="px-6 py-4 border-t border-slate-100 bg-green-50/50">
                                <p class="text-[10px] font-black text-green-500 uppercase tracking-widest text-center">AI is handling this conversation. Take over to reply manually.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar Controls -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Mode Controls -->
                        <div class="bg-white shadow-xl sm:rounded-[2rem] p-6 border border-slate-100">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-5">Controls</h3>
                            <div class="space-y-3">
                                <button v-if="conversation.status !== 'escalated'" @click="toggleMode('escalated')" class="w-full py-3 bg-orange-50 text-orange-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-orange-100 border border-orange-100 transition-all">
                                    Take Over (Human)
                                </button>
                                <button v-if="conversation.status !== 'active'" @click="toggleMode('active')" class="w-full py-3 bg-green-50 text-green-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-green-100 border border-green-100 transition-all">
                                    Resume AI
                                </button>
                                <button v-if="conversation.status !== 'closed'" @click="toggleMode('closed')" class="w-full py-3 bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-100 border border-slate-100 transition-all">
                                    Close Conversation
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
                                <div v-if="conversation.escalation_reason">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Escalation Reason</div>
                                    <div class="font-bold text-orange-600 text-xs">{{ conversation.escalation_reason }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Last Customer Message</div>
                                    <div class="font-bold text-slate-600 text-xs">{{ conversation.last_customer_message_at ? new Date(conversation.last_customer_message_at).toLocaleString() : 'Never' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">24h Window</div>
                                    <div class="font-black text-xs" :class="isWithin24h() ? 'text-green-500' : 'text-red-500'">
                                        {{ isWithin24h() ? 'Open' : 'Expired' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
