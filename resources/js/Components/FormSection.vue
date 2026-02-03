<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="px-8 py-8 bg-white sm:p-10 shadow-2xl shadow-slate-200/50 border border-slate-50"
                    :class="hasActions ? 'sm:rounded-t-[2.5rem]' : 'sm:rounded-[2.5rem]'"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex items-center justify-end px-8 py-4 bg-slate-50 text-end sm:px-10 border-x border-b border-slate-50 sm:rounded-b-[2.5rem]">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
