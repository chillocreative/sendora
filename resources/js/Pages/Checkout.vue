<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    plan: Object,
    billing_cycle: String,
    amount: Number,
    currency: String,
});

const form = useForm({
    plan_id: props.plan.id,
    billing_cycle: props.billing_cycle,
});

const submit = () => {
    form.post(route('payments.initiate'));
};
</script>

<template>
    <Head title="Checkout" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div class="mb-8 text-center pt-4">
            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Finalize Secure Payment</h2>
            <p class="text-slate-500 font-bold mt-2 uppercase text-[10px] tracking-[0.2em]">Transaction Encryption Enabled</p>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-[2rem] p-8 mb-8">
            <div class="flex justify-between items-center mb-4">
                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Selected Tier</span>
                <span class="font-black text-slate-900">{{ plan.name }}</span>
            </div>
            <div class="flex justify-between items-center mb-4">
                <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Billing Interval</span>
                <span class="font-black text-slate-900 capitalize">{{ billing_cycle }}</span>
            </div>
            <div class="border-t border-slate-200/50 my-6"></div>
            <div class="flex justify-between items-center">
                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Investment</span>
                <span class="text-2xl font-black text-[#780116] tracking-tighter">{{ currency }} {{ amount }}</span>
            </div>
        </div>

        <form @submit.prevent="submit">
            <PrimaryButton class="w-full justify-center py-5 bg-[#780116]! hover:bg-[#c32f27]! rounded-2xl! text-[11px]! font-black! uppercase! tracking-[0.2em]! shadow-xl! shadow-red-200! transition-all! transform! active:scale-95!" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ form.processing ? 'Authorizing...' : 'Initialize Payment' }}
            </PrimaryButton>
        </form>
        
        <div class="mt-8 text-center">
             <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Secured by Chip-in Infrastructure</p>
        </div>
    </AuthenticationCard>
</template>
