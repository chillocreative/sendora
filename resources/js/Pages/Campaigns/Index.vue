<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    campaigns: Object,
    canCreate: Boolean,
});

const showSuccessModal = ref(false);
const showFailedModal = ref(false);

const stopCampaign = (id) => {
    if (confirm('Stop this campaign? This will cancel all pending messages.')) {
        router.post(route('campaigns.stop', id));
    }
};

const deleteCampaign = (id) => {
    if (confirm('Permanently delete this campaign and all its message records?')) {
        router.delete(route('campaigns.destroy', id));
    }
};

const page = usePage();

// Check for flash status on load
if (page.props.jetstream.flash?.bannerStyle === 'success') {
    showSuccessModal.value = true;
} else if (page.props.jetstream.flash?.bannerStyle === 'danger') {
    showFailedModal.value = true;
}
</script>

<template>
    <AppLayout title="Campaigns">
        <!-- Success Modal -->
        <div v-if="showSuccessModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
            <div class="bg-white rounded-[3rem] p-10 max-w-sm w-full text-center shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-300">
                <div class="size-20 bg-red-50 text-[#780116] rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-100">
                    <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Payment Successful!</h3>
                <p class="text-slate-500 font-medium mb-8">Your account has been upgraded. You can now start creating campaigns.</p>
                <button @click="showSuccessModal = false" class="w-full py-4 bg-[#780116] text-white rounded-2xl font-black text-sm tracking-widest uppercase hover:bg-[#c32f27] transition shadow-xl shadow-red-100">
                    Awesome!
                </button>
            </div>
        </div>

        <!-- Failed Modal -->
        <div v-if="showFailedModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
            <div class="bg-white rounded-[3rem] p-10 max-w-sm w-full text-center shadow-2xl border border-slate-100 animate-in fade-in zoom-in duration-300">
                <div class="size-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-100">
                    <svg class="size-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h3 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Payment Failed</h3>
                <p class="text-slate-500 font-medium mb-8">Something went wrong with your transaction. Please try again or contact support.</p>
                <button @click="showFailedModal = false" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black text-sm tracking-widest uppercase hover:bg-slate-800 transition shadow-xl shadow-slate-200">
                    Try Again
                </button>
            </div>
        </div>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Campaigns
            </h2>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Empty State / Placeholder if no campaigns -->
                <div v-if="campaigns.data.length === 0" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 text-center">
                    <div class="inline-flex p-4 rounded-full bg-red-50 text-[#780116] mb-4">
                         <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-2">Create your first campaign</h3>
                    <p class="text-slate-500 max-w-md mx-auto mb-8">Send bulk messages to your contacts easily. Organize, schedule, and track your campaigns.</p>
                    
                    <Link v-if="canCreate" :href="route('campaigns.create')" class="inline-flex items-center px-6 py-3 bg-[#780116] text-white font-bold rounded-xl shadow-lg shadow-red-100 hover:bg-[#c32f27] transition">
                        Create Campaign
                    </Link>
                    <div v-else class="p-8 bg-orange-50 border border-orange-100 rounded-3xl max-w-sm mx-auto shadow-sm">
                        <p class="text-sm text-orange-700 font-bold mb-4">Upgrade to Pro to unlock Campaigns</p>
                        <Link :href="route('subscription.show')" class="inline-flex items-center px-6 py-3 bg-orange-500 text-white rounded-xl text-sm font-black hover:bg-orange-600 transition shadow-lg shadow-orange-100">
                            Upgrade to Pro
                        </Link>
                    </div>
                </div>

                <!-- Campaign List -->
                <div v-else class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="font-bold text-slate-700">All Campaigns</h3>
                         <Link v-if="canCreate" :href="route('campaigns.create')" class="flex items-center gap-2 px-4 py-2 bg-[#780116] text-white rounded-xl text-sm font-bold hover:bg-[#c32f27] transition shadow-lg shadow-red-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            New Campaign
                        </Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50">
                                <tr class="text-xs text-slate-500 uppercase tracking-wider">
                                    <th class="px-6 py-4 font-bold">Campaign</th>
                                    <th class="px-6 py-4 font-bold">Schedule</th>
                                    <th class="px-6 py-4 font-bold text-center">Results</th>
                                    <th class="px-6 py-4 font-bold text-center">Status</th>
                                    <th class="px-6 py-4 font-bold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="campaign in campaigns.data" :key="campaign.id" class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-black text-slate-900 text-base">{{ campaign.name || 'Untitled Campaign' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2 text-slate-500 font-medium">
                                            <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <div class="flex flex-col">
                                                <span>{{ campaign.scheduled_at ? new Date(campaign.scheduled_at).toLocaleDateString() : 'Immediate' }}</span>
                                                <span v-if="campaign.scheduled_at" class="text-[10px] text-slate-400">{{ new Date(campaign.scheduled_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-center gap-1.5">
                                            <div class="flex items-center gap-3">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[#780116] font-black text-lg leading-none">{{ campaign.success_count }}</span>
                                                    <span class="text-[9px] uppercase tracking-tighter text-slate-400 font-bold">Sent</span>
                                                </div>
                                                <div class="w-px h-6 bg-slate-100"></div>
                                                <div class="flex flex-col items-center">
                                                    <span class="text-red-500 font-black text-lg leading-none">{{ campaign.failure_count }}</span>
                                                    <span class="text-[9px] uppercase tracking-tighter text-slate-400 font-bold">Failed</span>
                                                </div>
                                            </div>
                                            <div class="w-24 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-[#f7b538] rounded-full" :style="{ width: (campaign.total_count > 0 ? (campaign.success_count / campaign.total_count) * 100 : 0) + '%' }"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm border whitespace-nowrap"
                                              :class="{
                                                'bg-amber-50 text-amber-600 border-amber-100': campaign.status === 'pending' || campaign.status === 'scheduled',
                                                'bg-red-50 text-[#780116] border-red-100': campaign.status === 'completed',
                                                'bg-orange-50 text-[#db7c26] border-orange-100': campaign.status === 'processing',
                                                'bg-slate-50 text-slate-400 border-slate-200': campaign.status === 'cancelled',
                                                'bg-red-50 text-red-600 border-red-100': campaign.status === 'failed'
                                              }">
                                            {{ campaign.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button v-if="['pending', 'scheduled', 'processing'].includes(campaign.status)" 
                                                    @click="stopCampaign(campaign.id)"
                                                    class="p-2.5 bg-white text-red-500 rounded-xl border border-red-50 hover:bg-red-50 transition shadow-sm"
                                                    title="Stop Campaign">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9"/></svg>
                                            </button>
                                            
                                            <Link :href="route('campaigns.edit', campaign.id)" 
                                                  class="p-2.5 bg-white text-slate-600 rounded-xl border border-slate-100 hover:bg-slate-50 transition shadow-sm"
                                                  title="Edit Campaign">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </Link>

                                            <button @click="deleteCampaign(campaign.id)" 
                                                    class="p-2.5 bg-white text-slate-400 rounded-xl border border-slate-100 hover:text-red-600 hover:border-red-100 transition shadow-sm"
                                                    title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
