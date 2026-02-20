<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    contactBook: Object,
    contacts: Object,
    allContacts: Array,
});

const selected = ref([]);
const showAddModal = ref(false);
const showImportModal = ref(false);
const showEditModal = ref(false);
const addContactIds = ref([]);

const editForm = useForm({
    name: props.contactBook.name,
    description: props.contactBook.description || '',
});

const importForm = useForm({
    file: null,
    contact_book_id: props.contactBook.id,
});

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

const addContacts = () => {
    if (addContactIds.value.length === 0) return;
    router.post(route('contact-books.add-contacts', props.contactBook.id), {
        contact_ids: addContactIds.value,
    }, {
        onSuccess: () => {
            showAddModal.value = false;
            addContactIds.value = [];
        },
    });
};

const removeSelected = () => {
    if (confirm(`Remove ${selected.value.length} contacts from this book? (Contacts will not be deleted)`)) {
        router.post(route('contact-books.remove-contacts', props.contactBook.id), {
            contact_ids: selected.value,
        }, {
            onSuccess: () => {
                selected.value = [];
            },
        });
    }
};

const deleteAllContacts = () => {
    if (confirm(`Are you sure you want to DELETE all ${props.contactBook.contacts_count} contacts in this book? This will permanently delete the contacts.`)) {
        router.delete(route('contact-books.destroy-all-contacts', props.contactBook.id), {
            onSuccess: () => {
                selected.value = [];
            },
        });
    }
};

const saveEdit = () => {
    editForm.put(route('contact-books.update', props.contactBook.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const handleFileChange = (e) => {
    importForm.file = e.target.files[0];
};

const importContacts = () => {
    importForm.post(route('contacts.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
            importForm.contact_book_id = props.contactBook.id;
        },
    });
};

const openAddModal = () => {
    addContactIds.value = [];
    showAddModal.value = true;
};

const toggleAddContact = (id) => {
    const index = addContactIds.value.indexOf(id);
    if (index > -1) {
        addContactIds.value.splice(index, 1);
    } else {
        addContactIds.value.push(id);
    }
};
</script>

<template>
    <AppLayout :title="contactBook.name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('contact-books.index')" class="flex items-center gap-2 px-3 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Back
                    </Link>
                    <div>
                        <h2 class="font-black text-2xl text-slate-900 leading-tight">{{ contactBook.name }}</h2>
                        <p v-if="contactBook.description" class="text-xs text-slate-400 font-medium">{{ contactBook.description }}</p>
                    </div>
                </div>
                <button @click="showEditModal = true" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition mr-12">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Bulk Actions Toolbar -->
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
                            <button @click="removeSelected" class="flex items-center gap-2 px-4 py-2 bg-orange-500/10 text-orange-400 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-orange-500 hover:text-white transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/></svg>
                                Remove from Book
                            </button>
                        </div>
                    </div>
                </Transition>

                <div class="bg-white overflow-hidden shadow-2xl sm:rounded-[2.5rem] border border-slate-100">
                    <!-- Toolbar -->
                    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-slate-50/30">
                        <div class="flex-1">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full bg-[#f7b538]"></div>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ contactBook.contacts_count }} Contacts in this book</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <button v-if="contactBook.contacts_count > 0" @click="deleteAllContacts" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-red-200 text-red-500 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-red-50 hover:border-red-300 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Delete All
                            </button>
                            <button @click="showImportModal = true" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-slate-100 text-slate-600 rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-50 hover:border-slate-200 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Import CSV
                            </button>
                            <button @click="openAddModal" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 transform hover:scale-105 active:scale-95">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Contacts
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
                                            <input type="checkbox" v-model="isSelectAll" class="size-5 rounded-lg border-2 border-slate-200 text-[#780116] focus:ring-4 focus:ring-[#780116]/10 transition-all cursor-pointer">
                                        </div>
                                    </th>
                                    <th class="px-8 py-5">Name</th>
                                    <th class="px-8 py-5">WhatsApp Number</th>
                                    <th class="px-8 py-5">Added Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="contact in contacts.data" :key="contact.id" class="group transition-all" :class="selected.includes(contact.id) ? 'bg-orange-50/50' : 'hover:bg-slate-50/80'">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center">
                                            <input type="checkbox" :checked="selected.includes(contact.id)" @change="toggleSelection(contact.id)" class="size-5 rounded-lg border-2 border-slate-200 text-[#780116] focus:ring-4 focus:ring-[#780116]/10 transition-all cursor-pointer">
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
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="p-20 text-center">
                            <div class="size-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <h3 class="text-xl font-black text-slate-900">No contacts yet</h3>
                            <p class="text-slate-500 mt-2 max-w-sm mx-auto font-medium">Add existing contacts or import a CSV into this book.</p>
                            <div class="mt-8 flex justify-center gap-4">
                                <button @click="openAddModal" class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl transition hover:scale-105 active:scale-95">Add Contacts</button>
                                <button @click="showImportModal = true" class="px-6 py-3 bg-slate-100 text-slate-900 rounded-2xl font-black text-sm uppercase tracking-widest transition hover:bg-slate-200">Import CSV</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Contacts Modal -->
        <DialogModal :show="showAddModal" @close="showAddModal = false" max-width="lg">
            <template #title>Add Contacts to Book</template>
            <template #content>
                <div class="space-y-4 pt-4">
                    <div v-if="allContacts.length > 0" class="bg-slate-50 rounded-2xl p-4 border border-slate-100 max-h-[400px] overflow-y-auto space-y-2">
                        <div v-for="contact in allContacts" :key="contact.id"
                             class="flex items-center p-3 bg-white rounded-xl border border-slate-100 hover:border-[#780116]/20 transition group cursor-pointer"
                             @click="toggleAddContact(contact.id)">
                            <div class="size-5 rounded border-2 flex items-center justify-center transition-all shrink-0"
                                 :class="addContactIds.includes(contact.id) ? 'bg-[#780116] border-[#780116] shadow-lg shadow-red-100' : 'bg-white border-slate-200 group-hover:border-[#780116]/30'">
                                <svg v-if="addContactIds.includes(contact.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <p class="text-xs font-black text-slate-900 truncate">{{ contact.name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">{{ contact.phone_number }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-sm font-bold text-slate-400">
                        All your contacts are already in this book.
                    </div>
                    <p v-if="addContactIds.length > 0" class="text-xs font-bold text-[#780116]">{{ addContactIds.length }} contacts selected</p>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showAddModal = false">Cancel</SecondaryButton>
                <PrimaryButton class="ml-2 bg-slate-900!" @click="addContacts" :disabled="addContactIds.length === 0">
                    Add {{ addContactIds.length }} Contacts
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Edit Book Modal -->
        <DialogModal :show="showEditModal" @close="showEditModal = false">
            <template #title>Edit Contact Book</template>
            <template #content>
                <div class="space-y-6 pt-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Book Name</label>
                        <input v-model="editForm.name" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all">
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Description (Optional)</label>
                        <input v-model="editForm.description" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all">
                        <InputError :message="editForm.errors.description" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showEditModal = false">Cancel</SecondaryButton>
                <PrimaryButton class="ml-2 bg-slate-900!" @click="saveEdit" :disabled="editForm.processing">Save Changes</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Import CSV Modal -->
        <DialogModal :show="showImportModal" @close="showImportModal = false">
            <template #title>Import Contacts into {{ contactBook.name }}</template>
            <template #content>
                <div class="space-y-6 pt-4">
                    <div class="bg-red-50 border border-red-100 rounded-2xl p-5">
                        <p class="text-xs text-[#780116] font-bold leading-relaxed mb-3">
                            Upload a CSV file with two columns: <span class="text-[#780116] font-black">Name</span> and <span class="text-[#780116] font-black">Phone Number</span>.
                            Contacts will be imported and added to this book.
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
                <SecondaryButton @click="showImportModal = false">Cancel</SecondaryButton>
                <button class="ml-2 px-6 py-2 bg-[#780116] text-white rounded-xl text-sm font-bold hover:bg-[#c32f27] disabled:opacity-50 transition shadow-lg shadow-red-100" @click="importContacts" :disabled="importForm.processing || !importForm.file">
                    {{ importForm.processing ? 'Importing...' : 'Import Now' }}
                </button>
            </template>
        </DialogModal>
    </AppLayout>
</template>
