<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    subscription: Object,
    plans: Array,
    currency: String,
});

const billingCycle = ref('monthly');
const form = useForm({
    plan_id: null,
    billing_cycle: 'monthly',
});

const featureNames = {
    'text_support': 'Text Messages',
    'image_support': 'Image Support',
    'file_support': 'File Attachments',
    'scheduling': 'Message Scheduling',
    'pdf_support': 'PDF Support',
    'link_preview': 'Link Previews',
    'auto_reply': 'Auto Replies',
    'message_preview': 'Message Previews',
    'multi_user': 'Multi-User Support',
    'webhooks': 'Webhook Integration',
    'api_access': 'API Access',
};

const getActiveFeatures = (plan) => {
    const features = plan.limits.features || {};
    return Object.keys(features)
        .filter(key => features[key])
        .map(key => featureNames[key] || key);
};

const isDowngrade = (plan) => {
    if (!props.subscription) return false;
    return Number(plan.monthly_price) < Number(props.subscription.plan.monthly_price);
};

const selectPlan = (plan) => {
    if (props.subscription?.plan?.id === plan.id) return;
    if (isDowngrade(plan)) return;
    
    form.plan_id = plan.id;
    form.billing_cycle = billingCycle.value;
    form.post(route('payments.initiate'));
};

const cancelForm = useForm({});
const confirmCancel = ref(false);

const cancelSubscription = () => {
    cancelForm.post('/subscription/cancel-plan', {
        preserveScroll: true,
        onSuccess: () => confirmCancel.value = false,
        onError: () => {
            // Optional: handle error visually if needed, usually global flash handles it
            confirmCancel.value = false;
        }
    });
};

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d.getTime())) return date;
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
};
</script>

<template>
    <AppLayout title="My Subscription">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Membership Hub
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Current Subscription Status -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-3xl border border-slate-100 mb-12">
                    <div class="p-8 sm:p-12">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div>
                                <h3 class="text-3xl font-black text-slate-900 mb-2 leading-none">Active Membership</h3>
                                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-2">Manage your high-performance plan</p>
                            </div>
                            <div v-if="subscription" class="mt-6 md:mt-0 px-8 py-4 bg-red-50 border border-red-100 rounded-[1.5rem] flex items-center shadow-sm">
                                <span class="size-2.5 bg-[#f7b538] rounded-full mr-3 animate-pulse shadow-sm shadow-orange-300"></span>
                                <span class="text-[#780116] font-black tracking-widest uppercase text-xs">{{ subscription.status }} : {{ subscription.plan.name }}</span>
                            </div>
                            <div v-else class="mt-6 md:mt-0 px-8 py-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] flex items-center">
                                <span class="size-2.5 bg-slate-300 rounded-full mr-3"></span>
                                <span class="text-slate-400 font-black tracking-widest uppercase text-xs">Awaiting Activation</span>
                            </div>
                        </div>

                        <div v-if="subscription" class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-100 transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 group">
                                <p class="text-[10px] font-black text-slate-300 group-hover:text-[#780116] uppercase tracking-[0.2em] mb-3 transition-colors">Tier Architecture</p>
                                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ subscription.plan.name }} Edition</p>
                            </div>
                            <div class="p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-100 transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 group">
                                <p class="text-[10px] font-black text-slate-300 group-hover:text-[#db7c26] uppercase tracking-[0.2em] mb-3 transition-colors">Billing Quantum</p>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900 tracking-tight">{{ currency }} {{ Number(subscription.plan.monthly_price).toFixed(2) }}</p>
                                    <div class="flex flex-col items-start justify-end pb-0.5">
                                        <span class="text-slate-400 font-bold text-[8px] uppercase tracking-wider leading-tight">per</span>
                                        <span class="text-slate-400 font-bold text-[8px] uppercase tracking-wider leading-tight">month</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="subscription.ends_at" class="p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-100 transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 group">
                                <p class="text-[10px] font-black text-slate-300 group-hover:text-[#f7b538] uppercase tracking-[0.2em] mb-3 transition-colors">Synchronization Date</p>
                                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ formatDate(subscription.ends_at) }}</p>
                            </div>
                        </div>
                        <div v-if="subscription" class="mt-12 flex justify-end pt-6 border-t border-slate-50">
                             <div v-if="!subscription.cancelled_at">
                                <button @click="confirmCancel = true" class="px-6 py-2 bg-red-50 text-red-600 font-bold text-xs rounded-full hover:bg-red-100 transition-colors flex items-center mb-1">
                                    <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Cancel Subscription
                                </button>
                             </div>
                             <div v-else class="px-5 py-3 bg-red-50 text-red-700 rounded-xl text-xs font-bold border border-red-100">
                                Cancellation Scheduled for {{ formatDate(subscription.ends_at) }}
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Cancel Confirmation Modal -->
                <div v-if="confirmCancel" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                    <div class="bg-white rounded-[2rem] p-8 max-w-md w-full shadow-2xl border border-slate-100">
                        <div class="size-16 bg-red-50 rounded-2xl flex items-center justify-center mb-6 text-[#780116]">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 mb-2">Cancel Subscription?</h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-8">
                            Are you sure you want to cancel? You will retain access to all features until <span class="font-bold text-slate-900">{{ formatDate(subscription?.ends_at) }}</span>.
                        </p>
                        <div class="flex gap-4">
                            <button type="button" @click="confirmCancel = false" class="flex-1 py-3 bg-slate-50 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition border border-slate-200">Keep Plan</button>
                            <button type="button" @click="cancelSubscription" :disabled="cancelForm.processing" class="flex-1 py-3 bg-[#780116] text-white font-bold rounded-xl hover:bg-[#c32f27] transition flex justify-center items-center shadow-lg shadow-red-200">
                                <span v-if="cancelForm.processing" class="animate-spin mr-2 h-4 w-4 border-2 border-white/30 border-t-white rounded-full"></span>
                                Confirm Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upgrade Options -->
                <div class="text-center mb-12">
                    <h3 class="text-4xl font-black text-slate-900 mb-4 tracking-tight">Available Plans</h3>
                    <p class="text-slate-500 font-medium max-w-2xl mx-auto">Scale your performance with our premium features. Upgrade your subscription anytime.</p>
                
                    <!-- Billing Switcher -->
                    <div class="mt-8 flex justify-center">
                        <div class="flex items-center space-x-2 bg-white p-2 rounded-[1.5rem] border border-slate-100 shadow-lg shadow-slate-100">
                            <button 
                                @click="billingCycle = 'monthly'"
                                :class="billingCycle === 'monthly' ? 'bg-[#780116] text-white shadow-xl shadow-red-100' : 'text-slate-400 hover:text-slate-700'"
                                class="px-10 py-4 rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest transition-all duration-300"
                            >
                                Monthly
                            </button>
                            <button 
                                @click="billingCycle = 'yearly'"
                                :class="billingCycle === 'yearly' ? 'bg-[#780116] text-white shadow-xl shadow-red-100' : 'text-slate-400 hover:text-slate-700'"
                                class="px-10 py-4 rounded-[1.25rem] text-[11px] font-black uppercase tracking-widest transition-all duration-300 flex items-center"
                            >
                                Yearly Access
                                <span class="ml-3 px-2 py-0.5 rounded-lg bg-[#f7b538] text-[9px] text-[#780116] font-black tracking-normal" :class="billingCycle === 'yearly' ? 'bg-white/20 text-white' : ''">
                                    - 20%
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    <div v-for="plan in plans" :key="plan.id" 
                         class="bg-white rounded-[2.5rem] border border-slate-100 p-8 flex flex-col transition-all duration-500 hover:shadow-2xl sm:hover:-translate-y-4"
                         :class="{'ring-2 ring-[#780116] ring-offset-4': plan.name === 'Pro'}">
                        
                        <div class="mb-10 lg:mb-12">
                            <h4 class="text-xl font-black text-slate-900 mb-4 tracking-tight">{{ plan.name }}</h4>
                            <div class="flex items-baseline gap-2 mb-2">
                                <div class="flex items-baseline">
                                    <span class="text-slate-400 font-black text-xs uppercase tracking-wider mr-1">{{ currency }}</span>
                                    <span class="text-4xl font-black text-slate-900 tracking-tighter">{{ billingCycle === 'monthly' ? Number(plan.monthly_price).toFixed(2) : (Number(plan.yearly_price) / 12).toFixed(2) }}</span>
                                </div>
                                <div class="flex flex-col items-start justify-end pb-1">
                                    <span class="text-slate-400 font-black text-[9px] uppercase tracking-[0.15em] leading-tight">per</span>
                                    <span class="text-slate-400 font-black text-[9px] uppercase tracking-[0.15em] leading-tight">month</span>
                                </div>
                            </div>
                            <div v-if="billingCycle === 'yearly' && plan.yearly_price > 0" class="text-[9px] font-black text-[#780116] uppercase tracking-widest bg-red-50 inline-block px-2 py-0.5 rounded-lg">
                                Billed Annually
                            </div>
                        </div>

                        <div class="space-y-4 mb-12 flex-grow border-t border-slate-50 pt-8">
                            <!-- Core Limits -->
                            <div class="grid grid-cols-1 gap-3">
                                <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                    <div class="size-2.5 rounded-full bg-[#780116] mr-4 shadow-sm shadow-red-200 shrink-0"></div>
                                    <span>{{ plan.limits?.whatsapp_nos }} WhatsApp accounts</span>
                                </div>
                                <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                    <div class="size-2.5 rounded-full bg-[#f7b538] mr-4 shadow-sm shadow-orange-200 shrink-0"></div>
                                    <span>{{ plan.limits?.contacts }} Contacts allowed</span>
                                </div>
                                <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                    <div class="size-2.5 rounded-full bg-[#db7c26] mr-4 shadow-sm shadow-orange-300 shrink-0"></div>
                                    <span>{{ plan.limits?.messages }} Messages / mo</span>
                                </div>
                            </div>

                            <!-- Included Features -->
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

                        <button 
                            @click="selectPlan(plan)"
                            :disabled="subscription?.plan?.id === plan.id || isDowngrade(plan) || form.processing"
                            :class="[
                                subscription?.plan?.id === plan.id ? 'bg-slate-50 text-slate-300 cursor-not-allowed border border-slate-100' :
                                isDowngrade(plan) ? 'bg-slate-50 text-slate-200 cursor-not-allowed border border-slate-50 shadow-none' :
                                (plan.name === 'Pro' ? 'bg-[#780116] text-white hover:bg-[#c32f27] shadow-xl shadow-red-200/50' : 'bg-slate-900 text-white hover:bg-slate-800 shadow-xl shadow-slate-200/30'),
                                'w-full py-5 rounded-2xl font-black text-[11px] tracking-[0.2em] uppercase transition-all flex items-center justify-center transform active:scale-95'
                            ]"
                        >
                            <span v-if="form.processing && form.plan_id === plan.id" class="mr-3 animate-spin size-4 border-2 border-white/30 border-t-white rounded-full"></span>
                            {{ 
                                subscription?.plan?.id === plan.id ? 'Current Tier' : 
                                isDowngrade(plan) ? 'Locked Tier' : 'Migrate Now' 
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
