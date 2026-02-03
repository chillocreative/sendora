<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center justify-center px-6 py-3 bg-[#c32f27] border border-transparent rounded-[1.25rem] font-black text-[10px] text-white uppercase tracking-[0.2em] shadow-xl shadow-red-200/50 hover:bg-[#a62621] active:scale-95 transition-all duration-150">
        <slot />
    </button>
</template>
