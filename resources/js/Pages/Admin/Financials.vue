<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    stats: Object,
    transactions: Array,
    users: Array,
    plans: Array,
    monthlyStats: Object,
    filterYears: Array,
});

// Helper
const formatNumber = (num) => {
    return Number(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

// Filter Data
const months = [
    'January', 'February', 'March', 'April', 'May', 'June', 
    'July', 'August', 'September', 'October', 'November', 'December'
];

const filters = reactive({
    month: props.monthlyStats?.month || new Date().getMonth() + 1,
    year: props.monthlyStats?.year || new Date().getFullYear(),
});

// Watch filters
watch(filters, (newFilters) => {
    router.visit(route('admin.financials'), {
        data: newFilters,
        preserveState: true,
        preserveScroll: true,
        only: ['monthlyStats'],
    });
}, { deep: true });

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// Form data
const createForm = reactive({
    user_id: '',
    subscription_plan_id: '',
    amount: '',
    currency: props.stats.currency,
    status: 'pending',
    reference_id: '',
    payment_method: '',
    processing: false,
});

const editForm = reactive({
    id: null,
    user_id: '',
    subscription_plan_id: '',
    amount: '',
    currency: props.stats.currency,
    status: 'pending',
    reference_id: '',
    payment_method: '',
    processing: false,
});

const deleteId = ref(null);

// Functions
const openCreateModal = () => {
    resetCreateForm();
    showCreateModal.value = true;
};

const openEditModal = (transaction) => {
    editForm.id = transaction.id;
    editForm.user_id = transaction.user_id;
    editForm.subscription_plan_id = transaction.subscription_plan_id;
    editForm.amount = transaction.raw_amount;
    editForm.currency = transaction.currency;
    editForm.status = transaction.status;
    editForm.reference_id = transaction.reference_id || '';
    editForm.payment_method = transaction.payment_method || '';
    showEditModal.value = true;
};

const openDeleteModal = (id) => {
    deleteId.value = id;
    showDeleteModal.value = true;
};

const resetCreateForm = () => {
    createForm.user_id = '';
    createForm.subscription_plan_id = '';
    createForm.amount = '';
    createForm.currency = props.stats.currency;
    createForm.status = 'pending';
    createForm.reference_id = '';
    createForm.payment_method = '';
};

const createTransaction = () => {
    createForm.processing = true;
    router.post(route('admin.transactions.store'), createForm, {
        onSuccess: () => {
            showCreateModal.value = false;
            resetCreateForm();
        },
        onFinish: () => {
            createForm.processing = false;
        },
    });
};

const updateTransaction = () => {
    editForm.processing = true;
    router.put(route('admin.transactions.update', editForm.id), {
        user_id: editForm.user_id,
        subscription_plan_id: editForm.subscription_plan_id,
        amount: editForm.amount,
        currency: editForm.currency,
        status: editForm.status,
        reference_id: editForm.reference_id,
        payment_method: editForm.payment_method,
    }, {
        onSuccess: () => {
            showEditModal.value = false;
        },
        onFinish: () => {
            editForm.processing = false;
        },
    });
};

const deleteTransaction = () => {
    router.delete(route('admin.transactions.destroy', deleteId.value), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deleteId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout title="Financials">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                Financial Ecosystem
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-[#780116]/5 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-[#780116]/10 transition-colors"></div>
                         <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Total Revenue</div>
                         <div class="text-4xl font-black text-slate-900 leading-none">{{ stats.currency }} {{ stats.total_revenue }}</div>
                         <div class="text-[11px] font-bold text-[#db7c26] mt-4 flex items-center bg-orange-50 w-fit px-3 py-1 rounded-full border border-orange-100">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Lifetime Revenue
                         </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-[#f7b538]/5 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-[#f7b538]/10 transition-colors"></div>
                         <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">MRR (Estimated)</div>
                         <div class="text-4xl font-black text-slate-900 leading-none">{{ stats.currency }} {{ stats.mrr }}</div>
                         <div class="text-[11px] font-bold text-[#780116] mt-4 flex items-center bg-red-50 w-fit px-3 py-1 rounded-full border border-red-100">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            Last 30 Days
                         </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] p-8 border border-slate-100 relative group">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-slate-100 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-slate-200 transition-colors"></div>
                         <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Active Subscribers</div>
                         <div class="text-4xl font-black text-slate-900 leading-none">{{ stats.active_subscribers }}</div>
                         <div class="text-[11px] font-bold text-slate-400 mt-4 flex items-center bg-slate-50 w-fit px-3 py-1 rounded-full border border-slate-100">
                            Current Active Plans
                         </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] mb-8 border border-slate-100">
                    <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Recent Transactions</h3>
                        <button 
                            @click="openCreateModal"
                            class="bg-[#780116] text-white px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-[#c32f27] transition shadow-lg shadow-red-200 flex items-center transform hover:scale-105 active:scale-95"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Transaction
                        </button>
                    </div>
                    <div class="p-6">
                        <div v-if="transactions.length === 0" class="text-center py-8 text-slate-500">
                            No transactions found.
                        </div>
                        <table v-else class="w-full text-left">
                            <thead>
                                <tr class="text-[10px] text-slate-400 font-black uppercase tracking-[0.15em] border-b border-slate-100">
                                    <th class="px-8 pb-4 font-black">Customer</th>
                                    <th class="pb-4 font-black">Plan</th>
                                    <th class="pb-4 font-black">Date</th>
                                    <th class="pb-4 font-black">Amount</th>
                                    <th class="pb-4 font-black">Status</th>
                                    <th class="px-8 pb-4 font-black text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <tr v-for="tx in transactions" :key="tx.id" class="border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="font-black text-slate-900">{{ tx.customer }}</div>
                                    </td>
                                    <td class="py-5 text-slate-600 font-bold uppercase text-[11px] tracking-wider">{{ tx.plan }}</td>
                                    <td class="py-5 text-slate-400 font-bold text-xs">{{ tx.date }}</td>
                                    <td class="py-5 font-black text-slate-900">{{ stats.currency }} {{ tx.amount }}</td>
                                    <td class="py-5">
                                        <span class="px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest"
                                            :class="tx.status === 'paid' ? 'bg-red-50 text-[#780116] border border-red-100' : 'bg-slate-50 text-slate-400 border border-slate-100'"
                                        >
                                            {{ tx.status }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <button 
                                                @click="openEditModal(tx)"
                                                class="p-2.5 rounded-xl bg-orange-50 text-[#db7c26] hover:bg-[#db7c26] hover:text-white transition-all transform hover:scale-110 active:scale-95 shadow-sm"
                                                title="Edit"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button 
                                                @click="openDeleteModal(tx.id)"
                                                class="p-2.5 rounded-xl bg-red-50 text-[#780116] hover:bg-[#780116] hover:text-white transition-all transform hover:scale-110 active:scale-95 shadow-sm"
                                                title="Delete"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Performance Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2.5rem] border border-slate-100">
                    <div class="p-10">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">
                            <!-- Header & Filters -->
                            <div class="flex items-center gap-8">
                                <div class="bg-[#780116] p-4 rounded-2xl shadow-xl shadow-red-200 transform -rotate-6">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Performance Tracking</h3>
                                    <div class="flex gap-3 mt-3">
                                        <select 
                                            v-model="filters.month" 
                                            class="bg-slate-50 border border-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-xl focus:ring-[#780116] focus:border-[#780116] block py-2.5 px-4 outline-none"
                                        >
                                            <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}</option>
                                        </select>
                                        <select 
                                            v-model="filters.year" 
                                            class="bg-slate-50 border border-slate-100 text-slate-600 text-[11px] font-black uppercase tracking-widest rounded-xl focus:ring-[#780116] focus:border-[#780116] block py-2.5 px-4 outline-none"
                                        >
                                            <option v-for="year in filterYears" :key="year" :value="year">{{ year }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtered Stats -->
                            <div class="flex-1 border-t md:border-t-0 md:border-l border-slate-100 pt-8 md:pt-0 md:pl-10">
                                <div class="grid grid-cols-2 gap-10">
                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Filtered Revenue</div>
                                        <div class="text-3xl font-black text-slate-900 leading-none">{{ stats.currency }} {{ formatNumber(monthlyStats.revenue) }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Transactions</div>
                                        <div class="text-3xl font-black text-slate-900 leading-none">{{ monthlyStats.count }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2.5rem] shadow-2xl max-w-2xl w-full border border-slate-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-xl font-black text-slate-900">Add New Transaction</h3>
                </div>
                <form @submit.prevent="createTransaction" class="p-8 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Customer</label>
                            <select v-model="createForm.user_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#780116] focus:ring-4 focus:ring-red-100 outline-none font-bold text-sm">
                                <option value="">Select Customer</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1">Plan</label>
                            <select v-model="createForm.subscription_plan_id" required class="w-full px-4 py-3 bg-slate-50 border border-slate-100 rounded-xl focus:border-[#780116] focus:ring-4 focus:ring-red-100 outline-none font-bold text-sm">
                                <option value="">Select Plan</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Amount</label>
                            <input v-model="createForm.amount" type="number" step="0.01" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100 outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Currency</label>
                            <input v-model="createForm.currency" type="text" maxlength="3" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100 outline-none" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Status</label>
                            <select v-model="createForm.status" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100 outline-none">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                            <input v-model="createForm.payment_method" type="text" class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100 outline-none" placeholder="e.g., Credit Card" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Reference ID</label>
                        <input v-model="createForm.reference_id" type="text" class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100 outline-none" placeholder="Optional" />
                    </div>
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="showCreateModal = false" class="px-6 py-3 rounded-xl border border-slate-100 text-slate-400 font-black text-[11px] uppercase tracking-widest hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" :disabled="createForm.processing" class="px-8 py-3 rounded-xl bg-[#780116] text-white font-black text-[11px] uppercase tracking-widest hover:bg-[#c32f27] transition shadow-lg shadow-red-200 disabled:opacity-50">
                            {{ createForm.processing ? 'Syncing...' : 'Add Transaction' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-xl font-bold text-slate-900">Edit Transaction</h3>
                </div>
                <form @submit.prevent="updateTransaction" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Customer</label>
                            <select v-model="editForm.user_id" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none">
                                <option value="">Select Customer</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Plan</label>
                            <select v-model="editForm.subscription_plan_id" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none">
                                <option value="">Select Plan</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Amount</label>
                            <input v-model="editForm.amount" type="number" step="0.01" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Currency</label>
                            <input v-model="editForm.currency" type="text" maxlength="3" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Status</label>
                            <select v-model="editForm.status" required class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                            <input v-model="editForm.payment_method" type="text" class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none" placeholder="e.g., Credit Card" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Reference ID</label>
                        <input v-model="editForm.reference_id" type="text" class="w-full px-3 py-2 border-2 border-slate-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 outline-none" placeholder="Optional" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-lg border-2 border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded-lg bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold hover:from-indigo-600 hover:to-purple-700 transition disabled:opacity-50">
                            {{ editForm.processing ? 'Updating...' : 'Update Transaction' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full border border-slate-100 overflow-hidden">
                <div class="p-10 text-center">
                    <div class="flex items-center justify-center w-20 h-20 rounded-[1.5rem] bg-red-50 text-[#780116] mx-auto mb-6 shadow-xl shadow-red-200/50 transform -rotate-12">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Remove Transaction</h3>
                    <p class="text-slate-500 font-medium mb-10 leading-relaxed text-sm">Are you certain you want to delete this record? This entry will be permanently removed from the ledger.</p>
                    <div class="flex flex-col gap-3">
                        <button @click="deleteTransaction" class="w-full py-4 rounded-xl bg-[#780116] text-white font-black text-xs uppercase tracking-[0.15em] hover:bg-[#c32f27] transition shadow-lg shadow-red-200 transform hover:scale-[1.02] active:scale-95">Purge Record</button>
                        <button @click="showDeleteModal = false" class="w-full py-4 rounded-xl border border-slate-100 text-slate-400 font-black text-xs uppercase tracking-[0.15em] hover:bg-slate-50 transition">Keep Entry</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
