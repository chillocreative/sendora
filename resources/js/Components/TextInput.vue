<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="border-slate-200 focus:border-[#780116] focus:ring-[#780116] rounded-xl shadow-none py-3 px-4 transition-all duration-200 placeholder:text-slate-300 w-full"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
