<template>
    <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 class="text-xl font-black text-slate-900 tracking-tight leading-none">
                <slot name="title" />
            </h3>

            <p class="mt-2 text-[10px] font-black text-slate-400 uppercase tracking-widest leading-relaxed">
                <slot name="description" />
            </p>
        </div>

        <div class="px-4 sm:px-0">
            <slot name="aside" />
        </div>
    </div>
</template>
