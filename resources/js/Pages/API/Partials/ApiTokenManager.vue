<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import Checkbox from '@/Components/Checkbox.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: '',
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route('api-tokens.store'), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (managingPermissionsFor.value = null),
    });
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (apiTokenBeingDeleted.value = null),
    });
};
</script>

<template>
    <div>
        <!-- Generate API Token -->
        <FormSection @submitted="createApiToken">
            <template #title>
                Create API Token
            </template>

            <template #description>
                API tokens allow third-party services to authenticate with our application on your behalf.
            </template>

            <template #form>
                <!-- Token Name -->
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="name" class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-2" value="Protocol Identifier" />
                    <TextInput
                        id="name"
                        v-model="createApiTokenForm.name"
                        type="text"
                        class="mt-1 block w-full border-slate-200 focus:border-[#780116] focus:ring-[#780116] rounded-2xl shadow-none py-4 px-5 font-bold transition-all bg-slate-50/50 placeholder:text-slate-300"
                        placeholder="e.g. Server Alpha Uplink"
                        autofocus
                    />
                    <InputError :message="createApiTokenForm.errors.name" class="mt-2" />
                </div>

                <!-- Token Permissions -->
                <div v-if="availablePermissions.length > 0" class="col-span-6">
                    <InputLabel for="permissions" class="text-[10px] uppercase tracking-widest font-black text-slate-400 mb-4" value="Authorization Scopes" />

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 p-6 rounded-[2rem] border border-slate-100">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center group cursor-pointer">
                                <Checkbox v-model:checked="createApiTokenForm.permissions" :value="permission" />
                                <span class="ms-3 text-xs font-black uppercase tracking-widest text-slate-500 group-hover:text-[#780116] transition-colors">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>

            <template #actions>
                <ActionMessage :on="createApiTokenForm.recentlySuccessful" class="me-3">
                    Created.
                </ActionMessage>

                <PrimaryButton :class="{ 'opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">
                    Create
                </PrimaryButton>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <SectionBorder />

            <!-- Manage API Tokens -->
            <div class="mt-10 sm:mt-0">
                <ActionSection>
                    <template #title>
                        Manage API Tokens
                    </template>

                    <template #description>
                        You may delete any of your existing tokens if they are no longer needed.
                    </template>

                    <!-- API Token List -->
                    <template #content>
                        <div class="space-y-6">
                            <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between bg-slate-50/50 p-6 rounded-[1.5rem] border border-slate-100/50 hover:bg-white hover:shadow-xl transition-all group">
                                <div class="break-all font-black text-slate-700 tracking-tight uppercase text-sm">
                                    {{ token.name }}
                                </div>

                                <div class="flex items-center ms-2">
                                    <div v-if="token.last_used_ago" class="text-[10px] font-black uppercase tracking-widest text-[#db7c26] bg-orange-50 px-3 py-1 rounded-lg">
                                        Active {{ token.last_used_ago }}
                                    </div>

                                    <button
                                        v-if="availablePermissions.length > 0"
                                        class="cursor-pointer ms-6 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-[#780116] transition-colors"
                                        @click="manageApiTokenPermissions(token)"
                                    >
                                        Scopes
                                    </button>

                                    <button class="cursor-pointer ms-6 text-[10px] font-black uppercase tracking-widest text-red-400 hover:text-red-700 transition-colors" @click="confirmApiTokenDeletion(token)">
                                        Terminate
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </ActionSection>
            </div>
        </div>

        <!-- Token Value Modal -->
        <DialogModal :show="displayingToken" @close="displayingToken = false">
            <template #title>
                API Token
            </template>

            <template #content>
                <div class="font-bold text-slate-500 mb-6">
                    Protocol token initialized successfully. Secure this transmission key immediately; it will not be re-transmitted for security integrity.
                </div>

                <div v-if="$page.props.jetstream.flash.token" class="bg-slate-900 border-l-4 border-[#780116] p-6 rounded-2xl font-mono text-sm text-[#f7b538] break-all shadow-2xl">
                    {{ $page.props.jetstream.flash.token }}
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="displayingToken = false">
                    Close
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- API Token Permissions Modal -->
        <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
            <template #title>
                API Token Permissions
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50 p-8 rounded-[2rem] border border-slate-100">
                    <div v-for="permission in availablePermissions" :key="permission">
                        <label class="flex items-center group cursor-pointer">
                            <Checkbox v-model:checked="updateApiTokenForm.permissions" :value="permission" />
                            <span class="ms-3 text-xs font-black uppercase tracking-widest text-slate-500 group-hover:text-[#780116] transition-colors">{{ permission }}</span>
                        </label>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="managingPermissionsFor = null">
                    Cancel
                </SecondaryButton>

                <PrimaryButton
                    class="ms-3"
                    :class="{ 'opacity-25': updateApiTokenForm.processing }"
                    :disabled="updateApiTokenForm.processing"
                    @click="updateApiToken"
                >
                    Save
                </PrimaryButton>
            </template>
        </DialogModal>

        <!-- Delete Token Confirmation Modal -->
        <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
            <template #title>
                Delete API Token
            </template>

            <template #content>
                Are you sure you would like to delete this API token?
            </template>

            <template #footer>
                <SecondaryButton @click="apiTokenBeingDeleted = null">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': deleteApiTokenForm.processing }"
                    :disabled="deleteApiTokenForm.processing"
                    @click="deleteApiToken"
                >
                    Delete
                </DangerButton>
            </template>
        </ConfirmationModal>
    </div>
</template>
