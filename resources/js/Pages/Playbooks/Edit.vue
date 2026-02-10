<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import { ref, computed } from 'vue';
import { useMarkdownPreview } from '@/Composables/useMarkdownPreview';
import axios from 'axios';

const props = defineProps({
    playbook: Object,
    versionCount: Number,
    whatsappNumbers: Array,
});

const form = useForm({
    name: props.playbook.name,
    content: props.playbook.content,
    model: props.playbook.model || 'gpt-4o',
    temperature: parseFloat(props.playbook.temperature) || 0.7,
    max_tokens: props.playbook.max_tokens || 500,
    is_active: props.playbook.is_active,
});

const activeTab = ref('edit');
const fileInput = ref(null);
const fileError = ref(null);

// Markdown preview
const contentRef = computed(() => form.content);
const { renderedHtml, parseWarnings } = useMarkdownPreview(contentRef);

// Version history
const showVersionModal = ref(false);
const versions = ref([]);
const loadingVersions = ref(false);
const selectedVersion = ref(null);
const selectedVersionHtml = ref('');
const loadingVersion = ref(false);

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
    form.put(route('playbooks.update', props.playbook.id));
};

const assignToNumber = (number) => {
    const isCurrentlyAssigned = number.playbook_id === props.playbook.id;
    router.post(route('playbooks.assign'), {
        whatsapp_number_id: number.id,
        playbook_id: isCurrentlyAssigned ? null : props.playbook.id,
        ai_reply_enabled: !isCurrentlyAssigned,
    }, {
        preserveScroll: true,
    });
};

const toggleAiReply = (number) => {
    if (number.playbook_id !== props.playbook.id) return;
    router.post(route('playbooks.assign'), {
        whatsapp_number_id: number.id,
        playbook_id: props.playbook.id,
        ai_reply_enabled: !number.ai_reply_enabled,
    }, {
        preserveScroll: true,
    });
};

// Version history methods
const openVersionHistory = async () => {
    showVersionModal.value = true;
    selectedVersion.value = null;
    selectedVersionHtml.value = '';
    loadingVersions.value = true;
    try {
        const res = await axios.get(route('playbooks.versions', props.playbook.id));
        versions.value = res.data.versions;
    } catch {
        versions.value = [];
    } finally {
        loadingVersions.value = false;
    }
};

const viewVersion = async (version) => {
    if (selectedVersion.value?.id === version.id) {
        selectedVersion.value = null;
        selectedVersionHtml.value = '';
        return;
    }
    loadingVersion.value = true;
    try {
        const res = await axios.get(route('playbooks.version.show', { id: props.playbook.id, versionId: version.id }));
        selectedVersion.value = res.data.version;
        // Render preview using marked + DOMPurify
        const { marked } = await import('marked');
        const DOMPurify = (await import('dompurify')).default;
        const rawHtml = marked.parse(res.data.version.content || '');
        selectedVersionHtml.value = DOMPurify.sanitize(rawHtml, {
            ALLOWED_TAGS: ['h1','h2','h3','h4','h5','h6','p','br','hr','ul','ol','li','strong','em','code','pre','blockquote','a','table','thead','tbody','tr','th','td','del','sup','sub','span'],
            ALLOWED_ATTR: ['href','title'],
        });
    } catch {
        selectedVersion.value = null;
        selectedVersionHtml.value = '';
    } finally {
        loadingVersion.value = false;
    }
};

const restoreVersion = (version) => {
    if (!confirm(`Restore to version ${version.version_number}? This will replace the current playbook content.`)) return;
    router.post(route('playbooks.version.restore', { id: props.playbook.id, versionId: version.id }), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showVersionModal.value = false;
        },
    });
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <AppLayout title="Edit Playbook">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('playbooks.index')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">
                    Edit: {{ playbook.name }}
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
                                <input v-model="form.name" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none" placeholder="e.g. Sales Agent, Support Bot">
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
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">or edit directly below</span>
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

                        <!-- Assign to WhatsApp Numbers -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight">Assign to WhatsApp Numbers</h3>
                            <div v-if="whatsappNumbers.length > 0" class="space-y-4">
                                <div v-for="number in whatsappNumbers" :key="number.id" class="flex items-center justify-between p-5 rounded-2xl border transition-all" :class="number.playbook_id === playbook.id ? 'bg-green-50/50 border-green-100' : 'bg-slate-50 border-slate-100'">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="number.status === 'connected' ? 'bg-green-100 text-green-600' : 'bg-slate-200 text-slate-400'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        </div>
                                        <div>
                                            <div class="font-black text-slate-900 text-sm">{{ number.phone_number || 'Device #' + number.id }}</div>
                                            <div class="text-[10px] font-bold uppercase tracking-widest" :class="number.status === 'connected' ? 'text-green-500' : 'text-slate-400'">{{ number.status }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <button v-if="number.playbook_id === playbook.id" type="button" @click="toggleAiReply(number)" class="inline-flex items-center px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest transition-all" :class="number.ai_reply_enabled ? 'bg-green-100 text-green-600 border border-green-200' : 'bg-slate-100 text-slate-400'">
                                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="number.ai_reply_enabled ? 'bg-green-400 animate-pulse' : 'bg-slate-300'"></span>
                                            {{ number.ai_reply_enabled ? 'AI On' : 'AI Off' }}
                                        </button>
                                        <button type="button" @click="assignToNumber(number)" class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all" :class="number.playbook_id === playbook.id ? 'bg-red-50 text-red-500 hover:bg-red-100 border border-red-100' : 'bg-[#780116] text-white hover:bg-[#c32f27] shadow-lg shadow-red-200'">
                                            {{ number.playbook_id === playbook.id ? 'Unassign' : 'Assign' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <p class="text-slate-400 text-sm font-bold">No WhatsApp numbers connected. Connect a number first.</p>
                            </div>
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
                                    </div>
                                </div>
                            </div>

                            <!-- Version History -->
                            <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Version History</h3>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="inline-flex items-center justify-center min-w-[28px] h-7 px-2 bg-purple-50 text-purple-600 rounded-full text-xs font-black border border-purple-100">
                                        {{ versionCount || 0 }} versions
                                    </span>
                                </div>
                                <button type="button" @click="openVersionHistory" class="w-full py-3 bg-slate-50 text-slate-600 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-100 border border-slate-100 transition-all">
                                    View History
                                </button>
                            </div>

                            <!-- Submit -->
                            <div class="bg-white shadow-2xl sm:rounded-[2rem] p-8 border border-slate-100">
                                <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[#780116] text-white font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-[#c32f27] shadow-xl shadow-red-200 transition-all disabled:opacity-50 transform hover:scale-105 active:scale-95">
                                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                                </button>
                                <div v-if="form.recentlySuccessful" class="mt-4 p-3 bg-green-50 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-2xl text-center border border-green-100">
                                    Playbook Updated
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Version History Modal -->
        <Teleport to="body">
            <div v-if="showVersionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" @click="showVersionModal = false"></div>
                <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl max-h-[80vh] overflow-hidden flex flex-col">
                    <!-- Modal Header -->
                    <div class="px-8 py-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Version History</h3>
                        <button @click="showVersionModal = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="flex-1 overflow-y-auto p-8">
                        <div v-if="loadingVersions" class="text-center py-8">
                            <p class="text-slate-400 text-sm font-bold">Loading versions...</p>
                        </div>
                        <div v-else-if="versions.length === 0" class="text-center py-8">
                            <p class="text-slate-400 text-sm font-bold">No version history available.</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="ver in versions" :key="ver.id" class="border rounded-2xl transition-all overflow-hidden" :class="selectedVersion?.id === ver.id ? 'border-purple-200 bg-purple-50/30' : 'border-slate-100'">
                                <div class="p-5 flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 bg-purple-50 text-purple-600 rounded-full text-[10px] font-black border border-purple-100">v{{ ver.version_number }}</span>
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ ver.source }}</span>
                                        </div>
                                        <p class="text-xs font-bold text-slate-600 mt-1">{{ ver.change_summary || 'No description' }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ formatDate(ver.created_at) }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="viewVersion(ver)" class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all" :class="selectedVersion?.id === ver.id ? 'bg-purple-100 text-purple-600' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                                            {{ selectedVersion?.id === ver.id ? 'Hide' : 'View' }}
                                        </button>
                                        <button v-if="ver.version_number !== versions[0]?.version_number" type="button" @click="restoreVersion(ver)" class="px-3 py-1.5 bg-[#780116] text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-[#c32f27] transition-all">
                                            Restore
                                        </button>
                                    </div>
                                </div>
                                <!-- Version Content Preview -->
                                <div v-if="selectedVersion?.id === ver.id" class="border-t border-purple-100 p-5">
                                    <div v-if="loadingVersion" class="text-center py-4">
                                        <p class="text-slate-400 text-xs font-bold">Loading content...</p>
                                    </div>
                                    <div v-else-if="selectedVersionHtml" class="prose prose-slate prose-sm max-w-none max-h-64 overflow-y-auto px-4 py-3 bg-white rounded-xl border border-slate-100" v-html="selectedVersionHtml"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
