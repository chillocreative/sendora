<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    playbooks: Object,
    whatsappNumbers: Array,
});

const expandedPlaybook = ref(null);

const toggleExpand = (id) => {
    expandedPlaybook.value = expandedPlaybook.value === id ? null : id;
};

const deletePlaybook = (id) => {
    if (confirm('Delete this playbook? It will be detached from any assigned WhatsApp numbers.')) {
        router.delete(route('playbooks.destroy', id));
    }
};

const toggleActive = (playbook) => {
    router.put(route('playbooks.update', playbook.id), {
        name: playbook.name,
        content: playbook.content,
        model: playbook.model,
        temperature: playbook.temperature,
        max_tokens: playbook.max_tokens,
        is_active: !playbook.is_active,
    }, {
        preserveScroll: true,
    });
};

const assignToNumber = (number, playbookId) => {
    const isCurrentlyAssigned = number.playbook_id === playbookId;
    router.post(route('playbooks.assign'), {
        whatsapp_number_id: number.id,
        playbook_id: isCurrentlyAssigned ? null : playbookId,
        ai_reply_enabled: !isCurrentlyAssigned,
    }, {
        preserveScroll: true,
    });
};

const toggleAiReply = (number, playbookId) => {
    if (number.playbook_id !== playbookId) return;
    router.post(route('playbooks.assign'), {
        whatsapp_number_id: number.id,
        playbook_id: playbookId,
        ai_reply_enabled: !number.ai_reply_enabled,
    }, {
        preserveScroll: true,
    });
};

const getAssignedNumbers = (playbookId) => {
    return props.whatsappNumbers.filter(n => n.playbook_id === playbookId);
};
</script>

<template>
    <AppLayout title="AI Playbooks">
        <template #header>
            <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                AI Playbooks
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Toolbar -->
                    <div class="p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-center bg-white gap-6">
                        <div class="text-center sm:text-left">
                            <h3 class="font-black text-slate-900 text-xl tracking-tight leading-none mb-2">Persona Library</h3>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Define AI personas that respond to your customers on WhatsApp</p>
                        </div>
                        <div class="flex space-x-3 w-full sm:w-auto">
                            <Link :href="route('playbooks.create')" class="w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-4 bg-[#780116] text-white rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-xl shadow-red-200 transform active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                New Playbook
                            </Link>
                        </div>
                    </div>

                    <!-- List -->
                    <div class="overflow-x-auto">
                        <table v-if="playbooks.data.length > 0" class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase tracking-[0.2em]">
                                    <th class="px-8 py-5 font-black">Playbook Name</th>
                                    <th class="px-8 py-5 font-black">Model</th>
                                    <th class="px-8 py-5 font-black text-center">Assigned Numbers</th>
                                    <th class="px-8 py-5 font-black text-center">Versions</th>
                                    <th class="px-8 py-5 font-black text-center">Status</th>
                                    <th class="px-8 py-5 font-black text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <template v-for="playbook in playbooks.data" :key="playbook.id">
                                    <tr class="hover:bg-slate-50/50 transition-all group">
                                        <td class="px-8 py-6">
                                            <div class="font-black text-slate-900">{{ playbook.name }}</div>
                                            <div class="text-[10px] text-slate-400 font-bold mt-1">Created {{ new Date(playbook.created_at).toLocaleDateString() }}</div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <span class="inline-block px-3 py-1 bg-blue-50 rounded-lg text-xs font-black text-blue-600 border border-blue-100">{{ playbook.model }}</span>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <button @click="toggleExpand(playbook.id)"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-black transition-all"
                                                :class="expandedPlaybook === playbook.id ? 'bg-[#780116] text-white shadow-lg shadow-red-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                {{ playbook.whatsapp_numbers_count }}
                                                <svg class="w-3 h-3 transition-transform" :class="expandedPlaybook === playbook.id ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-purple-50 text-purple-600 rounded-full text-xs font-black border border-purple-100">
                                                {{ playbook.versions_count || 0 }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-center">
                                            <button @click="toggleActive(playbook)"
                                                class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all"
                                                :class="playbook.is_active ? 'bg-green-50 text-green-600 hover:bg-green-100 border border-green-100' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'">
                                                <span class="w-2 h-2 rounded-full mr-2" :class="playbook.is_active ? 'bg-green-400 animate-pulse' : 'bg-slate-300'"></span>
                                                {{ playbook.is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-8 py-6 text-right space-x-3">
                                            <Link :href="route('playbooks.edit', playbook.id)" class="inline-block p-2 text-slate-300 hover:text-[#780116] hover:bg-red-50 rounded-xl transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </Link>
                                            <button @click="deletePlaybook(playbook.id)" class="p-2 text-slate-300 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Expandable assign panel -->
                                    <tr v-if="expandedPlaybook === playbook.id">
                                        <td colspan="6" class="px-8 py-6 bg-slate-50/80">
                                            <div class="max-w-2xl">
                                                <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Assign WhatsApp Numbers</h4>
                                                <div v-if="whatsappNumbers.length === 0" class="text-sm text-slate-400">
                                                    No WhatsApp numbers connected. Go to WhatsApp Manager to connect one.
                                                </div>
                                                <div v-else class="space-y-3">
                                                    <div v-for="number in whatsappNumbers" :key="number.id"
                                                        class="flex items-center justify-between p-4 rounded-xl border transition-all"
                                                        :class="number.playbook_id === playbook.id ? 'bg-white border-green-200 shadow-sm' : (number.playbook_id && number.playbook_id !== playbook.id) ? 'bg-slate-50 border-slate-100 opacity-50' : 'bg-white border-slate-200 hover:border-slate-300'">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-full flex items-center justify-center"
                                                                :class="number.status === 'connected' ? 'bg-green-100' : 'bg-slate-100'">
                                                                <svg class="w-5 h-5" :class="number.status === 'connected' ? 'text-green-600' : 'text-slate-400'" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.29-1.243l-.307-.184-2.866.852.852-2.866-.184-.307A8 8 0 1112 20z"/></svg>
                                                            </div>
                                                            <div>
                                                                <div class="text-sm font-bold text-slate-800">{{ number.phone_number }}</div>
                                                                <div class="text-[10px] font-bold uppercase tracking-widest"
                                                                    :class="number.status === 'connected' ? 'text-green-500' : 'text-slate-400'">
                                                                    {{ number.status }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <!-- AI On/Off toggle for assigned numbers -->
                                                            <button v-if="number.playbook_id === playbook.id"
                                                                @click="toggleAiReply(number, playbook.id)"
                                                                class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all"
                                                                :class="number.ai_reply_enabled ? 'bg-green-100 text-green-600 border border-green-200' : 'bg-slate-100 text-slate-400 border border-slate-200'">
                                                                {{ number.ai_reply_enabled ? 'AI ON' : 'AI OFF' }}
                                                            </button>
                                                            <!-- Assign/Unassign button -->
                                                            <button v-if="!number.playbook_id || number.playbook_id === playbook.id"
                                                                @click="assignToNumber(number, playbook.id)"
                                                                class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                                                                :class="number.playbook_id === playbook.id ? 'bg-red-50 text-red-500 hover:bg-red-100 border border-red-100' : 'bg-[#780116] text-white hover:bg-[#c32f27] shadow-lg shadow-red-200'">
                                                                {{ number.playbook_id === playbook.id ? 'Unassign' : 'Assign' }}
                                                            </button>
                                                            <!-- Already assigned to another playbook -->
                                                            <span v-else class="px-3 py-1.5 rounded-lg text-[10px] font-bold text-slate-400 bg-slate-50 border border-slate-100">
                                                                Assigned elsewhere
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div v-else class="p-12 text-center">
                            <div class="inline-flex p-4 rounded-full bg-slate-100 text-slate-400 mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">No playbooks yet</h3>
                            <p class="text-slate-500 mt-1">Create your first AI playbook to start automated conversations.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
