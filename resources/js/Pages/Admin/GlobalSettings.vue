<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    app_name: props.settings.app_name || 'Sendora',
    app_url: props.settings.app_url || '',
    currency: props.settings.currency || 'USD',
    timezone: props.settings.timezone || 'UTC',
    maintenance_mode: props.settings.maintenance_mode === '1' || props.settings.maintenance_mode === true,
    chip_in_brand_id: props.settings.chip_in_brand_id || '',
    chip_in_private_key: props.settings.chip_in_private_key || '',
    mail_host: props.settings.mail_host || 'smtp-relay.brevo.com',
    mail_port: props.settings.mail_port || '587',
    mail_username: props.settings.mail_username || '',
    mail_password: props.settings.mail_password || '',
    mail_encryption: props.settings.mail_encryption || 'tls',
    mail_from_address: props.settings.mail_from_address || 'hello@sendora.com',
    mail_from_name: props.settings.mail_from_name || 'Sendora',
    openai_api_key: props.settings.openai_api_key || '',
    deepseek_api_key: props.settings.deepseek_api_key || '',
    openai_default_model: props.settings.openai_default_model || 'gpt-4o',
    ai_reply_enabled: props.settings.ai_reply_enabled === '1' || props.settings.ai_reply_enabled === true,
});

const submit = () => {
    form.post(route('admin.settings.save'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Global Settings">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                System Infrastructure
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <!-- General Settings -->
                    <div class="md:col-span-2 space-y-8">
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight">Core Configuration</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Application Name</label>
                                    <input v-model="form.app_name" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Application URL</label>
                                    <input v-model="form.app_url" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Currency Symbol</label>
                                    <select v-model="form.currency" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="MYR">MYR (RM)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Timezone</label>
                                    <select v-model="form.timezone" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-red-50 focus:border-[#780116] transition-all outline-none">
                                        <option value="UTC">UTC</option>
                                        <option value="Asia/Kuala_Lumpur">Asia/Kuala Lumpur</option>
                                        <option value="America/New_York">America/New York</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- AI Configuration -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-green-50 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-green-100/50 transition-colors"></div>
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight relative z-10">AI Configuration</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">OpenAI API Key (For AI Replies & Sendora Commands)</label>
                                    <input v-model="form.openai_api_key" type="password" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-green-50 focus:border-green-500 transition-all outline-none" placeholder="sk-...">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">DeepSeek API Key (For DeepSeek Models)</label>
                                    <input v-model="form.deepseek_api_key" type="password" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-green-50 focus:border-green-500 transition-all outline-none" placeholder="sk-...">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Default AI Model</label>
                                    <select v-model="form.openai_default_model" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-green-50 focus:border-green-500 transition-all outline-none">
                                        <option value="gpt-4o">GPT-4o (Recommended)</option>
                                        <option value="gpt-4o-mini">GPT-4o Mini (Faster)</option>
                                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo (Budget)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">AI Auto-Reply</label>
                                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                        <span class="text-sm font-black text-slate-700">Global Kill Switch</span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" v-model="form.ai_reply_enabled" class="sr-only peer">
                                            <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-[20px] after:w-[22px] after:transition-all peer-checked:bg-green-500 shadow-inner"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400 font-bold mt-4 uppercase tracking-wider ml-1 relative z-10">
                                Controls AI-powered auto-replies, /sendora command parsing, and the Human-Stagger delay logic.
                            </p>
                        </div>

                        <!-- Payment Gateway (Chip-in) -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-50 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-orange-100/50 transition-colors"></div>
                            <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-8 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Payment Gateway</h3>
                                <div class="flex items-center gap-2 px-3 py-1 bg-orange-50 text-[#db7c26] text-[10px] font-black uppercase tracking-widest rounded-full border border-orange-100">
                                    <div class="w-1.5 h-1.5 bg-[#f7b538] rounded-full animate-pulse"></div>
                                    Secure Interface
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-8 relative z-10">
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Brand Identifier</label>
                                    <input v-model="form.chip_in_brand_id" type="text" class="w-full px-5 py-3.5 border-slate-100 rounded-2xl font-black bg-slate-50 focus:bg-white focus:ring-4 focus:ring-orange-50 focus:border-[#db7c26] transition-all outline-none" placeholder="e.g. CI-8829">
                                    <p class="text-[10px] text-slate-400 font-bold mt-2 ml-1 tracking-tight">Your unique Chip-in Brand ID from the merchant portal.</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">RSA Private Key</label>
                                    <textarea v-model="form.chip_in_private_key" rows="4" class="w-full px-5 py-4 border-slate-100 rounded-2xl font-mono text-[10px] bg-slate-50 focus:bg-white focus:ring-4 focus:ring-orange-50 focus:border-[#db7c26] transition-all outline-none leading-relaxed" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
                                    <p class="text-[10px] text-slate-400 font-bold mt-2 ml-1 tracking-tight">PEM format private key for secure transaction signing.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Email Infrastructure (Brevo) -->
                        <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-blue-100/50 transition-colors"></div>
                            <div class="flex items-center justify-between border-b border-slate-50 pb-6 mb-8 relative z-10">
                                <h3 class="text-xl font-black text-slate-900 tracking-tight">Email Infrastructure</h3>
                                <div class="flex items-center gap-2 px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-blue-100">
                                    <div class="w-1.5 h-1.5 bg-blue-400 rounded-full animate-pulse"></div>
                                    Brevo Managed
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 relative z-10">
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">SMTP Host</label>
                                    <input v-model="form.mail_host" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div class="md:col-span-1">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Port</label>
                                    <input v-model="form.mail_port" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">SMTP Username</label>
                                    <input v-model="form.mail_username" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">SMTP Password</label>
                                    <input v-model="form.mail_password" type="password" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Encryption</label>
                                    <input v-model="form.mail_encryption" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">From Name</label>
                                    <input v-model="form.mail_from_name" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">From Email Address</label>
                                    <input v-model="form.mail_from_address" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-slate-100 rounded-2xl font-bold focus:bg-white focus:ring-4 focus:ring-blue-50 focus:border-blue-400 transition-all outline-none">
                                </div>
                            </div>
                        </div>

                         <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100">
                            <h3 class="text-xl font-black text-slate-900 border-b border-slate-50 pb-6 mb-8 tracking-tight">Accessibility Control</h3>
                             <div class="space-y-4">
                                 <div class="flex items-center justify-between p-6 bg-slate-50 rounded-[1.5rem] border border-slate-100 transition-all hover:border-red-100">
                                     <div>
                                         <div class="font-black text-slate-900 tracking-tight">Maintenance Mode</div>
                                         <div class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-1">System status override</div>
                                     </div>
                                     <label class="relative inline-flex items-center cursor-pointer scale-110">
                                        <input type="checkbox" v-model="form.maintenance_mode" class="sr-only peer">
                                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-[20px] after:w-[22px] after:transition-all peer-checked:bg-[#780116] shadow-inner"></div>
                                    </label>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <!-- Side Panel Actions -->
                    <div class="md:col-span-1">
                        <div class="sticky top-8 space-y-4">
                            <div class="bg-white shadow-2xl sm:rounded-[2rem] p-8 border border-slate-100">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Orchestration</h3>
                                <button type="submit" :disabled="form.processing" class="w-full py-4 bg-[#780116] text-white font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-[#c32f27] shadow-xl shadow-red-200 transition-all mb-4 disabled:opacity-50 transform hover:scale-105 active:scale-95">
                                    {{ form.processing ? 'Syncing...' : 'Deploy Changes' }}
                                </button>
                                <button type="button" @click="form.reset()" class="w-full py-4 bg-white border border-slate-100 text-slate-400 font-black text-[11px] uppercase tracking-widest rounded-2xl hover:bg-slate-50 transition-all mb-6">
                                    Revert Configuration
                                </button>
                                
                                <div v-if="form.recentlySuccessful" class="mt-4 p-4 bg-red-50 text-[#780116] text-[10px] font-black uppercase tracking-widest rounded-2xl text-center border border-red-100">
                                    System State Updated!
                                </div>
                            </div>

                            <!-- Helpful Hint -->
                            <div class="p-6 bg-slate-900 rounded-[2rem] text-white/90 shadow-xl">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="px-2 py-1 bg-[#f7b538] text-slate-900 text-[8px] font-black uppercase tracking-widest rounded">Pro Tip</div>
                                </div>
                                <p class="text-[10px] font-bold leading-relaxed opacity-80 uppercase tracking-wider">Deploy changes to propagate these settings across the entire Sendora orchestration architecture instantly.</p>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </AppLayout>
</template>
