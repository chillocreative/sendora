<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    subscription: Object,
    plans: Array,
    currency: String,
});

const form = useForm({
    plan_id: null,
    billing_cycle: 'monthly',
});

const featureNames = {
    'google_calendar': 'Google Calendar Sync',
    'ai_command_parsing': 'AI Command Parsing (/sendora)',
    'auto_reply': 'AI Playbooks',
    'api_access': 'API Access',
};

const getActiveFeatures = (plan) => {
    const features = plan.limits.features || {};
    return Object.keys(features)
        .filter(key => features[key])
        .map(key => featureNames[key] || key);
};

const isLifetimeUser = computed(() => props.subscription?.plan?.is_lifetime === true);

const isDowngrade = (plan) => {
    if (!props.subscription) return false;
    // Lifetime users cannot downgrade (lifetime is permanent)
    if (isLifetimeUser.value) return true;
    return Number(plan.monthly_price) < Number(props.subscription.plan.monthly_price);
};

const selectPlan = (plan) => {
    if (props.subscription?.plan?.id === plan.id) return;
    if (isDowngrade(plan)) return;

    form.plan_id = plan.id;
    form.billing_cycle = plan.is_lifetime ? 'lifetime' : 'monthly';
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
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
                            <!-- Lifetime: show permanent badge -->
                            <div v-if="isLifetimeUser" class="p-8 bg-amber-50 rounded-[2.5rem] border border-amber-100 flex flex-col justify-center">
                                <p class="text-[10px] font-black text-amber-400 uppercase tracking-[0.2em] mb-3">Access Status</p>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <p class="text-2xl font-black text-slate-900 tracking-tight">Permanent — Never Expires</p>
                                </div>
                            </div>
                            <!-- Regular plan: show expiry date -->
                            <div v-else-if="subscription.ends_at" class="p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-100 transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 group">
                                <p class="text-[10px] font-black text-slate-300 group-hover:text-[#f7b538] uppercase tracking-[0.2em] mb-3 transition-colors">Synchronization Date</p>
                                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ formatDate(subscription.ends_at) }}</p>
                            </div>
                        </div>
                        <div v-if="subscription" class="mt-12 flex justify-end pt-6 border-t border-slate-50">
                            <!-- Lifetime: show permanent badge, no cancel -->
                            <div v-if="isLifetimeUser" class="px-5 py-3 bg-amber-50 text-amber-700 rounded-xl text-xs font-black border border-amber-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Lifetime Access — No Cancellation Needed
                            </div>
                             <div v-else-if="!subscription.cancelled_at">
                                <button @click="confirmCancel = true" class="px-6 py-2 bg-red-50 text-red-600 font-bold text-xs rounded-full hover:bg-red-100 transition-colors flex items-center mb-1">
                                    <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Cancel Subscription
                                </button>
                             </div>
                             <div v-else class="px-5 py-3 bg-red-50 text-red-700 rounded-xl text-xs font-bold border border-red-100">
                                <span v-if="subscription.ends_at">Cancellation Scheduled for {{ formatDate(subscription.ends_at) }}</span>
                                <span v-else>Subscription Cancelled - Access will continue until billing period ends</span>
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
                
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
                    <div v-for="plan in plans.filter(p => !p.is_lifetime && p.monthly_price > 0)" :key="plan.id"
                         class="bg-white rounded-[2.5rem] border border-slate-100 p-8 flex flex-col transition-all duration-500 hover:shadow-2xl sm:hover:-translate-y-4"
                         :class="{'ring-2 ring-[#780116] ring-offset-4': plan.name === 'Pro'}">

                        <div class="mb-10 lg:mb-12">
                            <h4 class="text-xl font-black text-slate-900 mb-4 tracking-tight">{{ plan.name }}</h4>
                            <div class="mb-2">
                                <div class="flex items-baseline justify-start mb-1">
                                    <span class="text-slate-400 font-black text-xs uppercase tracking-wider mr-1">{{ currency }}</span>
                                    <span class="text-4xl font-black text-slate-900 tracking-tighter">{{ Number(plan.monthly_price).toFixed(2) }}</span>
                                </div>
                                <div class="text-slate-400 font-black text-[9px] uppercase tracking-[0.15em]">
                                    per month
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 mb-12 flex-grow border-t border-slate-50 pt-8">
                            <div class="grid grid-cols-1 gap-3">
                                <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                    <div class="size-2.5 rounded-full bg-[#780116] mr-4 shadow-sm shadow-red-200 shrink-0"></div>
                                    <span>{{ plan.limits?.whatsapp_nos }} WhatsApp accounts</span>
                                </div>
                                <div class="flex items-center text-[13px] font-bold text-slate-600 bg-slate-50/50 p-4 rounded-2xl border border-slate-100">
                                    <div class="size-2.5 rounded-full bg-[#f7b538] mr-4 shadow-sm shadow-orange-200 shrink-0"></div>
                                    <span>{{ plan.limits?.reminders_per_month === 0 ? 'Unlimited' : plan.limits?.reminders_per_month }} Reminders / mo</span>
                                </div>
                            </div>
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

                <!-- Lifetime Plan Row -->
                <div v-for="plan in plans.filter(p => p.is_lifetime)" :key="'lifetime-' + plan.id"
                     class="mt-8 bg-gradient-to-r from-amber-50 via-white to-amber-50 rounded-[2.5rem] p-8 sm:p-10 border-2 border-amber-200 shadow-xl shadow-amber-100/60 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">

                    <div class="flex-1 text-center md:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-400 text-amber-900 text-[10px] font-black uppercase tracking-widest mb-4 shadow-md shadow-amber-200">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            One-Time Payment · Forever
                        </div>
                        <h4 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">{{ plan.name }} Access</h4>
                        <p class="text-slate-500 font-medium max-w-sm">Pay once, use forever. Pro-level features with no recurring charges.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center gap-8">
                        <div class="text-center">
                            <div class="flex items-baseline justify-center">
                                <span class="text-lg font-bold text-amber-500 mr-1">{{ currency }}</span>
                                <span class="text-5xl font-black text-slate-900">{{ Number(plan.monthly_price).toFixed(2) }}</span>
                            </div>
                            <p class="mt-1 text-[10px] font-black text-amber-700 uppercase tracking-widest">One-Time · No Renewal</p>
                        </div>

                        <button
                            @click="selectPlan(plan)"
                            :disabled="subscription?.plan?.id === plan.id || isLifetimeUser || form.processing"
                            :class="[
                                (subscription?.plan?.id === plan.id || isLifetimeUser) ? 'bg-slate-100 text-slate-400 cursor-not-allowed shadow-none' : 'bg-amber-400 text-amber-900 hover:bg-amber-500 shadow-xl shadow-amber-200 hover:scale-105',
                                'px-10 py-5 rounded-2xl font-black text-[11px] tracking-[0.2em] uppercase transition-all active:scale-95'
                            ]"
                        >
                            <span v-if="form.processing && form.plan_id === plan.id" class="mr-2 animate-spin size-4 inline-block border-2 border-amber-900/30 border-t-amber-900 rounded-full"></span>
                            {{ subscription?.plan?.id === plan.id || isLifetimeUser ? 'Lifetime Active' : 'Get Lifetime Access' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
