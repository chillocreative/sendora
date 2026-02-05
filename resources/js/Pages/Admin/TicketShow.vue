<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    ticket: Object,
});

const form = useForm({
    message: '',
    status: props.ticket.status,
    attachments: [],
});

const fileInput = ref(null);
const previewFiles = ref([]);

const handleFiles = (e) => {
    const files = Array.from(e.target.files);
    if (form.attachments.length + files.length > 5) {
        alert('Maximum 5 files allowed.');
        return;
    }
    files.forEach(file => {
        form.attachments.push(file);
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (ev) => {
                previewFiles.value.push({ name: file.name, url: ev.target.result, type: 'image' });
            };
            reader.readAsDataURL(file);
        } else {
            previewFiles.value.push({ name: file.name, url: null, type: 'file' });
        }
    });
    e.target.value = '';
};

const removeFile = (index) => {
    form.attachments.splice(index, 1);
    previewFiles.value.splice(index, 1);
};

const submitReply = () => {
    form.post(route('admin.tickets.reply', props.ticket.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.message = '';
            form.attachments = [];
            previewFiles.value = [];
        },
    });
};

const statusColors = {
    open: 'bg-blue-100 text-blue-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    resolved: 'bg-green-100 text-green-700',
    closed: 'bg-slate-100 text-slate-500',
};

const priorityColors = {
    low: 'bg-slate-100 text-slate-600',
    medium: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600',
};

const statusLabel = (status) => status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());

const formatDate = (date) => {
    return new Date(date).toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout :title="'Ticket #' + ticket.id">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="flex items-start gap-4 mb-8">
                <Link :href="route('admin.tickets')" class="text-slate-400 hover:text-slate-600 transition mt-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-black text-slate-900">{{ ticket.subject }}</h1>
                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="statusColors[ticket.status]">
                            {{ statusLabel(ticket.status) }}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider" :class="priorityColors[ticket.priority]">
                            {{ ticket.priority }}
                        </span>
                        <span class="text-xs text-slate-400">#{{ ticket.id }} by {{ ticket.user?.name }}</span>
                    </div>
                </div>
            </div>

            <!-- Conversation Thread -->
            <div class="space-y-4 mb-8">
                <div
                    v-for="reply in ticket.replies"
                    :key="reply.id"
                    class="rounded-2xl border p-6"
                    :class="reply.is_admin
                        ? 'bg-[#780116]/5 border-[#780116]/10'
                        : 'bg-white border-slate-200'"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black text-white"
                             :class="reply.is_admin ? 'bg-[#780116]' : 'bg-slate-700'">
                            {{ reply.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </div>
                        <div>
                            <span class="text-sm font-bold text-slate-900">{{ reply.user?.name || 'Unknown' }}</span>
                            <span v-if="reply.is_admin" class="ml-2 px-2 py-0.5 bg-[#780116] text-white rounded text-[9px] font-black uppercase">Admin</span>
                            <span v-else class="ml-2 px-2 py-0.5 bg-slate-200 text-slate-600 rounded text-[9px] font-black uppercase">User</span>
                        </div>
                        <span class="text-xs text-slate-400 ml-auto">{{ formatDate(reply.created_at) }}</span>
                    </div>
                    <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-wrap">{{ reply.message }}</p>

                    <!-- Attachments -->
                    <div v-if="reply.attachments && reply.attachments.length > 0" class="flex flex-wrap gap-3 mt-4">
                        <a
                            v-for="(attachment, i) in reply.attachments"
                            :key="i"
                            :href="'/storage/' + attachment"
                            target="_blank"
                            class="block"
                        >
                            <img
                                v-if="attachment.match(/\.(jpg|jpeg|png|gif)$/i)"
                                :src="'/storage/' + attachment"
                                class="w-24 h-24 rounded-xl object-cover border border-slate-200 hover:opacity-80 transition"
                            />
                            <div v-else class="w-24 h-24 rounded-xl border border-slate-200 flex flex-col items-center justify-center bg-white hover:bg-slate-50 transition">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="text-[9px] text-slate-400 mt-1">PDF</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Admin Reply Form -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 mb-4">Admin Reply</h3>
                <form @submit.prevent="submitReply">
                    <textarea
                        v-model="form.message"
                        rows="4"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#780116]/20 focus:border-[#780116] transition resize-none"
                        placeholder="Type your reply to the user..."
                    ></textarea>
                    <p v-if="form.errors.message" class="text-red-500 text-xs mt-1">{{ form.errors.message }}</p>

                    <div class="flex items-center gap-4 mt-4 flex-wrap">
                        <!-- Attach -->
                        <button type="button" @click="fileInput?.click()" class="text-sm text-slate-500 hover:text-[#780116] transition flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                            Attach
                        </button>
                        <input ref="fileInput" type="file" class="hidden" multiple accept=".jpg,.jpeg,.png,.gif,.pdf" @change="handleFiles" />

                        <!-- Status Change -->
                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-500">Status:</label>
                            <select v-model="form.status" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-[#780116]/20">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div class="flex-1"></div>

                        <button
                            type="submit"
                            :disabled="form.processing || !form.message.trim()"
                            class="bg-[#780116] text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-[#c32f27] transition disabled:opacity-50"
                        >
                            <span v-if="form.processing">Sending...</span>
                            <span v-else>Send Reply</span>
                        </button>
                    </div>

                    <!-- Preview -->
                    <div v-if="previewFiles.length > 0" class="flex flex-wrap gap-3 mt-4">
                        <div v-for="(file, index) in previewFiles" :key="index" class="relative group">
                            <div v-if="file.type === 'image'" class="w-16 h-16 rounded-lg overflow-hidden border border-slate-200">
                                <img :src="file.url" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-16 h-16 rounded-lg border border-slate-200 flex items-center justify-center bg-slate-50">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <button type="button" @click="removeFile(index)" class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition">&times;</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
