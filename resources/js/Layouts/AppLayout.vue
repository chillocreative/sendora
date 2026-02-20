<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const page = usePage();
const showingNavigationDropdown = ref(false);
const showMobileMenu = ref(false);

const isAdmin = computed(() => page.props.auth.user?.is_admin || false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};

const adminNavigation = [
    { name: 'Overview', route: 'dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' }, 
    { name: 'Financials', route: 'admin.financials', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { name: 'User Management', route: 'admin.users', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' }, 
    { name: 'Subscription Plans', route: 'admin.plans', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 011.414.586l4 4a1 1 0 01.586 1.414V19a2 2 0 01-2 2z' }, 
    { name: 'Server Health', route: 'admin.server', icon: 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01' }, 
    { name: 'Global Settings', route: 'admin.settings', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }, 
    { name: 'System WhatsApp', route: 'admin.whatsapp', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { name: 'Support Tickets', route: 'admin.tickets', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
];

const userNavigation = [
    { name: 'Dashboard', route: 'dashboard', icon: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' },
    { name: 'WhatsApp Manager', route: 'whatsapp.index', icon: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z' },
    { name: 'WhatsApp Warmer', route: 'warmer.index', icon: 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z' },
    { name: 'Contacts', route: 'contacts.index', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { name: 'Contact Books', route: 'contact-books.index', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { name: 'Campaigns', route: 'campaigns.index', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { name: 'Send Test Message', route: 'test-message.index', icon: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' },
    { name: 'AI Playbooks', route: 'playbooks.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', feature: 'auto_reply' },
    { name: 'Conversations', route: 'conversations.index', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', feature: 'auto_reply' },
    { name: 'Tickets', route: 'tickets.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', badgeKey: 'unread_tickets' },
    { name: 'Subscription', route: 'subscription.show', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' },
    { name: 'API Tokens', route: 'api-tokens.index', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z', feature: 'api_access' },
    { name: 'API Documentation', route: 'api-docs', icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', feature: 'api_access' },
];

const navigation = computed(() => {
    if (page.props.auth.user.is_admin) return adminNavigation;

    const features = page.props.auth.user.active_subscription?.plan?.limits?.features || {};
    
    return userNavigation.filter(item => {
        if (!item.feature) return true;
        return features[item.feature] === true;
    });
});

</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-slate-50 flex">
            
            <!-- Sidebar (Desktop) -->
            <aside class="hidden md:flex flex-col w-64 bg-slate-900 text-white fixed h-full z-20 overflow-y-auto">
                <!-- Branding -->
                <div class="h-24 px-4 flex items-center border-b border-white/5 bg-gradient-to-b from-slate-800/40 to-transparent">
                    <Link :href="route('dashboard')" class="flex items-center gap-3 p-2 rounded-2xl hover:bg-white/5 transition-colors group w-full">
                        <ApplicationMark class="scale-90 origin-left" />
                        <div class="flex flex-col">
                            <span class="text-xl font-black tracking-tighter text-white leading-none">Sendora</span>
                            <span class="text-[8px] font-black text-[#f7b538] uppercase tracking-[0.3em] mt-1 opacity-70 group-hover:opacity-100 transition-opacity">Blaster Pro</span>
                        </div>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                    <template v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.route ? route(item.route) : '#'"
                            :class="[
                                (item.route && route().current(item.route))
                                ? 'bg-[#780116] text-white shadow-lg shadow-black/20'
                                : 'text-slate-400 hover:bg-white/5 hover:text-white',
                                'group flex items-center px-4 py-3 text-sm font-bold rounded-xl transition-all duration-200'
                            ]"
                        >
                            <svg class="mr-3 flex-shrink-0 h-5 w-5" :class="(item.route && route().current(item.route)) ? 'text-white' : 'text-slate-500 group-hover:text-[#f7b538]'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                            <span v-if="item.badgeKey && $page.props.auth.user[item.badgeKey] > 0" class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-black text-white bg-[#c32f27] rounded-full">
                                {{ $page.props.auth.user[item.badgeKey] }}
                            </span>
                        </Link>
                    </template>
                </nav>

            </aside>

            <!-- Mobile Menu Overlay -->
            <div v-if="showMobileMenu" class="fixed inset-0 z-40 bg-slate-900/90 backdrop-blur-sm md:hidden" @click="showMobileMenu = false"></div>
            
            <!-- Mobile Sidebar -->
            <div :class="showMobileMenu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 shadow-2xl transition-transform duration-500 md:hidden flex flex-col">
                 <div class="h-24 px-4 flex items-center justify-between border-b border-white/5 bg-gradient-to-b from-slate-800/40 to-transparent">
                    <Link :href="route('dashboard')" class="flex flex-1 items-center gap-3 p-2 rounded-2xl hover:bg-white/5 transition-colors group" @click="showMobileMenu = false">
                        <ApplicationMark class="scale-90 origin-left" />
                        <div class="flex flex-col">
                            <span class="text-xl font-black tracking-tighter text-white leading-none">Sendora</span>
                            <span class="text-[8px] font-black text-[#f7b538] uppercase tracking-[0.3em] mt-1 opacity-70 group-hover:opacity-100 transition-opacity">Blaster Pro</span>
                        </div>
                    </Link>
                    <button @click="showMobileMenu = false" class="ml-2 size-10 flex items-center justify-center rounded-xl bg-slate-800/50 text-slate-400 hover:text-white transition-all active:scale-90">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                     <template v-for="item in navigation" :key="item.name">
                        <Link
                            :href="item.route ? route(item.route) : '#'"
                            class="group flex items-center px-4 py-4 text-sm font-bold rounded-xl transition-all duration-200"
                            :class="(item.route && route().current(item.route)) ? 'bg-[#780116] text-white shadow-lg shadow-black/40' : 'text-slate-400 hover:bg-white/5 hover:text-white'"
                            @click="showMobileMenu = false"
                        >
                            <svg class="mr-4 h-5 w-5" :class="(item.route && route().current(item.route)) ? 'text-white' : 'text-slate-500 group-hover:text-[#f7b538]'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" /></svg>
                            {{ item.name }}
                            <span v-if="item.badgeKey && $page.props.auth.user[item.badgeKey] > 0" class="ml-auto inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-black text-white bg-[#c32f27] rounded-full">
                                {{ $page.props.auth.user[item.badgeKey] }}
                            </span>
                        </Link>
                    </template>
                </nav>
            </div>

            <!-- Content Area -->
            <div class="flex-1 flex flex-col md:pl-64 min-h-screen transition-all duration-300">
                
                <!-- Helper Header for Mobile & Breadcrumbs/Actions -->
                <header class="h-20 bg-white/80 backdrop-blur-md border-b border-slate-200 sticky top-0 z-30 px-4 sm:px-8 flex items-center justify-between">
                    <div class="flex items-center flex-1">
                        <button @click="showMobileMenu = true" class="md:hidden mr-4 text-slate-500 hover:text-slate-700">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        
                        <div class="flex-1">
                            <slot v-if="$slots.header" name="header" />
                            <h2 v-else class="font-bold text-xl text-slate-800 leading-tight">
                                {{ title }}
                            </h2>
                        </div>
                    </div>

                    <!-- Right Header Actions (User Dropdown) -->
                    <div class="flex items-center gap-4">
                        <!-- Settings Dropdown -->
                         <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-800 transition">
                                        <span class="hidden lg:block text-slate-600">{{ $page.props.auth.user.name }}</span>
                                        <img class="size-8 rounded-full object-cover border border-slate-200" :src="$page.props.auth.user.profile_photo_url" alt="">
                                        <svg class="hidden sm:block size-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
                                        Manage Account
                                    </div>

                                    <DropdownLink :href="route('profile.show')">
                                        Profile Settings
                                    </DropdownLink>

                                    <DropdownLink v-if="!isAdmin" :href="route('subscription.show')">
                                        Subscription
                                    </DropdownLink>

                                    <DropdownLink v-if="!isAdmin" :href="route('api-tokens.index')">
                                        API Tokens
                                    </DropdownLink>

                                    <div class="border-t border-slate-100" />

                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button" class="text-red-600 hover:bg-red-50">
                                            Log Out
                                        </DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-4 sm:p-8 overflow-y-auto">
                    <slot />
                </main>

                <!-- Admin Footer -->
                <footer class="bg-white border-t border-slate-100 py-10 px-4 sm:px-8 mt-auto">
                    <div class="flex flex-col gap-6 md:flex-row justify-between items-center text-[10px] sm:text-[11px] text-slate-400 font-black uppercase tracking-[0.2em]">
                        <div class="flex items-center gap-4">
                            <ApplicationMark class="scale-[0.6] origin-center opacity-40 hover:opacity-100 transition-opacity" />
                            <span class="mt-0.5">&copy; 2026 Sendora. <span class="hidden sm:inline">All rights reserved.</span></span>
                        </div>
                        <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-2">
                            <Link :href="route('privacy.policy')" class="hover:text-[#780116] transition-colors">Privacy Policy</Link>
                            <span class="text-slate-900">|</span>
                            <Link :href="route('terms.show')" class="hover:text-[#780116] transition-colors">Terms of Service</Link>
                        </div>
                    </div>
                </footer>

            </div>
        </div>
    </div>
</template>
