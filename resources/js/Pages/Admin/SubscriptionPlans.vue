<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    plans: Array,
    currency: String,
});

const featureNames = {
    'google_calendar': 'Google Calendar Sync',
    'ai_command_parsing': 'AI Command Parsing (/sendora)',
    'auto_reply': 'AI Playbooks',
    'api_access': 'API Access',
};

const getActiveFeatures = (plan) => {
    const features = plan.limits?.features || {};
    return Object.keys(features)
        .filter(key => features[key])
        .map(key => featureNames[key] || key);
};

const editingPlan = ref(null);
const form = useForm({
    name: '',
    monthly_price: '',
    limits: {
        whatsapp_nos: 0,
        reminders_per_month: 0,
        features: {}
    },
});

const editPlan = (plan) => {
    editingPlan.value = plan;
    form.name = plan.name;
    form.monthly_price = plan.monthly_price;
    form.limits = JSON.parse(JSON.stringify(plan.limits || {
        whatsapp_nos: 0,
        reminders_per_month: 0,
        features: {}
    }));
};

const toggleLifetimeActive = (plan) => {
    const toggleForm = useForm({
        name: plan.name,
        monthly_price: plan.monthly_price,
        limits: plan.limits,
        is_active: !plan.is_active,
    });
    toggleForm.put(route('admin.plans.update', plan.id));
};

const updatePlan = () => {
    form.put(route('admin.plans.update', editingPlan.value.id), {
        onSuccess: () => {
            editingPlan.value = null;
        }
    });
};

const toggleFeature = (key) => {
    if (!form.limits.features) form.limits.features = {};
    form.limits.features[key] = !form.limits.features[key];
};

const closeEdit = () => {
    editingPlan.value = null;
    form.reset();
};
</script>

<template>
    <AppLayout title="Subscription Plans">
        <template #header>
            <h2 class="font-black text-2xl text-slate-900 leading-tight">
                Subscription Plans
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="plans.length === 0" class="text-center py-20 bg-white rounded-[3rem] border-2 border-dashed border-slate-100">
                    <div class="size-20 bg-slate-50 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-slate-300">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">No Plans Yet</h3>
                    <p class="text-slate-500 mt-2">Defined plans will appear here for management.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="plan in plans" :key="plan.id"
                         class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/50 overflow-hidden border border-slate-100 flex flex-col transition-all hover:-translate-y-2 duration-500"
                         :class="{
                            'ring-2 ring-[#780116] ring-offset-4': plan.name === 'Pro',
                            'ring-2 ring-amber-400 ring-offset-4': plan.is_lifetime,
                         }">

                        <div class="p-10 flex-1">
                            <div class="flex justify-between items-start mb-6">
                                <h3 class="text-2xl font-black text-slate-900 leading-none">{{ plan.name }}</h3>
                                <div v-if="plan.is_lifetime" class="px-4 py-1.5 bg-amber-400 text-amber-900 text-[9px] font-black uppercase tracking-[0.2em] rounded-full shadow-lg shadow-amber-200">Lifetime</div>
                                <div v-else-if="plan.name === 'Pro'" class="px-4 py-1.5 bg-[#780116] text-white text-[9px] font-black uppercase tracking-[0.2em] rounded-full shadow-lg shadow-red-200">Recommended</div>
                            </div>

                            <!-- Lifetime is_active toggle -->
                            <div v-if="plan.is_lifetime" class="mb-6 flex items-center gap-4 p-4 bg-amber-50 rounded-2xl border border-amber-100">
                                <button
                                    @click="toggleLifetimeActive(plan)"
                                    class="relative inline-flex items-center h-6 w-11 rounded-full transition-colors duration-300 focus:outline-none"
                                    :class="plan.is_active ? 'bg-amber-400' : 'bg-slate-200'"
                                >
                                    <span class="inline-block w-4 h-4 bg-white rounded-full shadow transform transition-transform duration-300" :class="plan.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
                                <span class="text-xs font-black uppercase tracking-widest" :class="plan.is_active ? 'text-amber-700' : 'text-slate-400'">
                                    {{ plan.is_active ? 'Enabled — Visible on Pricing page' : 'Disabled — Hidden from Pricing page' }}
                                </span>
                            </div>

                            <div class="flex items-baseline text-slate-900">
                                <span class="text-sm font-bold mr-1 text-slate-400">{{ currency }}</span>
                                <span class="text-5xl font-black tracking-tighter">{{ plan.monthly_price }}</span>
                                <span class="ml-1 text-slate-400 font-bold text-sm">{{ plan.is_lifetime ? 'one-time' : '/mo' }}</span>
                            </div>
                            
                            <div class="mt-10 space-y-4">
                                <!-- Core Limits -->
                                <div class="grid grid-cols-1 gap-3">
                                    <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <div class="size-2.5 rounded-full bg-[#780116] mr-4 shadow-sm shadow-red-200"></div>
                                        <span>{{ plan.limits?.whatsapp_nos }} WhatsApp accounts</span>
                                    </div>
                                    <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <div class="size-2.5 rounded-full bg-[#f7b538] mr-4 shadow-sm shadow-orange-200"></div>
                                        <span>{{ plan.limits?.reminders_per_month === 0 ? 'Unlimited' : plan.limits?.reminders_per_month }} Reminders / mo</span>
                                    </div>
                                </div>

                                <!-- Dynamic Features -->
                                <div class="mt-8">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 ml-1">Included Features</p>
                                    <ul class="grid grid-cols-1 gap-3">
                                        <li v-for="feature in getActiveFeatures(plan)" :key="feature" class="flex items-center text-[13px] font-bold text-slate-500">
                                            <svg class="w-4 h-4 text-[#780116] mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            {{ feature }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="p-10 pt-0">
                            <button @click="editPlan(plan)" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-[#780116] transition-all shadow-xl shadow-slate-200 transform active:scale-95">
                                Edit Architecture
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <DialogModal :show="editingPlan !== null" @close="closeEdit" max-width="2xl">
            <template #title>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-red-50 rounded-2xl flex items-center justify-center text-[#780116] shadow-sm shadow-red-100 transform -rotate-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <div class="text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Plan Configuration</div>
                        <span class="text-xl font-black text-slate-900">{{ form.name }}</span>
                    </div>
                </div>
            </template>
            <template #content>
                <div class="space-y-8 py-4">
                    <!-- General Settings -->
                    <div class="grid grid-cols-1 gap-6 p-6 bg-slate-50 rounded-3xl border border-slate-100">
                        <div>
                            <InputLabel for="name" value="PLAN NAME" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="name" v-model="form.name" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-white" placeholder="e.g. Pro" />
                        </div>
                        <div>
                            <InputLabel for="monthly" value="PRICE" class="text-[10px] font-black tracking-widest text-slate-400 mb-2 ml-1" />
                            <div class="relative">
                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 font-bold">{{ currency }}</span>
                                <TextInput id="monthly" v-model="form.monthly_price" type="number" step="0.01" class="w-full pl-14 pr-5 py-3 border-slate-100 rounded-2xl font-bold bg-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Usage Limits -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4 ml-1">Usage Limits</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
                                <InputLabel for="wa" value="WHATSAPP NOS" class="text-[9px] font-black text-slate-400 mb-2" />
                                <TextInput id="wa" v-model="form.limits.whatsapp_nos" type="number" class="w-full text-lg font-black bg-slate-50 border-none rounded-xl" />
                            </div>
                            <div class="p-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
                                <InputLabel for="reminders" value="REMINDERS / MONTH (0 = unlimited)" class="text-[9px] font-black text-slate-400 mb-2" />
                                <TextInput id="reminders" v-model="form.limits.reminders_per_month" type="number" class="w-full text-lg font-black bg-slate-50 border-none rounded-xl" />
                            </div>
                        </div>
                    </div>

                    <!-- Features Toggles -->
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-400 mb-4 ml-1">Enable Features</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 px-1">
                            <div v-for="(name, key) in featureNames" :key="key" 
                                 @click="toggleFeature(key)"
                                 class="flex items-center justify-between p-5 rounded-2xl border-2 transition-all cursor-pointer group/item"
                                 :class="form.limits.features?.[key] ? 'bg-red-50 border-red-100 shadow-sm' : 'bg-white border-slate-50 hover:border-slate-100'">
                                <span class="text-sm font-black" :class="form.limits.features?.[key] ? 'text-[#780116]' : 'text-slate-400'">{{ name }}</span>
                                <div class="size-8 rounded-xl flex items-center justify-center transition-all duration-300"
                                     :class="form.limits.features?.[key] ? 'bg-[#780116] text-white shadow-lg shadow-red-200 transform scale-110' : 'bg-slate-100 text-slate-300 group-hover/item:bg-slate-200 group-hover/item:text-slate-400'">
                                    <svg v-if="form.limits.features?.[key]" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <div v-else class="size-1.5 bg-current rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeEdit" class="rounded-[1.25rem]! px-8! py-4! text-[11px]! font-black! uppercase! tracking-widest!">Cancel</SecondaryButton>
                <PrimaryButton class="ml-4 px-10! py-4! bg-[#780116]! rounded-[1.25rem]! text-[11px]! font-black! uppercase! tracking-widest! shadow-xl shadow-red-200!" @click="updatePlan" :disabled="form.processing">
                    {{ form.processing ? 'Syncing...' : 'Push Updates' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
