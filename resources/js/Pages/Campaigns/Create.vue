<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    contacts: Array,
    whatsappNumbers: Array,
    contactBooks: Array,
    campaign: Object,
    selectedContactIds: Array,
    isEditing: Boolean,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const hasLinkPreview = computed(() => {
    return user.value?.active_subscription?.plan?.limits?.features?.link_preview ?? false;
});

const form = useForm({
    name: props.campaign?.name || '',
    whatsapp_number_id: props.campaign?.whatsapp_number_id || (props.whatsappNumbers.length > 0 ? props.whatsappNumbers[0].id : ''),
    body: props.campaign?.body || '',
    media: null,
    scheduled_at: props.campaign?.scheduled_at ? props.campaign.scheduled_at.slice(0, 16) : '',
    contact_ids: props.selectedContactIds || [],
    contact_book_ids: [],
    is_drip: props.campaign?.is_drip || false,
    drip_delay_minutes: props.campaign?.drip_delay_minutes || 1,
});

const showIndividualContacts = ref(false);

const estimatedRecipients = computed(() => {
    let count = form.contact_ids.length;
    if (props.contactBooks) {
        props.contactBooks.forEach(book => {
            if (form.contact_book_ids.includes(book.id)) {
                count += book.contacts_count;
            }
        });
    }
    return count;
});

const toggleBook = (bookId) => {
    const index = form.contact_book_ids.indexOf(bookId);
    if (index > -1) {
        form.contact_book_ids.splice(index, 1);
    } else {
        form.contact_book_ids.push(bookId);
    }
};

// Link Preview Logic
const detectedLink = computed(() => {
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const matches = form.body.match(urlRegex);
    return matches ? matches[0] : null;
});

const getDomain = (url) => {
    try {
        return new URL(url).hostname;
    } catch (e) {
        return '';
    }
};

const toggleSelectAll = () => {
    if (form.contact_ids.length === props.contacts.length) {
        form.contact_ids = [];
    } else {
        form.contact_ids = props.contacts.map(c => c.id);
    }
};

const submit = () => {
    if (props.isEditing) {
        form.post(route('campaigns.update', props.campaign.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('campaigns.store'), {
            forceFormData: true,
        });
    }
};
</script>

<template>
    <AppLayout :title="isEditing ? 'Edit Campaign' : 'Create Campaign'">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-black text-2xl text-slate-900 leading-tight">
                    {{ isEditing ? 'Edit Campaign' : 'New Campaign' }}
                </h2>
                <Link :href="route('campaigns.index')" class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-200 transition mr-12">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to List
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    
                    <!-- Left: Form -->
                    <div class="xl:col-span-8 bg-white overflow-hidden shadow-2xl sm:rounded-[3rem] border border-slate-100">
                        <form @submit.prevent="submit" class="p-8 lg:p-12 space-y-12">
                            <div class="space-y-10">
                                <!-- Campaign Title -->
                                <div>
                                    <InputLabel for="name" value="Campaign Title" class="text-xs uppercase tracking-widest font-black text-slate-400 mb-3" />
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="w-full px-8 py-5 bg-slate-50 border-slate-100 focus:bg-white focus:border-[#780116] focus:ring-4 focus:ring-[#780116]/10 rounded-2xl text-xl font-black text-slate-900 transition-all placeholder:text-slate-300 shadow-sm"
                                        placeholder="E.g., VIP Flash Sale - February"
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <!-- Sender Selection -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div>
                                        <InputLabel for="whatsapp_number_id" value="Select Sender Account" class="text-xs uppercase tracking-widest font-black text-slate-400 mb-3" />
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-[#780116] transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                            </div>
                                            <select 
                                                id="whatsapp_number_id"
                                                v-model="form.whatsapp_number_id"
                                                class="w-full pl-14 pr-10 py-5 bg-slate-50 border-slate-100 focus:bg-white focus:border-[#780116] focus:ring-4 focus:ring-[#780116]/10 rounded-2xl text-slate-900 font-bold transition-all appearance-none shadow-sm"
                                            >
                                                <option value="" disabled>Select a connected number</option>
                                                <option v-for="number in whatsappNumbers" :key="number.id" :value="number.id">
                                                    {{ number.name || 'Unnamed Account' }} ({{ number.phone_number || number.id }})
                                                </option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.whatsapp_number_id" class="mt-2" />
                                    </div>
                                    <div class="flex items-center">
                                        <div v-if="whatsappNumbers.length === 0" class="p-4 bg-red-50 text-red-700 rounded-2xl border border-red-100 flex items-center gap-3">
                                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                            <p class="text-[10px] font-black uppercase tracking-widest leading-tight">No connected WhatsApp accounts found. Please connect one first.</p>
                                        </div>
                                        <div v-else class="p-4 bg-orange-50 text-orange-700 rounded-2xl border border-orange-100 flex items-center gap-3">
                                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-[10px] font-black uppercase tracking-widest leading-tight">Messages will be routed through this selected account.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Message and Selection Row -->
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div class="space-y-6">
                                        <div class="space-y-3">
                                            <InputLabel for="body" value="Message Content" class="text-xs uppercase tracking-widest font-black text-slate-400" />
                                            <div class="relative group">
                                                <textarea
                                                    id="body"
                                                    v-model="form.body"
                                                    rows="8"
                                                    class="w-full px-6 py-5 bg-slate-50 border-slate-100 focus:bg-white focus:border-[#780116] focus:ring-4 focus:ring-[#780116]/10 rounded-[2rem] text-slate-700 transition-all placeholder:text-slate-300 resize-none font-medium leading-relaxed"
                                                    placeholder="Write something engaging..."
                                                ></textarea>
                                                <div class="absolute bottom-4 right-6 px-3 py-1 bg-white/80 backdrop-blur rounded-full border border-slate-100 text-[10px] font-black text-slate-400">
                                                    {{ form.body.length }} Characters
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.body" class="mt-2" />
                                        </div>

                                        <div class="space-y-3">
                                            <InputLabel value="Attachment (Optional)" class="text-xs uppercase tracking-widest font-black text-slate-400" />
                                            <div class="h-[200px] flex justify-center px-8 py-6 border-2 border-slate-100 border-dashed rounded-[2rem] hover:border-[#780116] hover:bg-red-50 transition-all bg-slate-50/50 group overflow-hidden">
                                                <div class="flex flex-col items-center justify-center text-center">
                                                    <template v-if="!form.media">
                                                        <div class="size-12 bg-white rounded-2xl shadow-sm text-slate-200 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:text-[#780116] transition-all">
                                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        </div>
                                                        <label for="media-upload" class="relative cursor-pointer font-black text-[#780116] hover:text-[#c32f27] underline decoration-2 underline-offset-4 text-sm">
                                                            <span>Browse Files</span>
                                                            <input id="media-upload" type="file" @input="form.media = $event.target.files[0]" class="sr-only">
                                                        </label>
                                                    </template>
                                                    <div v-else class="flex flex-col items-center animate-in zoom-in duration-300">
                                                        <div class="size-16 bg-[#780116] text-white rounded-2xl shadow-xl flex items-center justify-center mb-2 relative">
                                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                            <button type="button" @click="form.media = null" class="absolute -top-2 -right-2 size-8 bg-white text-[#c32f27] rounded-xl shadow-lg flex items-center justify-center hover:bg-red-50 transition border border-slate-100">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                        <p class="text-xs font-black text-slate-900 truncate max-w-[150px]">{{ form.media.name }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.media" class="mt-2" />
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel value="Recipients" class="text-xs uppercase tracking-widest font-black text-slate-400 mb-4" />

                                        <!-- Contact Books Selection -->
                                        <div v-if="contactBooks && contactBooks.length > 0" class="mb-4">
                                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1 mb-2 block">Select Contact Books</label>
                                            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 space-y-2">
                                                <div v-for="book in contactBooks" :key="book.id"
                                                     class="flex items-center p-3 bg-white rounded-xl border border-slate-100 hover:border-[#780116]/20 transition group cursor-pointer"
                                                     @click="toggleBook(book.id)">
                                                    <div class="size-5 rounded border-2 flex items-center justify-center transition-all shrink-0"
                                                         :class="form.contact_book_ids.includes(book.id) ? 'bg-[#780116] border-[#780116] shadow-lg shadow-red-100' : 'bg-white border-slate-200 group-hover:border-[#780116]/30'">
                                                        <svg v-if="form.contact_book_ids.includes(book.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                    </div>
                                                    <div class="ml-3 flex-1 min-w-0">
                                                        <p class="text-xs font-black text-slate-900 truncate">{{ book.name }}</p>
                                                        <p class="text-[10px] text-slate-400 font-medium">{{ book.contacts_count }} contacts</p>
                                                    </div>
                                                    <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Estimated recipients count -->
                                        <div v-if="form.contact_book_ids.length > 0 || form.contact_ids.length > 0" class="mb-4 px-4 py-3 bg-[#780116]/5 rounded-xl border border-[#780116]/10">
                                            <p class="text-xs font-black text-[#780116]">~{{ estimatedRecipients }} estimated recipients (duplicates auto-removed)</p>
                                        </div>

                                        <!-- Individual Contacts (expandable) -->
                                        <div>
                                            <button type="button" @click="showIndividualContacts = !showIndividualContacts" class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition mb-3">
                                                <svg class="w-3 h-3 transition-transform" :class="showIndividualContacts ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
                                                Individual Contacts ({{ contacts.length }})
                                            </button>

                                            <div v-if="showIndividualContacts">
                                                <div class="flex items-center justify-end mb-2">
                                                    <button type="button" @click="toggleSelectAll" class="text-[10px] font-black uppercase tracking-widest text-[#780116] hover:text-[#c32f27] bg-red-50 px-3 py-1.5 rounded-lg transition">
                                                        {{ form.contact_ids.length === contacts.length ? 'Deselect All' : 'Select All' }}
                                                    </button>
                                                </div>
                                                <div class="bg-slate-50 rounded-[2rem] p-6 border border-slate-100 h-[300px] overflow-y-auto">
                                                    <div class="space-y-2">
                                                        <div v-for="contact in contacts" :key="contact.id"
                                                             class="flex items-center p-3 bg-white rounded-xl border border-slate-100 hover:border-[#780116]/20 transition group cursor-pointer"
                                                             @click="form.contact_ids.includes(contact.id) ? form.contact_ids = form.contact_ids.filter(id => id !== contact.id) : form.contact_ids.push(contact.id)">
                                                            <div class="size-5 rounded border-2 flex items-center justify-center transition-all shrink-0"
                                                                 :class="form.contact_ids.includes(contact.id) ? 'bg-[#780116] border-[#780116] shadow-lg shadow-red-100' : 'bg-white border-slate-200 group-hover:border-[#780116]/30'">
                                                                <svg v-if="form.contact_ids.includes(contact.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                            </div>
                                                            <div class="ml-3 flex-1 min-w-0">
                                                                <p class="text-xs font-black text-slate-900 truncate">{{ contact.name }}</p>
                                                                <p class="text-[10px] text-slate-400 font-medium">{{ contact.phone }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-if="contacts.length === 0" class="py-12 text-center">
                                                        <p class="text-sm font-bold text-slate-400">No contacts found.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <InputError :message="form.errors.contact_ids" class="mt-4" />
                                    </div>
                                </div>

                                <!-- Schedule -->
                                 <div class="bg-gradient-to-br from-[#780116] to-[#db7c26] rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl">
                                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-8">
                                        <div class="flex-1 text-center sm:text-left">
                                            <h3 class="text-xl font-black mb-2 italic tracking-tight">Smart Scheduling</h3>
                                            <p class="text-slate-400 font-medium leading-relaxed max-w-sm text-xs">
                                                Automation that works while you sleep. Set your campaign to go live at the peak engagement time.
                                            </p>
                                        </div>
                                        <div class="bg-white/5 backdrop-blur-md p-4 rounded-3xl border border-white/10 flex flex-col items-center">
                                            <div class="relative group">
                                                <input
                                                    type="datetime-local"
                                                    v-model="form.scheduled_at"
                                                    class="w-60 px-6 py-4 bg-white text-slate-900 rounded-xl border-none focus:ring-4 focus:ring-[#f7b538] ring-offset-4 ring-offset-[#780116] font-black text-xs transition-all cursor-pointer shadow-xl appearance-none"
                                                />
                                                <div v-if="form.scheduled_at" @click="form.scheduled_at = ''" class="absolute -top-2 -right-2 size-8 bg-red-500 text-white rounded-xl flex items-center justify-center cursor-pointer hover:bg-red-600 transition shadow-xl border-4 border-slate-900">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                 <!-- Drip Sequence / Message Delay -->
                                <div class="bg-gradient-to-br from-[#db7c26] to-[#c32f27] rounded-[2.5rem] p-8 text-white relative overflow-hidden shadow-2xl">
                                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
                                    <div class="relative z-10">
                                        <div class="flex flex-col sm:flex-row sm:items-start gap-6">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <svg class="w-6 h-6 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    <h3 class="text-xl font-black italic tracking-tight">Drip Sequence</h3>
                                                </div>
                                                <p class="text-white/70 font-medium leading-relaxed text-xs mb-4">
                                                    Add delays between messages to simulate natural human-like sending. This helps avoid spam filters and improves deliverability.
                                                </p>
                                                
                                                <!-- Toggle -->
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <div class="relative">
                                                        <input type="checkbox" v-model="form.is_drip" class="sr-only" />
                                                        <div class="w-12 h-6 rounded-full transition-all" :class="form.is_drip ? 'bg-[#f7b538]' : 'bg-white/20'"></div>
                                                        <div class="absolute top-0.5 left-0.5 size-5 bg-white rounded-full transition-all shadow-lg" :class="form.is_drip ? 'translate-x-6' : ''"></div>
                                                    </div>
                                                    <span class="text-sm font-bold" :class="form.is_drip ? 'text-white' : 'text-white/60'">{{ form.is_drip ? 'Enabled' : 'Disabled' }}</span>
                                                </label>
                                            </div>

                                            <!-- Delay Settings -->
                                            <div v-if="form.is_drip" class="bg-white/10 backdrop-blur-md p-6 rounded-2xl border border-white/10 min-w-[200px] animate-in slide-in-from-right duration-300">
                                                <label class="text-[10px] font-black uppercase tracking-widest text-white/60 block mb-2">Delay Between Messages</label>
                                                <div class="flex items-center gap-3">
                                                    <input 
                                                        type="number" 
                                                        v-model="form.drip_delay_minutes" 
                                                        min="1" 
                                                        max="1440"
                                                        class="w-20 px-4 py-3 bg-white text-slate-900 rounded-xl border-none font-black text-center text-lg" 
                                                    />
                                                    <span class="text-sm font-bold text-white/80">minutes</span>
                                                </div>
                                                <p class="text-[10px] text-white/50 mt-3">
                                                    Est. time: {{ Math.round(estimatedRecipients * form.drip_delay_minutes) }} min for {{ estimatedRecipients }} contacts
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-10 border-t border-slate-50">
                                <Link :href="route('campaigns.index')" class="text-sm font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest transition">
                                    Go Back
                                </Link>
                                 <button type="submit" 
                                    class="px-10 py-5 bg-[#780116] text-white rounded-2xl font-black text-sm tracking-widest uppercase hover:bg-[#c32f27] transition shadow-2xl hover:scale-105 active:scale-95 disabled:opacity-50"
                                    :disabled="form.processing">
                                    {{ form.scheduled_at ? 'Schedule Campaign' : (isEditing ? 'Save Changes' : 'Start Blast Now') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right: Live Preview -->
                    <div class="xl:col-span-4 space-y-6">
                        <div class="sticky top-6">
                            <h3 class="text-xs uppercase tracking-[0.2em] font-black text-slate-400 mb-6 ml-2">Live Message Preview</h3>
                            
                            <!-- Phone Mockup -->
                            <div class="bg-slate-950 rounded-[3rem] p-4 pt-12 pb-12 shadow-2xl border-4 border-slate-800 relative w-full max-w-[340px] mx-auto overflow-hidden">
                                <!-- Speaker -->
                                <div class="absolute top-6 left-1/2 -translate-x-1/2 w-16 h-1.5 bg-slate-800 rounded-full"></div>
                                
                                <!-- Screen Content -->
                                <div class="bg-[#E5DDD5] h-[550px] rounded-[2rem] overflow-hidden flex flex-col relative">
                                    <!-- WA Header -->
                                    <div class="bg-[#075E54] p-4 flex items-center gap-3">
                                        <div class="size-8 bg-slate-200 rounded-full flex items-center justify-center text-slate-400">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        </div>
                                        <div>
                                            <p class="text-white text-[10px] font-bold">Recipient</p>
                                            <p class="text-[#D1E1E0] text-[8px]">online</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Chat Area -->
                                    <div class="flex-1 p-4 space-y-4 overflow-y-auto">
                                        <div v-if="form.body" class="max-w-[85%] bg-white rounded-xl p-2.5 shadow-sm text-xs text-slate-800 relative animate-in slide-in-from-bottom-2 duration-300">
                                            <!-- Media Placeholder -->
                                            <div v-if="form.media" class="mb-2 bg-slate-100 rounded-lg aspect-video flex items-center justify-center text-slate-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            </div>

                                            <!-- Link Preview Content (If Feature Enabled) -->
                                            <template v-if="hasLinkPreview && detectedLink">
                                                <div class="mb-3 bg-slate-50 border-l-4 border-[#f7b538] rounded-r-lg p-2 flex flex-col gap-1 shadow-inner group">
                                                    <span class="text-[9px] font-bold text-[#db7c26] truncate">{{ getDomain(detectedLink) }}</span>
                                                    <span class="text-[11px] font-black text-slate-900 leading-tight">Sendora - Effortless Engagement</span>
                                                    <span class="text-[9px] text-slate-500 leading-snug">Experience the power of multiple user connections and automated campaigns...</span>
                                                    <div class="mt-1 aspect-video bg-slate-200 rounded-md overflow-hidden relative">
                                                        <div class="absolute inset-0 flex items-center justify-center text-slate-400">
                                                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Message Text -->
                                            <p class="whitespace-pre-wrap leading-relaxed">
                                                <template v-for="(word, i) in form.body.split(/(\s+)/)">
                                                    <span v-if="word.match(/https?:\/\/[^\s]+/)" class="text-[#780116] underline">{{ word }}</span>
                                                    <span v-else>{{ word }}</span>
                                                </template>
                                            </p>

                                            <!-- Time & Status -->
                                            <div class="flex items-center justify-end gap-1 mt-1">
                                                <span class="text-[8px] text-slate-400">{{ new Date().getHours() }}:{{ new Date().getMinutes() < 10 ? '0'+new Date().getMinutes() : new Date().getMinutes() }}</span>
                                            </div>
                                            
                                            <!-- Bubble Tail -->
                                            <div class="absolute -left-2 top-0 w-3 h-3 bg-white" style="clip-path: polygon(100% 0, 0 0, 100% 100%);"></div>
                                        </div>

                                        <div v-else class="flex items-center justify-center h-full text-slate-400 text-xs italic">
                                            Type your message to see preview...
                                        </div>
                                    </div>

                                    <!-- WA Input Mock -->
                                    <div class="bg-white p-3 flex items-center gap-2">
                                        <div class="flex-1 bg-slate-50 border border-slate-100 rounded-full px-4 py-2 text-[10px] text-slate-400">Message</div>
                                        <div class="size-8 bg-[#128C7E] rounded-full flex items-center justify-center text-white">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Navigation Bar -->
                                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 w-24 h-1 bg-slate-800 rounded-full"></div>
                            </div>

                            <!-- Feature Indicator -->
                             <div class="mt-6 p-6 rounded-[2rem] border-2 border-dashed flex items-center gap-4 transition-all"
                                 :class="hasLinkPreview ? 'bg-red-50 border-red-100' : 'bg-slate-50 border-slate-100'">
                                <div class="size-10 rounded-xl flex items-center justify-center"
                                     :class="hasLinkPreview ? 'bg-[#780116] text-white shadow-lg shadow-red-100' : 'bg-slate-200 text-slate-400'">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.828a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-[11px] font-black uppercase tracking-widest" :class="hasLinkPreview ? 'text-[#780116]' : 'text-slate-500'">Link Preview</h4>
                                    <p class="text-[10px] font-medium" :class="hasLinkPreview ? 'text-[#c32f27]' : 'text-slate-400'">
                                        {{ hasLinkPreview ? 'Status: Active on your plan' : 'Upgrade to enable automatic previews' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
