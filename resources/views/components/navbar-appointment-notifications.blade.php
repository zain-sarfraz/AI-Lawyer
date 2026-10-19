<div
    x-data="appointmentNotifier({
        feedUrl: @js(route('notifications.appointments.feed')),
        markReadUrl: @js(route('notifications.appointments.mark-read')),
    })"
    class="relative flex items-center"
>
    <!-- Sound Toggle Quick Button -->
    <button
        type="button"
        @click="toggleSound()"
        class="mr-2 grid h-9 w-9 place-items-center rounded-xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-white"
        :title="soundEnabled ? 'Notification Sound: ON (Click to Mute)' : 'Notification Sound: OFF (Click to Unmute)'"
        :aria-label="soundEnabled ? 'Mute notification sound' : 'Unmute notification sound'"
    >
        <template x-if="soundEnabled">
            <svg class="h-4 w-4 text-teal-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
            </svg>
        </template>
        <template x-if="!soundEnabled">
            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
        </template>
    </button>

    <!-- Bell Icon with Ringing / Blinking Animation -->
    <div class="relative">
        <button
            type="button"
            @click="openDropdown()"
            class="relative grid h-10 w-10 place-items-center rounded-xl border transition"
            :class="[
                unreadCount > 0 || hasNew
                    ? 'border-teal-400/50 bg-teal-500/15 text-teal-300 shadow-[0_0_15px_rgba(45,212,191,0.25)]'
                    : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white'
            ]"
            :aria-label="'Appointments notification bell (' + unreadCount + ' unread)'"
        >
            <svg
                class="h-5 w-5 transition-transform"
                :class="{ 'animate-bell-swing text-teal-300': hasNew || unreadCount > 0 }"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <template x-if="unreadCount > 0">
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-extrabold text-white shadow-lg">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative" x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                </span>
            </template>
        </button>

        <div
            x-show="open"
            x-cloak
            @click.outside="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-2 scale-95"
            class="absolute right-0 top-full z-50 mt-2 w-80 sm:w-96 rounded-2xl border border-white/10 bg-navy-900/95 p-3 text-slate-100 shadow-2xl backdrop-blur-xl"
        >
            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-teal-500/20 text-teal-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <div>
                        <div class="text-sm font-bold text-white">Appointments</div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400" x-text="items.length + ' Recent booked'"></div>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="testSound()"
                        class="rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-[11px] font-semibold text-teal-300 hover:bg-white/10 transition"
                        title="Click to hear notification sound"
                    >
                        Test 🔔
                    </button>
                </div>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-white/5 py-1">
                <template x-if="items.length === 0">
                    <div class="py-8 text-center text-xs text-slate-400">
                        <svg class="mx-auto h-8 w-8 text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        No appointments found.
                    </div>
                </template>

                <template x-for="item in items" :key="item.id">
                    <a
                        :href="item.view_url"
                        class="group block rounded-xl p-2.5 transition hover:bg-white/5"
                        :class="{ 'bg-teal-500/10 border-l-2 border-teal-400': item.is_unread }"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate text-xs font-bold text-white group-hover:text-teal-300" x-text="item.client_name"></span>
                                    <template x-if="item.is_ai">
                                        <span class="rounded bg-teal-400/20 px-1 py-0.5 text-[9px] font-extrabold text-teal-300 uppercase">AI Booked</span>
                                    </template>
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-[11px] text-slate-400">
                                    <span class="text-teal-200 font-medium" x-text="item.service_name"></span>
                                    <span>•</span>
                                    <span x-text="item.lawyer_name"></span>
                                </div>
                                <div class="mt-1 flex items-center gap-2 text-[10px] text-slate-400">
                                    <span class="font-medium text-slate-300" x-text="item.date + ' @ ' + item.time"></span>
                                    <span>•</span>
                                    <span class="text-slate-400" x-text="item.client_phone"></span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="block text-[10px] text-slate-400" x-text="item.created_at_human"></span>
                                <span class="mt-1 inline-block rounded-full bg-white/10 px-1.5 py-0.5 text-[9px] font-semibold text-slate-300 uppercase" x-text="item.status"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>

            <div class="border-t border-white/10 pt-2 text-center">
                <a
                    href="{{ route('appointments.index') }}"
                    class="block rounded-xl bg-white/5 py-1.5 text-xs font-semibold text-teal-300 hover:bg-teal-500/20 transition"
                >
                    View All Appointments &rarr;
                </a>
            </div>
        </div>
    </div>

    <div
        x-show="toast !== null"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-4 translate-x-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 translate-x-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 translate-x-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-4 translate-x-4 scale-95"
        class="fixed right-4 top-4 z-50 flex w-88 max-w-[calc(100vw-2rem)] items-start gap-3 rounded-2xl border border-teal-400/30 bg-navy-900/95 p-4 text-white shadow-2xl shadow-teal-500/10 backdrop-blur-xl"
    >
        <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-teal-500/20 text-teal-300">
            <svg class="h-6 w-6 animate-bell-swing" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold uppercase tracking-wider text-teal-300">New Appointment Booked!</span>
                <button type="button" @click="toast = null" class="text-slate-400 hover:text-white text-xs">✕</button>
            </div>
            <div class="mt-1 text-sm font-bold text-white truncate" x-text="toast?.client_name"></div>
            <div class="mt-0.5 text-xs text-teal-200" x-text="toast?.service_name + ' with ' + toast?.lawyer_name"></div>
            <div class="mt-1 text-[11px] text-slate-400" x-text="toast?.date + ' @ ' + toast?.time + ' (' + toast?.client_phone + ')'"></div>
            <div class="mt-2 flex gap-2">
                <a
                    :href="toast?.view_url"
                    class="rounded-lg bg-teal-500/20 px-2.5 py-1 text-xs font-semibold text-teal-300 hover:bg-teal-500/30 transition"
                >
                    View Appointment
                </a>
                <button
                    type="button"
                    @click="toast = null"
                    class="rounded-lg border border-white/10 px-2.5 py-1 text-xs font-medium text-slate-300 hover:bg-white/5 transition"
                >
                    Dismiss
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes bellSwing {
    0%, 100% { transform: rotate(0deg); }
    20%, 60% { transform: rotate(18deg); }
    40%, 80% { transform: rotate(-18deg); }
}
.animate-bell-swing {
    animation: bellSwing 1s ease-in-out infinite;
    transform-origin: top center;
}
</style>
