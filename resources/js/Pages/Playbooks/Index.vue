<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    playbooks: Object,
});

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
                                <tr v-for="playbook in playbooks.data" :key="playbook.id" class="hover:bg-slate-50/50 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="font-black text-slate-900">{{ playbook.name }}</div>
                                        <div class="text-[10px] text-slate-400 font-bold mt-1">Created {{ new Date(playbook.created_at).toLocaleDateString() }}</div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="inline-block px-3 py-1 bg-blue-50 rounded-lg text-xs font-black text-blue-600 border border-blue-100">{{ playbook.model }}</span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-slate-100 text-slate-600 rounded-full text-xs font-black">
                                            {{ playbook.whatsapp_numbers_count }}
                                        </span>
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
