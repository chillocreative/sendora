<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, computed } from 'vue';
import { useMarkdownPreview } from '@/Composables/useMarkdownPreview';

const props = defineProps({
    defaultTemplate: String,
    whatsappNumbers: Array,
});

const form = useForm({
    name: '',
    content: props.defaultTemplate || '',
    model: 'gpt-4o',
    temperature: 0.7,
    max_tokens: 500,
});

const activeTab = ref('edit');
const fileInput = ref(null);
const fileError = ref(null);

const contentRef = computed(() => form.content);
const { renderedHtml, parseWarnings } = useMarkdownPreview(contentRef);

const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const ext = file.name.split('.').pop().toLowerCase();
    if (ext !== 'md') {
        fileError.value = 'Only .md files are accepted.';
        event.target.value = '';
        return;
    }
    if (file.size > 512 * 1024) {
        fileError.value = 'File must be under 512KB.';
        event.target.value = '';
        return;
    }

    fileError.value = null;
    const reader = new FileReader();
    reader.onload = (e) => {
        form.content = e.target.result;
        activeTab.value = 'preview';
    };
    reader.readAsText(file);
    event.target.value = '';
};

const submit = () => {
    form.post(route('playbooks.store'));
};
</script>

<template>
    <AppLayout title="Create Playbook">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('playbooks.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                    Create Playbook
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Playbook Name -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight">Persona Identity</h3>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Playbook Name</label>
                                <input v-model="form.name" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none" placeholder="e.g. Sales Agent, Support Bot, Lead Qualifier">
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                        </div>

                        <!-- Playbook Content -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight">Playbook Content</h3>

                            <!-- File Upload -->
                            <div class="mb-6">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Import from File</label>
                                <div class="flex items-center gap-4">
                                    <input ref="fileInput" type="file" accept=".md" class="hidden" @change="handleFileUpload">
                                    <button type="button" @click="fileInput.click()" class="inline-flex items-center gap-2 px-5 py-3 bg-slate-50 border border-slate-100 text-slate-600 font-black text-[10px] uppercase tracking-widest rounded-2xl hover:bg-slate-100 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        Upload .md File
                                    </button>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">or type directly below</span>
                                </div>
                                <p v-if="fileError" class="text-[11px] font-bold text-red-500 mt-2 ml-1">{{ fileError }}</p>
                                <p class="text-[9px] text-slate-300 font-bold mt-2 ml-1 uppercase tracking-wider">Max 512KB, .md files only</p>
                                <a href="/samples/sendora-sample-persona.md" download class="inline-flex items-center gap-2 mt-3 ml-1 text-[10px] font-black text-blue-600 hover:text-blue-800 uppercase tracking-widest transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Download Sample Playbook
                                </a>
                            </div>

                            <!-- Tab Switcher -->
                            <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-4 w-fit">
                                <button type="button" @click="activeTab = 'edit'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all" :class="activeTab === 'edit' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'">
                                    Edit
                                </button>
                                <button type="button" @click="activeTab = 'preview'" class="px-5 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all" :class="activeTab === 'preview' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'">
                                    Preview
                                </button>
                            </div>

                            <!-- Edit Tab -->
                            <div v-show="activeTab === 'edit'">
                                <textarea v-model="form.content" rows="24" class="w-full px-5 py-4 bg-slate-50 border-slate-100 rounded-2xl font-mono text-sm focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none leading-relaxed" placeholder="Define your AI persona..."></textarea>
                            </div>

                            <!-- Preview Tab -->
                            <div v-show="activeTab === 'preview'">
                                <div v-if="renderedHtml" class="px-6 py-5 bg-slate-50 border border-slate-100 rounded-2xl prose prose-slate max-w-none prose-sm min-h-[24rem]" v-html="renderedHtml"></div>
                                <div v-else class="px-6 py-12 bg-slate-50 border border-slate-100 rounded-2xl text-center min-h-[24rem] flex items-center justify-center">
                                    <p class="text-slate-400 text-sm font-bold">Nothing to preview. Start writing or upload a .md file.</p>
                                </div>
                            </div>

                            <InputError :message="form.errors.content" class="mt-2" />

                            <!-- Warnings -->
                            <div v-if="parseWarnings.length > 0" class="mt-3 space-y-1">
                                <div v-for="(warn, i) in parseWarnings" :key="i" class="flex items-start gap-2 px-4 py-2 bg-yellow-50 border border-yellow-100 rounded-xl">
                                    <svg class="w-3.5 h-3.5 text-yellow-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    <span class="text-[10px] font-bold text-yellow-700">{{ warn }}</span>
                                </div>
                            </div>

                            <p class="text-[10px] text-slate-400 font-bold mt-3 ml-1 uppercase tracking-wider">
                                Use markdown sections: # Persona, # Tone & Style, # Knowledge Base, # Goals, # Escalation Rules, # Forbidden Actions
                            </p>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-28 space-y-6">
                            <!-- How This Works -->
                            <div class="p-6 bg-slate-900 rounded-[2rem] text-white/90 shadow-xl">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="px-2 py-1 bg-blue-500 text-white text-[8px] font-black uppercase tracking-widest rounded">How It Works</div>
                                </div>
                                <p class="text-[11px] font-bold leading-relaxed opacity-90">
                                    This playbook is the persona your AI uses to reply on WhatsApp. Every heading, rule, and detail you write here directly shapes how the AI responds to customers.
                                </p>
                                <p class="text-[10px] font-bold leading-relaxed opacity-60 mt-3">
                                    The more specific your playbook, the more accurate and on-brand your AI replies will be. Include product details, FAQs, tone guidance, and escalation rules.
                                </p>
                            </div>

                            <!-- AI Settings -->
                            <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">AI Configuration</h3>

                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Model</label>
                                        <select v-model="form.model" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                            <option value="gpt-4o">GPT-4o (Recommended)</option>
                                            <option value="gpt-4o-mini">GPT-4o Mini (Faster)</option>
                                            <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Budget)</option>
                                        </select>
                                        <InputError :message="form.errors.model" class="mt-2" />
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Temperature: {{ form.temperature }}</label>
                                        <input v-model.number="form.temperature" type="range" min="0" max="2" step="0.1" class="w-full accent-[#780116]">
                                        <div class="flex justify-between text-[9px] font-bold text-slate-300 mt-1">
                                            <span>Precise</span>
                                            <span>Creative</span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Max Tokens</label>
                                        <input v-model.number="form.max_tokens" type="number" min="100" max="2000" step="50" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                        <InputError :message="form.errors.max_tokens" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Submit -->
                            <div class="bg-white shadow-2xl sm:rounded-[2rem] p-8 border border-slate-100">
                                <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[#780116] text-white font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-[#c32f27] shadow-xl shadow-red-200 transition-all disabled:opacity-50 transform hover:scale-105 active:scale-95">
                                    {{ form.processing ? 'Creating...' : 'Create Playbook' }}
                                </button>
                                <Link :href="route('playbooks.index')" class="block w-full py-4 mt-3 bg-white border border-slate-100 text-slate-400 font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-slate-50 transition-all text-center">
                                    Cancel
                                </Link>
                            </div>

                            <!-- Tip -->
                            <div class="p-6 bg-slate-50 rounded-[2rem] border border-slate-100">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="px-2 py-1 bg-[#f7b538] text-slate-900 text-[8px] font-black uppercase tracking-widest rounded">Tip</div>
                                </div>
                                <p class="text-[10px] font-bold leading-relaxed text-slate-500 uppercase tracking-wider">After creating, assign this playbook to a WhatsApp number from the edit page to activate AI replies.</p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
