<script setup>
import Modal from './Modal.vue';

const emit = defineEmits(['close']);

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="close"
    >
        <div class="px-8 py-8">
            <div class="text-xl font-black text-slate-900 tracking-tight leading-none mb-4">
                <slot name="title" />
            </div>

            <div class="mt-4 text-sm font-bold text-slate-500 leading-relaxed">
                <slot name="content" />
            </div>
        </div>

        <div class="flex flex-row justify-end px-8 py-6 bg-slate-50 text-end rounded-b-[2rem]">
            <slot name="footer" />
        </div>
    </Modal>
</template>
