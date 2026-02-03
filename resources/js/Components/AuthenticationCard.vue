<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-[#780116] to-[#db7c26]">
        <div class="mb-6">
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-md px-8 py-8 bg-white border border-slate-200 sm:rounded-[2rem]">
            <slot />
        </div>
    </div>
</template>
