<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Terms of Service">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Terms of Service
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] p-10 md:p-16 relative">
                    <!-- Decorative background -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 size-64 bg-teal-50 rounded-full blur-3xl opacity-50 -z-0"></div>
                    
                    <div class="relative z-10 prose prose-slate prose-teal max-w-none">
                        <div class="mb-12">
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Terms of Service</h1>
                            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Effective Date: February 1, 2026</p>
                        </div>

                        <div class="space-y-10 text-slate-600 leading-relaxed">
                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">1</span>
                                    Acceptance of Terms
                                </h2>
                                <p>By accessing or using Sendora (the "Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the Service.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">2</span>
                                    Description of Service
                                </h2>
                                <p>Sendora provides WhatsApp marketing and automation tools. You understand that you are responsible for how you use these tools and for complying with WhatsApp's own Terms of Service.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">3</span>
                                    User Conduct
                                </h2>
                                <p>You agree not to use the Service for any unlawful purpose or for the promotion of illegal activities. You specifically agree not to use the Service for sending unsolicited spam messages or violating anti-spam laws.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">4</span>
                                    Account Termination
                                </h2>
                                <p>We reserve the right to suspend or terminate your account at our discretion, without notice, for conduct that we believe violates these Terms of Service or is harmful to other users of the Service, us, or third parties.</p>
                            </section>

                            <section class="bg-amber-50 p-8 rounded-3xl border border-amber-100 mt-12 text-sm text-amber-900">
                                <h3 class="font-black mb-2 uppercase tracking-tight">Important Disclaimer</h3>
                                <p class="mb-0">Sendora is not affiliated with WhatsApp Inc. Use our automated tools responsibly to avoid account bans from WhatsApp.</p>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
