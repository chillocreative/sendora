<script>
// Shared across all instances: the Google script is loaded only once.
let scriptPromise = null;

function loadRecaptcha() {
    if (scriptPromise) return scriptPromise;

    scriptPromise = new Promise((resolve, reject) => {
        const ready = () => window.grecaptcha.ready(() => resolve(window.grecaptcha));

        if (window.grecaptcha && window.grecaptcha.render) {
            ready();
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = ready;
        script.onerror = () => {
            scriptPromise = null; // allow retry on next mount
            reject(new Error('Failed to load reCAPTCHA'));
        };
        document.head.appendChild(script);
    });

    return scriptPromise;
}
</script>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    modelValue: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const siteKey = computed(() => usePage().props.recaptchaSiteKey);
const container = ref(null);
let widgetId = null;
let unmounted = false;

onMounted(async () => {
    if (!siteKey.value) return;

    try {
        const grecaptcha = await loadRecaptcha();
        if (unmounted || !container.value) return;

        widgetId = grecaptcha.render(container.value, {
            sitekey: siteKey.value,
            callback: (token) => emit('update:modelValue', token),
            'expired-callback': () => emit('update:modelValue', ''),
            'error-callback': () => emit('update:modelValue', ''),
        });
    } catch (e) {
        emit('update:modelValue', '');
    }
});

onBeforeUnmount(() => {
    unmounted = true;
});

const reset = () => {
    emit('update:modelValue', '');
    if (widgetId !== null && window.grecaptcha) {
        window.grecaptcha.reset(widgetId);
    }
};

defineExpose({ reset });
</script>

<template>
    <div v-if="siteKey" ref="container" class="recaptcha-field"></div>
</template>
