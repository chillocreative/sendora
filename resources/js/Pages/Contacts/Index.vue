<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    contacts: Object,
    limit: Number,
    count: Number,
});

const showImportModal = ref(false);
const showContactModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const selected = ref([]);

const contactForm = useForm({
    name: '',
    phone_number: '',
});

const importForm = useForm({
    file: null,
});

const bulkDeleteForm = useForm({
    ids: [],
});

const fileInput = ref(null);

// Selection Logic
const isSelectAll = computed({
    get: () => props.contacts.data.length > 0 && selected.value.length === props.contacts.data.length,
    set: (value) => {
        if (value) {
            selected.value = props.contacts.data.map(c => c.id);
        } else {
            selected.value = [];
        }
    }
});

const toggleSelection = (contactId) => {
    const index = selected.value.indexOf(contactId);
    if (index > -1) {
        selected.value.splice(index, 1);
    } else {
        selected.value.push(contactId);
    }
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const importContacts = () => {
    importForm.post(route('contacts.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const openCreateModal = () => {
    isEditing.value = false;
    contactForm.reset();
    showContactModal.value = true;
};

const openEditModal = (contact) => {
    isEditing.value = true;
    editingId.value = contact.id;
    contactForm.name = contact.name;
    contactForm.phone_number = contact.phone_number;
    showContactModal.value = true;
};

const saveContact = () => {
    if (isEditing.value) {
        contactForm.put(route('contacts.update', editingId.value), {
            onSuccess: () => {
                showContactModal.value = false;
                contactForm.reset();
            },
        });
    } else {
        contactForm.post(route('contacts.store'), {
            onSuccess: () => {
                showContactModal.value = false;
                contactForm.reset();
            },
        });
    }
};

const deleteContact = (id) => {
    if (confirm('Are you sure you want to delete this contact?')) {
        router.delete(route('contacts.destroy', id), {
            onSuccess: () => {
                selected.value = selected.value.filter(sid => sid !== id);
            }
        });
    }
};

const bulkDelete = () => {
    if (confirm(`Are you sure you want to delete ${selected.value.length} selected contacts?`)) {
        bulkDeleteForm.ids = selected.value;
        bulkDeleteForm.post(route('contacts.bulk-delete'), {
            onSuccess: () => {
                selected.value = [];
            }
        });
    }
};

const deleteAll = () => {
    if (confirm('DANGER: Are you sure you want to delete ALL contacts? This cannot be undone.')) {
        bulkDeleteForm.ids = props.contacts.data.map(c => c.id); // For simple demo, usually backend clear
        // But let's use the bulk delete with all IDs if user wants delete all visible or all in DB
        // For simplicity, let's just use the selected array which user can use to select all
    }
};

const closeImportModal = () => {
    showImportModal.value = false;
    importForm.reset();
    importForm.clearErrors();
};

const closeContactModal = () => {
    showContactModal.value = false;
    contactForm.reset();
    contactForm.clearErrors();
};
</script>

<template>
    <AppLayout title="Contacts">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Bulk Actions Toolbar (Sticky/Floating) -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-y-4 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-y-0 opacity-100"
                    leave-to-class="translate-y-4 opacity-0"
                >
                    <div v-if="selected.length > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white px-6 py-4 rounded-3xl shadow-2xl flex items-center gap-8 border border-white/10 backdrop-blur-xl">
                        <div class="flex items-center gap-4 border-r border-white/10 pr-8">
                            <span class="text-sm font-black uppercase tracking-widest text-[#f7b538]">{{ selected.length }} Selected</span>
                            <button @click="selected = []" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-white transition">Deselect All</button>
                        </div>
                        <div class="flex items-center gap-4">
                            <button 
                                @click="bulkDelete"
                                :disabled="bulkDeleteForm.processing"
                                class="flex items-center gap-2 px-4 py-2 bg-red-500/10 text-red-400 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-red-500 hover:text-white transition-all disabled:opacity-50"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete Selected
                            </button>
                        </div>
                    </div>
                </Transition>

                <div class="bg-white overflow-hidden shadow-2xl sm:rounded-[2.5rem] border border-slate-100">
                    <!-- Stats / Toolbar -->
                    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-slate-50/30">
                        <div class="flex-1">
                             <h3 class="font-black text-slate-900 text-lg">Your Contacts</h3>
                             <div class="flex flex-wrap items-center gap-4 mt-1">
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-[#f7b538]"></div>
                                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ count }} Active</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ limit }} Limit</span>
                                </div>
                                 <div class="hidden sm:block w-32 h-1.5 bg-slate-200 rounded-full overflow-hidden ml-2">
                                    <div class="h-full bg-[#780116] rounded-full transition-all duration-1000" :style="{ width: (count/limit * 100) + '%' }"></div>
                                </div>
                             </div>
                        </div>
                        
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <button @click="showImportModal = true" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-slate-100 text-slate-600 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-200 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Import
                            </button>
                            <button @click="openCreateModal" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 transform hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add New
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table v-if="contacts.data.length > 0" class="w-full text-left border-collapse">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 uppercase font-black tracking-[0.2em]">
                                    <th class="px-8 py-5 w-10">
                                        <div class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                v-model="isSelectAll"
                                                class="size-5 rounded-lg border-2 border-slate-200 text-[#780116] focus:ring-4 focus:ring-[#780116]/10 transition-all cursor-pointer"
                                            >
                                        </div>
                                    </th>
                                    <th class="px-8 py-5">Name</th>
                                    <th class="px-8 py-5">WhatsApp Number</th>
                                    <th class="px-8 py-5">Added Date</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr 
                                    v-for="contact in contacts.data" 
                                    :key="contact.id" 
                                    class="group transition-all"
                                    :class="selected.includes(contact.id) ? 'bg-red-50/50' : 'hover:bg-slate-50/80'"
                                >
                                    <td class="px-8 py-5">
                                        <div class="flex items-center">
                                            <input 
                                                type="checkbox" 
                                                :checked="selected.includes(contact.id)"
                                                @change="toggleSelection(contact.id)"
                                                class="size-5 rounded-lg border-2 border-slate-200 text-[#780116] focus:ring-4 focus:ring-[#780116]/10 transition-all cursor-pointer"
                                            >
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 font-black text-slate-900">{{ contact.name }}</td>
                                    <td class="px-8 py-5 font-mono text-slate-500">
                                        <div class="flex items-center gap-2">
                                            <div class="size-2 rounded-full bg-[#f7b538]"></div>
                                            {{ contact.phone_number }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-slate-400 font-bold text-xs">{{ new Date(contact.created_at).toLocaleDateString() }}</td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                            <button @click="openEditModal(contact)" class="p-2 bg-white text-slate-600 rounded-lg shadow-sm border border-slate-100 hover:bg-slate-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button @click="deleteContact(contact.id)" class="p-2 bg-white text-red-400 rounded-lg shadow-sm border border-slate-100 hover:bg-red-50 hover:text-red-500 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                         <div v-else class="p-20 text-center">
                            <div class="size-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <h3 class="text-xl font-black text-slate-900">Start your network</h3>
                            <p class="text-slate-500 mt-2 max-w-sm mx-auto font-medium">Add your first contact manually or import a bulk list to start blasting your messages.</p>
                            <div class="mt-8 flex justify-center gap-4">
                                <button @click="openCreateModal" class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl transition hover:scale-105 active:scale-95">Add Manually</button>
                                <button @click="showImportModal = true" class="px-6 py-3 bg-slate-100 text-slate-900 rounded-2xl font-black text-sm uppercase tracking-widest transition hover:bg-slate-200">Import CSV</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form Modal -->
        <DialogModal :show="showContactModal" @close="closeContactModal">
            <template #title>
                {{ isEditing ? 'Edit Contact' : 'New Contact' }}
            </template>
            <template #content>
                <div class="space-y-6 pt-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Full Name</label>
                        <input v-model="contactForm.name" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all" placeholder="e.g. John Doe">
                        <InputError :message="contactForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Phone Number</label>
                        <input v-model="contactForm.phone_number" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all" placeholder="e.g. 60123456789">
                        <p class="text-[10px] text-slate-400 font-medium ml-1">Include country code without (+). e.g. 60 for Malaysia.</p>
                        <InputError :message="contactForm.errors.phone_number" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeContactModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-2 bg-slate-900!" @click="saveContact" :disabled="contactForm.processing">
                    {{ isEditing ? 'Save Changes' : 'Create Contact' }}
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Import Modal -->
        <DialogModal :show="showImportModal" @close="closeImportModal">
            <template #title>
                Import Contacts
            </template>
            <template #content>
                <div class="space-y-6 pt-4">
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-5">
                        <p class="text-xs text-[#780116] font-bold leading-relaxed mb-3">
                            Upload a CSV file with two columns: <span class="text-[#780116] font-black">Name</span> and <span class="text-[#780116] font-black">Phone Number</span>. 
                            Duplicate numbers will be ignored.
                        </p>
                        <a href="/samples/contacts_sample.csv" download class="inline-flex items-center gap-2 text-[#db7c26] hover:text-[#780116] text-xs font-black uppercase tracking-widest transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Sample CSV
                        </a>
                    </div>
                    
                    <div class="border-2 border-dashed border-slate-100 rounded-3xl p-10 text-center hover:bg-slate-50 transition-all cursor-pointer group" @click="$refs.fileInput.click()">
                        <input type="file" ref="fileInput" class="hidden" accept=".csv,.txt,.xlsx" @change="handleFileChange">
                        <div v-if="importForm.file" class="flex flex-col items-center">
                            <div class="size-16 bg-[#780116] text-white rounded-2xl flex items-center justify-center shadow-lg mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-sm font-black text-slate-900">{{ importForm.file.name }}</span>
                            <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">Ready to import</span>
                        </div>
                        <div v-else class="text-slate-300 group-hover:text-slate-400 transition-colors">
                            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="block text-sm font-black uppercase tracking-widest">Select CSV File</span>
                        </div>
                    </div>
                    <InputError :message="importForm.errors.file" />
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeImportModal">Cancel</SecondaryButton>
                <button class="ml-2 px-6 py-2 bg-[#780116] text-white rounded-xl text-sm font-bold hover:bg-[#c32f27] disabled:opacity-50 transition shadow-lg shadow-red-100" @click="importContacts" :disabled="importForm.processing || !importForm.file">
                    {{ importForm.processing ? 'Importing...' : 'Import Now' }}
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
