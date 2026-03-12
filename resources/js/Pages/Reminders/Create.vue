<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    whatsappNumbers: Array,
    hasCalendar: Boolean,
});

const form = useForm({
    title: '',
    description: '',
    event_at: '',
    minutes_before: 15,
    location: '',
    recurrence_rule: '',
    whatsapp_number_id: props.whatsappNumbers?.[0]?.id || '',
    add_to_calendar: props.hasCalendar,
});

const submit = () => {
    form.post(route('reminders.store'));
};

// Set minimum datetime to now
const minDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};
</script>

<template>
    <AppLayout title="Create Reminder">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('reminders.index')" class="p-2 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">Create Reminder</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
                        <!-- Title -->
                        <div>
                            <InputLabel for="title" value="TITLE" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="title" v-model="form.title" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" placeholder="e.g. Team Meeting" required />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <!-- Date & Time -->
                        <div>
                            <InputLabel for="event_at" value="DATE & TIME" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="event_at" v-model="form.event_at" type="datetime-local" :min="minDateTime()" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" required />
                            <p v-if="form.errors.event_at" class="text-red-500 text-xs mt-1">{{ form.errors.event_at }}</p>
                        </div>

                        <!-- Remind Before -->
                        <div>
                            <InputLabel for="minutes_before" value="REMIND ME BEFORE" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="minutes_before" v-model="form.minutes_before" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-white text-slate-700">
                                <option :value="0">At the time of event</option>
                                <option :value="5">5 minutes before</option>
                                <option :value="10">10 minutes before</option>
                                <option :value="15">15 minutes before</option>
                                <option :value="30">30 minutes before</option>
                                <option :value="60">1 hour before</option>
                                <option :value="120">2 hours before</option>
                                <option :value="1440">1 day before</option>
                            </select>
                        </div>

                        <!-- Location -->
                        <div>
                            <InputLabel for="location" value="LOCATION (OPTIONAL)" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="location" v-model="form.location" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" placeholder="e.g. Conference Room A" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="DESCRIPTION (OPTIONAL)" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <textarea id="description" v-model="form.description" rows="3" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold resize-none" placeholder="Additional details..."></textarea>
                        </div>

                        <!-- Recurrence -->
                        <div>
                            <InputLabel for="recurrence" value="REPEAT" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="recurrence" v-model="form.recurrence_rule" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-white text-slate-700">
                                <option value="">No repeat (one-time)</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <!-- WhatsApp & Calendar Options -->
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Delivery Options</p>

                        <!-- WhatsApp Number -->
                        <div>
                            <InputLabel for="wa_number" value="SEND VIA WHATSAPP NUMBER" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="wa_number" v-model="form.whatsapp_number_id" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-white text-slate-700">
                                <option value="">Auto-select (first connected)</option>
                                <option v-for="num in whatsappNumbers" :key="num.id" :value="num.id">
                                    {{ num.phone_number || `Device #${num.id}` }}
                                </option>
                            </select>
                            <p v-if="whatsappNumbers.length === 0" class="text-amber-500 text-xs mt-2 font-bold">
                                No connected WhatsApp numbers. <Link :href="route('whatsapp.index')" class="underline">Connect one first.</Link>
                            </p>
                        </div>

                        <!-- Add to Google Calendar -->
                        <div v-if="hasCalendar" class="flex items-center gap-4 p-5 bg-blue-50 rounded-2xl border border-blue-100">
                            <button type="button" @click="form.add_to_calendar = !form.add_to_calendar"
                                class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors duration-300 focus:outline-none"
                                :class="form.add_to_calendar ? 'bg-blue-500' : 'bg-slate-200'">
                                <span class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-300"
                                    :class="form.add_to_calendar ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                            <span class="text-sm font-bold text-blue-700">Also add to Google Calendar</span>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-4">
                        <Link :href="route('reminders.index')" class="px-8 py-4 bg-white border border-slate-200 text-slate-600 rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-slate-50 transition">
                            Cancel
                        </Link>
                        <PrimaryButton class="px-10! py-4! bg-[#780116]! rounded-2xl! text-[11px]! font-black! uppercase! tracking-widest! shadow-xl shadow-red-200!" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Reminder' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
