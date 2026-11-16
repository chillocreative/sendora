<script>
// Shared across all instances: the Cloudflare script is loaded only once.
let scriptPromise = null;

function loadTurnstile() {
    if (scriptPromise) return scriptPromise;

    scriptPromise = new Promise((resolve, reject) => {
        if (window.turnstile) {
            resolve(window.turnstile);
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = () => resolve(window.turnstile);
        script.onerror = () => {
            scriptPromise = null; // allow retry on next mount
            reject(new Error('Failed to load Turnstile'));
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

const siteKey = computed(() => usePage().props.turnstileSiteKey);
const container = ref(null);
let widgetId = null;
let unmounted = false;

onMounted(async () => {
    if (!siteKey.value) return;

    try {
        const turnstile = await loadTurnstile();
        if (unmounted || !container.value) return;

        widgetId = turnstile.render(container.value, {
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
    if (widgetId !== null && window.turnstile) {
        window.turnstile.remove(widgetId);
    }
});

const reset = () => {
    emit('update:modelValue', '');
    if (widgetId !== null && window.turnstile) {
        window.turnstile.reset(widgetId);
    }
};

defineExpose({ reset });
</script>

<template>
    <div v-if="siteKey" ref="container" class="cf-turnstile-field flex justify-center sm:justify-start"></div>
</template>
