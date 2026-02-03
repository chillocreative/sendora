<script setup>
import SectionTitle from './SectionTitle.vue';
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
            <div class="px-8 py-8 sm:p-10 bg-white shadow-2xl shadow-slate-200/50 sm:rounded-[2.5rem] border border-slate-50">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>
