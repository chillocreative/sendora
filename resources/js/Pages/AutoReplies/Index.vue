<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    autoReplies: Object,
});

const showModal = ref(false);
const editingId = ref(null);

const form = useForm({
    keyword: '',
    match_type: 'contains',
    reply_message: '',
    is_active: true,
});

const openCreateModal = () => {
    editingId.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (reply) => {
    editingId.value = reply.id;
    form.keyword = reply.keyword;
    form.match_type = reply.match_type || 'contains';
    form.reply_message = reply.reply_message;
    form.is_active = !!reply.is_active; // Ensure boolean
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const save = () => {
    if (editingId.value) {
        form.put(route('auto-replies.update', editingId.value), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('auto-replies.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteReply = (id) => {
    if (confirm('Are you sure you want to delete this auto-reply?')) {
        router.delete(route('auto-replies.destroy', id));
    }
};

const toggleActive = (reply) => {
    router.put(route('auto-replies.update', reply.id), {
        keyword: reply.keyword,
        match_type: reply.match_type || 'contains',
        reply_message: reply.reply_message,
        is_active: !reply.is_active,
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Auto-Replies">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                Automation Protocols
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Toolbar -->
                    <div class="p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-center bg-white gap-6">
                        <div class="text-center sm:text-left">
                             <h3 class="font-black text-slate-900 text-xl tracking-tight leading-none mb-2">Neural Ruleset</h3>
                             <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Execute automated response clusters based on keyword triggers</p>
                        </div>
                        <div class="flex space-x-3 w-full sm:w-auto">
                             <button @click="openCreateModal" class="w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-[#780116] text-white rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-xl shadow-red-200 transform active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Deploy New Rule
                             </button>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="overflow-x-auto">
                        <table v-if="autoReplies.data.length > 0" class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 font-black">Keyword Identifier</th>
                                    <th class="px-8 py-5 font-black">Match Type</th>
                                    <th class="px-8 py-5 font-black">Transmission Payload</th>
                                    <th class="px-8 py-5 font-black text-center">Protocol State</th>
                                    <th class="px-8 py-5 font-black text-right">Operations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="reply in autoReplies.data" :key="reply.id" class="hover:bg-slate-50/50 transition-all group">
                                    <td class="px-8 py-6">
                                        <span class="inline-block px-3 py-1 bg-red-50 rounded-lg text-xs font-black text-[#780116] border border-red-100 uppercase tracking-wider">{{ reply.keyword }}</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                            :class="reply.match_type === 'exact' ? 'bg-purple-50 text-purple-600 border border-purple-200' : 'bg-blue-50 text-blue-600 border border-blue-200'">
                                            {{ reply.match_type === 'exact' ? '= Exact' : '⊃ Contains' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-slate-600 font-bold max-w-xs truncate" :title="reply.reply_message">{{ reply.reply_message }}</td>
                                    <td class="px-8 py-6 text-center">
                                        <button @click="toggleActive(reply)" 
                                            class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all"
                                            :class="reply.is_active ? 'bg-orange-50 text-[#db7c26] hover:bg-orange-100 shadow-sm border border-orange-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                        >
                                            <span class="w-2 h-2 rounded-full mr-2" :class="reply.is_active ? 'bg-[#f7b538] animate-pulse' : 'bg-slate-300'"></span>
                                            {{ reply.is_active ? 'Authorized' : 'Standby' }}
                                        </button>
                                    </td>
                                    <td class="px-8 py-6 text-right space-x-3">
                                        <button @click="openEditModal(reply)" class="p-2 text-slate-300 hover:text-[#780116] hover:bg-red-50 rounded-xl transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteReply(reply.id)" class="p-2 text-slate-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-12 text-center">
                            <div class="inline-flex p-4 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">No rules yet</h3>
                            <p class="text-slate-500 mt-1">Create your first auto-reply rule to start automating.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="showModal" @close="closeModal">
            <template #title>
                {{ editingId ? 'Edit Auto-Reply' : 'New Auto-Reply' }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel for="keyword" value="Keyword" />
                        <TextInput
                            id="keyword"
                            v-model="form.keyword"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g. hello, pricing, support"
                            autofocus
                        />
                        <InputError :message="form.errors.keyword" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="match_type" value="Match Type" />
                        <select
                            id="match_type"
                            v-model="form.match_type"
                            class="mt-1 block w-full border-slate-200 focus:border-[#780116] focus:ring-[#780116] rounded-lg shadow-sm"
                        >
                            <option value="contains">Contains keyword (e.g., "hello" matches "hello there")</option>
                            <option value="exact">Exact match only (e.g., "hello" matches only "hello")</option>
                        </select>
                        <InputError :message="form.errors.match_type" class="mt-2" />
                        <p class="text-xs text-slate-400 mt-1">
                            <span v-if="form.match_type === 'exact'">Triggers only when the message is exactly "{{ form.keyword }}"</span>
                            <span v-else>Triggers when message contains "{{ form.keyword }}" anywhere</span>
                        </p>
                    </div>
                    <div>
                        <InputLabel for="reply_message" class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-2" value="Response Signature" />
                        <textarea
                            id="reply_message"
                            v-model="form.reply_message"
                            class="mt-1 block w-full border-slate-200 focus:border-[#780116] focus:ring-[#780116] rounded-2xl shadow-none py-4 px-5 font-bold transition-all bg-slate-50/50 placeholder:text-slate-300"
                            rows="5"
                            placeholder="Initialize automated response cluster..."
                        ></textarea>
                        <InputError :message="form.errors.reply_message" class="mt-2" />
                    </div>
                     <div class="flex items-center pt-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <label for="is_active" class="ml-3 text-xs font-black uppercase tracking-widest text-slate-500">Authorize active deployment</label>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-2" @click="save" :disabled="form.processing">
                    {{ editingId ? 'Update' : 'Create' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
