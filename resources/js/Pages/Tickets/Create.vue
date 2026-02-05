<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    subject: '',
    description: '',
    priority: 'medium',
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

    // Reset input so same file can be re-selected
    e.target.value = '';
};

const removeFile = (index) => {
    form.attachments.splice(index, 1);
    previewFiles.value.splice(index, 1);
};

const submit = () => {
    form.post(route('tickets.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <AppLayout title="New Ticket">
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <Link :href="route('tickets.index')" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Create New Ticket</h1>
                    <p class="text-sm text-slate-500 mt-1">Describe your issue and we'll get back to you</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-2xl border border-slate-200 p-8 space-y-6">
                <!-- Subject -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Subject</label>
                    <input
                        v-model="form.subject"
                        type="text"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#780116]/20 focus:border-[#780116] transition"
                        placeholder="Brief summary of your issue..."
                    />
                    <p v-if="form.errors.subject" class="text-red-500 text-xs mt-1">{{ form.errors.subject }}</p>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Priority</label>
                    <div class="flex gap-3">
                        <button
                            v-for="p in ['low', 'medium', 'high', 'urgent']"
                            :key="p"
                            type="button"
                            @click="form.priority = p"
                            class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider border-2 transition"
                            :class="form.priority === p
                                ? 'border-[#780116] bg-[#780116] text-white'
                                : 'border-slate-200 text-slate-500 hover:border-slate-300'"
                        >
                            {{ p }}
                        </button>
                    </div>
                    <p v-if="form.errors.priority" class="text-red-500 text-xs mt-1">{{ form.errors.priority }}</p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Description</label>
                    <textarea
                        v-model="form.description"
                        rows="6"
                        class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#780116]/20 focus:border-[#780116] transition resize-none"
                        placeholder="Describe your issue in detail..."
                    ></textarea>
                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</p>
                </div>

                <!-- Attachments -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Attachments <span class="text-slate-400 font-normal">(optional, max 5 files)</span></label>
                    <div
                        @click="fileInput?.click()"
                        class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center cursor-pointer hover:border-[#780116]/30 hover:bg-slate-50 transition"
                    >
                        <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="text-sm text-slate-500">Click to upload images or documents</p>
                        <p class="text-xs text-slate-400 mt-1">JPG, PNG, GIF, PDF up to 5MB each</p>
                    </div>
                    <input ref="fileInput" type="file" class="hidden" multiple accept=".jpg,.jpeg,.png,.gif,.pdf" @change="handleFiles" />
                    <p v-if="form.errors.attachments" class="text-red-500 text-xs mt-1">{{ form.errors.attachments }}</p>

                    <!-- Preview -->
                    <div v-if="previewFiles.length > 0" class="flex flex-wrap gap-3 mt-4">
                        <div v-for="(file, index) in previewFiles" :key="index" class="relative group">
                            <div v-if="file.type === 'image'" class="w-20 h-20 rounded-xl overflow-hidden border border-slate-200">
                                <img :src="file.url" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-20 h-20 rounded-xl border border-slate-200 flex items-center justify-center bg-slate-50">
                                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <button
                                type="button"
                                @click="removeFile(index)"
                                class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
                            >
                                &times;
                            </button>
                            <p class="text-[10px] text-slate-400 mt-1 truncate w-20">{{ file.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-4 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="bg-[#780116] text-white px-8 py-3 rounded-xl text-sm font-bold hover:bg-[#c32f27] transition shadow-lg shadow-red-100 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Submitting...</span>
                        <span v-else>Submit Ticket</span>
                    </button>
                    <Link :href="route('tickets.index')" class="text-sm font-bold text-slate-500 hover:text-slate-700 transition">
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
