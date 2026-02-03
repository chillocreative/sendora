<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    policy: String,
});
</script>

<template>
    <AppLayout title="Privacy Policy">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Privacy Policy
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] p-10 md:p-16 relative">
                    <!-- Decorative background -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 size-64 bg-teal-50 rounded-full blur-3xl opacity-50 -z-0"></div>
                    
                    <div class="relative z-10 prose prose-slate prose-teal max-w-none">
                        <div class="mb-12">
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Privacy Policy</h1>
                            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Last Updated: February 1, 2026</p>
                        </div>

                        <div class="space-y-10 text-slate-600 leading-relaxed">
                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">1</span>
                                    Information We Collect
                                </h2>
                                <p>We collect information you provide directly to us when you create an account, use our services, or communicate with us. This may include your name, email address, phone number, and WhatsApp account details necessary for message blasting.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">2</span>
                                    How We Use Your Information
                                </h2>
                                <p>We use the information we collect to provide, maintain, and improve our services, including sending messages via your connected WhatsApp accounts, processing transactions, and sending technical notices and support messages.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">3</span>
                                    Data Security
                                </h2>
                                <p>We take reasonable measures to help protect information about you from loss, theft, misuse, and unauthorized access, disclosure, alteration, and destruction. Your WhatsApp session data is stored securely and never shared with third parties.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">4</span>
                                    Your Choices
                                </h2>
                                <p>You may update or correct your account information at any time by logging into your account settings. You can also disconnect your WhatsApp account or delete your account entirely at any moment.</p>
                            </section>

                            <section class="bg-slate-50 p-8 rounded-3xl border border-slate-100 mt-12 text-sm">
                                <h3 class="font-black text-slate-800 mb-2 uppercase tracking-tight">Contact Us</h3>
                                <p class="mb-0 text-slate-500">If you have any questions about this Privacy Policy, please contact us at <a href="mailto:privacy@Sendora.com" class="text-teal-600 font-bold hover:underline">privacy@Sendora.com</a>.</p>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
