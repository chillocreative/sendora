<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    contactBooks: Array,
});

const showCreateModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    name: '',
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showCreateModal.value = true;
};

const openEditModal = (book) => {
    isEditing.value = true;
    editingId.value = book.id;
    form.name = book.name;
    form.description = book.description || '';
    form.clearErrors();
    showCreateModal.value = true;
};

const saveBook = () => {
    if (isEditing.value) {
        form.put(route('contact-books.update', editingId.value), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route('contact-books.store'), {
            onSuccess: () => {
                showCreateModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteBook = (id) => {
    if (confirm('Are you sure you want to delete this contact book? Contacts will not be deleted.')) {
        router.delete(route('contact-books.destroy', id));
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <AppLayout title="Contact Books">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-2xl sm:rounded-[2.5rem] border border-slate-100">
                    <!-- Header -->
                    <div class="p-8 border-b border-slate-50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-slate-50/30">
                        <div class="flex-1">
                            <h3 class="font-black text-slate-900 text-lg">Contact Books</h3>
                            <div class="flex items-center gap-1.5 mt-1">
                                <div class="w-2 h-2 rounded-full bg-[#f7b538]"></div>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">{{ contactBooks.length }} Books</span>
                            </div>
                        </div>
                        <button @click="openCreateModal" class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 text-white rounded-2xl text-sm font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 transform hover:scale-105 active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            New Book
                        </button>
                    </div>

                    <!-- Books Grid -->
                    <div v-if="contactBooks.length > 0" class="p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="book in contactBooks" :key="book.id" class="group relative bg-white border-2 border-slate-100 rounded-3xl p-6 hover:border-[#780116]/20 hover:shadow-xl transition-all duration-300">
                                <!-- Actions -->
                                <div class="absolute top-4 right-4 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-all">
                                    <button @click.stop="openEditModal(book)" class="p-2 bg-white text-slate-600 rounded-lg shadow-sm border border-slate-100 hover:bg-slate-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click.stop="deleteBook(book.id)" class="p-2 bg-white text-red-400 rounded-lg shadow-sm border border-slate-100 hover:bg-red-50 hover:text-red-500 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                <Link :href="route('contact-books.show', book.id)" class="block">
                                    <!-- Icon -->
                                    <div class="size-14 bg-slate-50 rounded-2xl flex items-center justify-center mb-4 group-hover:bg-[#780116]/5 transition-colors">
                                        <svg class="w-7 h-7 text-slate-300 group-hover:text-[#780116] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>

                                    <!-- Info -->
                                    <h4 class="font-black text-slate-900 text-base truncate">{{ book.name }}</h4>
                                    <p v-if="book.description" class="text-xs text-slate-400 font-medium mt-1 line-clamp-2">{{ book.description }}</p>

                                    <!-- Contact Count -->
                                    <div class="mt-4 flex items-center gap-2">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 rounded-full">
                                            <svg class="w-3.5 h-3.5 text-[#f7b538]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ book.contacts_count }} Contacts</span>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="p-20 text-center">
                        <div class="size-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900">Organize your contacts</h3>
                        <p class="text-slate-500 mt-2 max-w-sm mx-auto font-medium">Create contact books to group your contacts for easy campaign targeting.</p>
                        <div class="mt-8">
                            <button @click="openCreateModal" class="px-6 py-3 bg-slate-900 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl transition hover:scale-105 active:scale-95">Create First Book</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="showCreateModal" @close="closeModal">
            <template #title>
                {{ isEditing ? 'Edit Contact Book' : 'New Contact Book' }}
            </template>
            <template #content>
                <div class="space-y-6 pt-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Book Name</label>
                        <input v-model="form.name" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all" placeholder="e.g. Clients KL">
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Description (Optional)</label>
                        <input v-model="form.description" type="text" class="w-full px-5 py-4 bg-slate-50 border-slate-100 focus:bg-white focus:border-slate-900 focus:ring-4 focus:ring-slate-900/5 rounded-2xl text-sm font-bold transition-all" placeholder="e.g. Kuala Lumpur based clients">
                        <InputError :message="form.errors.description" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-2 bg-slate-900!" @click="saveBook" :disabled="form.processing">
                    {{ isEditing ? 'Save Changes' : 'Create Book' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
