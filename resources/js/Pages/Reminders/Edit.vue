<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    reminder: Object,
    whatsappNumbers: Array,
    hasCalendar: Boolean,
});

const formatForInput = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    title: props.reminder.title,
    description: props.reminder.description || '',
    event_at: formatForInput(props.reminder.event_at || props.reminder.reminder_at),
    minutes_before: props.reminder.minutes_before,
    location: props.reminder.location || '',
    recurrence_rule: props.reminder.recurrence_rule || '',
    whatsapp_number_id: props.reminder.whatsapp_number_id || '',
});

const submit = () => {
    form.put(route('reminders.update', props.reminder.id));
};

const minDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};
</script>

<template>
    <AppLayout title="Edit Reminder">
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('reminders.index')" class="p-2 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </Link>
                <h2 class="font-black text-2xl text-slate-800 leading-tight tracking-tight">Edit Reminder</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-8">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8 space-y-6">
                        <div>
                            <InputLabel for="title" value="TITLE" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="title" v-model="form.title" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" required />
                            <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <InputLabel for="event_at" value="DATE & TIME" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="event_at" v-model="form.event_at" type="datetime-local" :min="minDateTime()" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" required />
                            <p v-if="form.errors.event_at" class="text-red-500 text-xs mt-1">{{ form.errors.event_at }}</p>
                        </div>

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

                        <div>
                            <InputLabel for="location" value="LOCATION (OPTIONAL)" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="location" v-model="form.location" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold" />
                        </div>

                        <div>
                            <InputLabel for="description" value="DESCRIPTION (OPTIONAL)" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <textarea id="description" v-model="form.description" rows="3" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold resize-none"></textarea>
                        </div>

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

                        <div>
                            <InputLabel for="wa_number" value="WHATSAPP NUMBER" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="wa_number" v-model="form.whatsapp_number_id" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-white text-slate-700">
                                <option value="">Auto-select</option>
                                <option v-for="num in whatsappNumbers" :key="num.id" :value="num.id">
                                    {{ num.phone_number || `Device #${num.id}` }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <Link :href="route('reminders.index')" class="px-8 py-4 bg-white border border-slate-200 text-slate-600 rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-slate-50 transition">
                            Cancel
                        </Link>
                        <PrimaryButton class="px-10! py-4! bg-[#780116]! rounded-2xl! text-[11px]! font-black! uppercase! tracking-widest! shadow-xl shadow-red-200!" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
