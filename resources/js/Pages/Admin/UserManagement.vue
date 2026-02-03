<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    users: Object, // Paginated object
    plans: Array,
});

// Edit State
const editingUser = ref(null);
const form = useForm({
    name: '',
    email: '',
    plan_id: null,
    status: 'Active',
});

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    plan_id: null,
    status: 'Active',
});

const showCreateModal = ref(false);

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const saveUser = () => {
    createForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        }
    });
};

const editUser = (user) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.plan_id = user.plan_id;
    form.status = user.status;
    form.clearErrors();
};

const updateUser = () => {
    form.put(route('admin.users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingUser.value = null;
            form.reset();
        }
    });
};

const closeEdit = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
};

// Delete State
const userToDelete = ref(null);
const deleteForm = useForm({});

const confirmDelete = (user) => {
    userToDelete.value = user;
};

const deleteUser = () => {
    deleteForm.delete(route('admin.users.delete', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            userToDelete.value = null;
        }
    });
};
</script>

<template>
    <AppLayout title="User Management">
        <template #header>
            <h2 class="font-bold text-xl text-slate-800 leading-tight">
                User Directory
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] border border-slate-100">
                    <!-- Toolbar -->
                    <div class="p-8 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <div class="relative">
                            <input type="text" placeholder="Search users..." class="pl-12 pr-6 py-3 bg-white border border-slate-100 rounded-2xl text-sm font-bold focus:ring-[#780116] focus:border-[#780116] w-80 shadow-sm transition-all outline-none">
                            <i class="fa-solid fa-magnifying-glass text-slate-300 absolute left-4 top-4 text-sm"></i>
                        </div>
                        <div class="flex space-x-4">
                             <button class="px-6 py-3 bg-white border border-slate-100 rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-400 hover:bg-slate-50 transition shadow-sm">Filter</button>
                             <button @click="openCreateModal" class="px-8 py-3 bg-[#780116] text-white rounded-2xl text-[11px] font-black uppercase tracking-widest hover:bg-[#c32f27] transition shadow-xl shadow-red-200 transform hover:scale-105 active:scale-95 flex items-center gap-2">
                                <i class="fa-solid fa-plus text-xs"></i>
                                Add Member
                             </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <div v-if="users.data.length === 0" class="p-8 text-center text-slate-500">
                            No users found.
                        </div>
                        <table v-else class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr class="text-[10px] text-slate-400 font-black uppercase tracking-[0.15em]">
                                    <th class="px-8 py-5">Verified Member</th>
                                    <th class="px-6 py-5">Role</th>
                                    <th class="px-6 py-5">Membership</th>
                                    <th class="px-6 py-5">Status</th>
                                    <th class="px-6 py-5">Last Billing</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/50 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center">
                                            <div class="h-12 w-12 flex-shrink-0 relative">
                                                <img class="h-12 w-12 rounded-[1.25rem] object-cover border-2 border-white shadow-md group-hover:scale-110 transition-transform duration-500" :src="user.profile_photo_url" :alt="user.name">
                                                <div v-if="user.status === 'Active'" class="absolute -bottom-1 -right-1 w-4 h-4 bg-red-500 border-2 border-white rounded-full"></div>
                                            </div>
                                            <div class="ml-5">
                                                <div class="font-black text-slate-900 group-hover:text-[#780116] transition-colors tracking-tight">{{ user.name }}</div>
                                                <div class="text-slate-400 text-[11px] font-bold">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-6">
                                        <span class="text-xs font-black uppercase tracking-widest" :class="user.role === 'Admin' ? 'text-[#780116]' : 'text-slate-400'">{{ user.role }}</span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-[0.1em] border"
                                            :class="user.plan === 'Free' ? 'bg-slate-50 text-slate-400 border-slate-100' : 'bg-red-50 text-[#780116] border-red-100'"
                                        >{{ user.plan }}</span>
                                    </td>
                                    <td class="px-6 py-6">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest border"
                                            :class="user.status === 'Active' ? 'bg-red-50 text-[#780116] border-red-100' : 'bg-slate-50 text-slate-300 border-slate-100'"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full mr-2" :class="user.status === 'Active' ? 'bg-[#f7b538] animate-pulse' : 'bg-slate-300'"></span>
                                            {{ user.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-6 text-[11px] font-black text-slate-400 uppercase tracking-widest">{{ user.payment_date || '--' }}</td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-2.5">
                                             <button @click="editUser(user)" class="w-10 h-10 flex items-center justify-center bg-orange-50 text-[#db7c26] hover:bg-[#db7c26] hover:text-white rounded-xl shadow-sm transition-all transform hover:scale-110 active:scale-95" title="Edit Member">
                                                <i class="fa-solid fa-pen-to-square text-sm"></i>
                                             </button>
                                             <button @click="confirmDelete(user)" class="w-10 h-10 flex items-center justify-center bg-red-50 text-[#780116] hover:bg-[#780116] hover:text-white rounded-xl shadow-sm transition-all transform hover:scale-110 active:scale-95" title="Remove Member">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                             </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination (Simple) -->
                    <div v-if="users.links.length > 3" class="bg-white px-4 py-3 border-t border-slate-200 sm:px-6 flex justify-between items-center">
                         <div class="flex space-x-1">
                             <template v-for="(link, k) in users.links" :key="k">
                                 <Link v-if="link.url" :href="link.url" 
                                    class="px-4 py-2 border rounded-xl text-[10px] font-black uppercase tracking-widest transition-all"
                                    :class="link.active ? 'bg-[#780116] border-[#780116] text-white shadow-lg shadow-red-100' : 'border-slate-100 text-slate-400 hover:bg-slate-50'"
                                    v-html="link.label"
                                 />
                             </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <DialogModal :show="editingUser !== null" @close="closeEdit">
            <template #title>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-orange-50 rounded-2xl flex items-center justify-center text-[#db7c26] shadow-sm shadow-orange-100 transform -rotate-6">
                        <i class="fa-solid fa-user-pen text-xl"></i>
                    </div>
                    <span class="text-xl font-black text-slate-900 tracking-tight">Modify Profile</span>
                </div>
            </template>
            <template #content>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 py-4">
                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel for="name" value="FULL NAME" class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="name" v-model="form.name" type="text" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-slate-50 focus:bg-white transition-all shadow-none" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel for="email" value="EMAIL ADDRESS" class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1" />
                            <TextInput id="email" v-model="form.email" type="email" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-slate-50 focus:bg-white transition-all shadow-none" />
                            <InputError :message="form.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="plan_id" value="SUBSCRIPTION PLAN" class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="plan_id" v-model="form.plan_id" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-slate-50 focus:bg-white transition-all focus:ring-[#780116] focus:border-[#780116] outline-none">
                                <option :value="null">Free Tier</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                            <InputError :message="form.errors.plan_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="status" value="MEMBERSHIP STATUS" class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 ml-1" />
                            <select id="status" v-model="form.status" class="w-full px-5 py-3 border-slate-100 rounded-2xl font-bold bg-slate-50 focus:bg-white transition-all focus:ring-[#780116] focus:border-[#780116] outline-none">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>
                    </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeEdit" class="rounded-2xl! px-8! py-4! text-[11px]! font-black! uppercase! tracking-widest!">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3 px-10! py-4! bg-[#780116]! rounded-2xl! text-[11px]! font-black! uppercase! tracking-widest! shadow-xl shadow-red-200!" @click="updateUser" :disabled="form.processing">Save Profile</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Create Modal -->
        <DialogModal :show="showCreateModal" @close="showCreateModal = false">
            <template #title>
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-red-50 rounded-2xl flex items-center justify-center text-[#780116] shadow-sm shadow-red-100 transform -rotate-6">
                        <i class="fa-solid fa-user-plus text-xl"></i>
                    </div>
                    <span class="text-xl font-black text-slate-900 tracking-tight">Onboard Member</span>
                </div>
            </template>
            <template #content>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel for="create_name" value="Name" />
                            <TextInput id="create_name" v-model="createForm.name" type="text" class="mt-1 block w-full" placeholder="Full Name" />
                            <InputError :message="createForm.errors.name" class="mt-2" />
                        </div>
                        <div class="col-span-1 sm:col-span-2">
                            <InputLabel for="create_email" value="Email" />
                            <TextInput id="create_email" v-model="createForm.email" type="email" class="mt-1 block w-full" placeholder="email@example.com" />
                            <InputError :message="createForm.errors.email" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="create_password" value="Password" />
                            <TextInput id="create_password" v-model="createForm.password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <InputError :message="createForm.errors.password" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="create_password_confirmation" value="Confirm Password" />
                            <TextInput id="create_password_confirmation" v-model="createForm.password_confirmation" type="password" class="mt-1 block w-full" />
                            <InputError :message="createForm.errors.password_confirmation" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="create_plan_id" value="Initial Plan" />
                            <select id="create_plan_id" v-model="createForm.plan_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option :value="null">Free Tier</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
                            </select>
                            <InputError :message="createForm.errors.plan_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="create_status" value="Membership Status" />
                            <select id="create_status" v-model="createForm.status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                            <InputError :message="createForm.errors.status" class="mt-2" />
                        </div>
                    </div>
            </template>
            <template #footer>
                <SecondaryButton @click="showCreateModal = false" class="rounded-2xl! px-8! py-4! text-[11px]! font-black! uppercase! tracking-widest!">Later</SecondaryButton>
                <PrimaryButton class="ml-3 px-10! py-4! bg-[#780116]! rounded-2xl! text-[11px]! font-black! uppercase! tracking-widest! shadow-xl shadow-red-200!" @click="saveUser" :disabled="createForm.processing">Create Member</PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="userToDelete !== null" @close="userToDelete = null">
            <template #title>
                Delete User
            </template>
            <template #content>
                Are you sure you want to delete this user? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="userToDelete = null">Cancel</SecondaryButton>
                <DangerButton class="ml-2" @click="deleteUser" :disabled="deleteForm.processing">Delete User</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
