<template>
    <div class="relative group">
        <!-- Main Icon Container -->
        <div class="relative z-10 size-10 bg-gradient-to-br from-[#780116] to-[#c32f27] rounded-xl flex items-center justify-center shadow-lg transform transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 active:scale-95 overflow-hidden">
            <!-- Subtle Inner Glow -->
            <div class="absolute inset-x-0 top-0 h-px bg-white/30"></div>
            
            <svg class="w-6 h-6 text-white drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>

            <!-- Interactive Shine Effect -->
            <div class="absolute top-0 -left-full w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent group-hover:left-full transition-all duration-1000"></div>
        </div>

        <!-- Layered Glow Effects -->
        <div class="absolute inset-0 bg-red-900/40 blur-lg rounded-xl transform scale-90 group-hover:scale-125 transition-all duration-500 -z-10"></div>
        <div class="absolute inset-0 bg-red-800/20 blur-2xl rounded-full transform scale-150 -z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-700"></div>
    </div>
</template>
