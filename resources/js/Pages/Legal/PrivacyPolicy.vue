<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Footer from '@/Components/Footer.vue';
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="min-h-screen flex flex-col bg-slate-50">
        <!-- Simple Public Header -->
        <header class="bg-white border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <Link :href="'/'" class="flex items-center">
                        <div class="w-10 h-10 bg-[#780116] rounded-xl flex items-center justify-center shadow-lg shadow-red-200">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <span class="ml-3 text-2xl font-black tracking-tight text-slate-900">Sendora</span>
                    </Link>
                    <div class="flex items-center gap-4">
                        <Link :href="'/'" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Home</Link>
                        <Link :href="'/faq'" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">FAQ</Link>
                        <Link :href="'/terms'" class="text-sm font-semibold text-slate-600 hover:text-[#780116] transition">Terms</Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] p-10 md:p-16 relative">
                    <!-- Decorative background -->
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 size-64 bg-teal-50 rounded-full blur-3xl opacity-50 -z-0"></div>

                    <div class="relative z-10 prose prose-slate prose-teal max-w-none">
                        <div class="mb-12">
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-4">Privacy Policy</h1>
                            <p class="text-slate-400 font-bold uppercase tracking-widest text-xs">Last Updated: April 19, 2026</p>
                        </div>

                        <div class="space-y-10 text-slate-600 leading-relaxed">
                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">1</span>
                                    Information We Collect
                                </h2>
                                <p class="mb-4">We collect information you provide directly to us when you create an account, use our services, or communicate with us. This includes your name, email address, phone number, and WhatsApp account details necessary for sending reminders and messages.</p>
                                <p class="mb-2"><strong class="text-slate-800">Google user data we access (only if you choose to connect Google Calendar):</strong></p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li><strong>Google account email address</strong> — accessed via the <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">https://www.googleapis.com/auth/userinfo.email</code> scope, used to identify which Google account is connected.</li>
                                    <li><strong>Google Calendar events</strong> — accessed via the <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">https://www.googleapis.com/auth/calendar.events</code> scope. We read event title, description, location, start/end times, and event IDs from your calendars.</li>
                                    <li><strong>Contact birthdays</strong> — read from your Google Contacts birthday calendar (<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">#contacts@group.v.calendar.google.com</code>) so we can send you proactive birthday reminders on WhatsApp.</li>
                                    <li><strong>OAuth access and refresh tokens</strong> — issued by Google after you grant consent, used to call the Google Calendar API on your behalf.</li>
                                </ul>
                                <p class="mt-4">We do not access Gmail, Google Drive, Google Photos, Google Sheets, or any other Google service.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">2</span>
                                    How We Use Your Information
                                </h2>
                                <p class="mb-4">We use the information we collect to provide, maintain, and improve our services, including sending messages via your connected WhatsApp accounts, processing transactions, and sending technical notices and support messages.</p>
                                <p class="mb-2"><strong class="text-slate-800">How we use Google user data:</strong></p>
                                <ul class="list-disc pl-6 space-y-1">
                                    <li>Sync your Google Calendar events into Sendora so we can send you WhatsApp reminders before each event starts (5, 15, or 30 minutes before, based on your settings).</li>
                                    <li>Create, update, or delete events in your Google Calendar when you create, update, or delete the corresponding reminders inside Sendora.</li>
                                    <li>Look up upcoming birthdays from your Google Contacts birthday calendar to send you proactive birthday reminders on WhatsApp.</li>
                                    <li>Display your connected Google account email in your dashboard so you know which account is linked.</li>
                                </ul>
                                <p class="mt-4"><strong class="text-slate-800">We do not</strong> use Google user data for advertising, do not sell it, do not share it with any third party, and do not use it to develop, improve, or train generalized AI or machine learning models.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">3</span>
                                    Google API Services User Data Policy
                                </h2>
                                <p>Sendora's use and transfer to any other app of information received from Google APIs will adhere to the <a href="https://developers.google.com/terms/api-services-user-data-policy" target="_blank" rel="noopener" class="text-teal-600 font-bold hover:underline">Google API Services User Data Policy</a>, including the Limited Use requirements.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">4</span>
                                    Data Sharing
                                </h2>
                                <p>We do not sell, rent, trade, transfer, or share your Google user data with any third party. Google user data is processed solely on our infrastructure to deliver the calendar reminder feature you signed up for. We do not derive aggregated, anonymized, or statistical datasets from Google user data. Our hosting provider processes data only as part of standard infrastructure operations (storage at rest and transmission), under contractual confidentiality obligations.</p>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">5</span>
                                    Data Storage &amp; Protection
                                </h2>
                                <ul class="list-disc pl-6 space-y-2">
                                    <li>OAuth access tokens and refresh tokens issued by Google are <strong>encrypted at rest</strong> using AES-256-CBC (Laravel's encrypted attribute cast, keyed by the application's <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded">APP_KEY</code>) before being written to our MySQL database.</li>
                                    <li>All communication between your browser, Sendora, and Google is encrypted in transit using HTTPS / TLS.</li>
                                    <li>OAuth tokens are never written to log files, never exposed in URLs, and never transmitted to any third party.</li>
                                    <li>Calendar event data synced into Sendora is stored in our database with the same access controls as the rest of your account data — only you (and authorized administrators for support purposes) can access it.</li>
                                    <li>Access to production systems is restricted to authorized personnel with multi-factor authentication.</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">6</span>
                                    Data Retention &amp; Deletion
                                </h2>
                                <p class="mb-4">We retain your Google user data only as long as you keep Google Calendar connected to Sendora. You can revoke access and delete your Google data at any time:</p>
                                <ul class="list-disc pl-6 space-y-2">
                                    <li><strong>In-app:</strong> Click the <strong>Disconnect</strong> button on the Google Calendar page in your Sendora dashboard. This immediately calls Google's token revocation endpoint and deletes the stored connection record (including your access token, refresh token, and Google email) from our database.</li>
                                    <li><strong>From your Google account:</strong> Visit <a href="https://myaccount.google.com/permissions" target="_blank" rel="noopener" class="text-teal-600 font-bold hover:underline">myaccount.google.com/permissions</a> and remove Sendora's access. Our cached tokens will fail on next use and the connection will be cleaned up.</li>
                                    <li><strong>Calendar events synced into Sendora</strong> (which are kept as reminders so we can notify you on WhatsApp) are deleted when you delete the reminder, when you disconnect Google Calendar and request a full purge, or when you delete your Sendora account.</li>
                                    <li><strong>Full data deletion request:</strong> Email <a href="mailto:chillocreative@gmail.com" class="text-teal-600 font-bold hover:underline">chillocreative@gmail.com</a> from the address tied to your account. We will permanently delete all your data — including any Google user data we hold — within 30 days.</li>
                                    <li><strong>Account deletion:</strong> Deleting your Sendora account removes all Google-derived data within 30 days.</li>
                                </ul>
                            </section>

                            <section>
                                <h2 class="text-2xl font-black text-slate-800 mb-4 flex items-center gap-3">
                                    <span class="size-8 bg-teal-100 text-teal-600 rounded-lg flex items-center justify-center text-sm">7</span>
                                    Your Choices
                                </h2>
                                <p>You may update or correct your account information at any time by logging into your account settings. You can disconnect your WhatsApp account, disconnect Google Calendar, or delete your account entirely at any moment.</p>
                            </section>

                            <section class="bg-slate-50 p-8 rounded-3xl border border-slate-100 mt-12 text-sm">
                                <h3 class="font-black text-slate-800 mb-2 uppercase tracking-tight">Contact Us</h3>
                                <p class="mb-0 text-slate-500">If you have any questions about this Privacy Policy, or wish to request access to or deletion of your data, please contact us at <a href="mailto:chillocreative@gmail.com" class="text-teal-600 font-bold hover:underline">chillocreative@gmail.com</a>.</p>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>
