<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { locale, locales, setLocale, t } from '../lib/i18n';

defineProps({
    // Route name of the page rendering the shell, used to highlight the
    // active tab/link. Passed explicitly instead of read from the route so a
    // page can opt out of highlighting (e.g. a modal-like sub-page).
    active: { type: String, default: '' },
});

const router = useRouter();
const auth = useAuthStore();

const tabs = [
    { name: 'dashboard', to: '/', icon: 'home', labelKey: 'nav.dashboard' },
    { name: 'analytics', to: '/analytics', icon: 'chart', labelKey: 'nav.analytics' },
    { name: 'new-course', to: '/courses/new', icon: 'plus', labelKey: 'nav.new_course' },
    { name: 'profile', to: '/profile', icon: 'user', labelKey: 'nav.profile' },
];

const icons = {
    home: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    chart: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C6.5 20.496 6 21 5.375 21h-2.25A1.125 1.125 0 0 1 2 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
    plus: 'M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    user: 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
};

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <header class="flex items-center justify-between border-b border-neutral-100 px-4 py-3 sm:px-6 sm:py-4">
            <router-link to="/" class="flex items-center gap-2">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-neutral-900 text-xs font-semibold text-white">R</span>
                <span class="text-sm font-medium text-neutral-900">{{ t('nav.brand') }}</span>
            </router-link>

            <nav class="hidden items-center gap-6 sm:flex">
                <router-link
                    v-for="tab in [tabs[0], tabs[1], tabs[3]]"
                    :key="tab.name"
                    :to="tab.to"
                    class="text-sm transition"
                    :class="active === tab.name ? 'font-medium text-neutral-900' : 'text-neutral-500 hover:text-neutral-900'"
                >
                    {{ t(tab.labelKey) }}
                </router-link>
                <router-link
                    to="/courses/new"
                    class="rounded-lg bg-neutral-900 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-neutral-800"
                >
                    {{ t('nav.new_course') }}
                </router-link>
            </nav>

            <div class="flex items-center gap-3 sm:gap-4">
                <select
                    :value="locale"
                    class="cursor-pointer rounded-lg border border-neutral-200 bg-white px-2 py-1.5 text-xs font-medium text-neutral-600 focus:border-neutral-900 focus:outline-none"
                    @change="setLocale($event.target.value)"
                >
                    <option v-for="option in locales" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>

                <span class="hidden text-sm text-neutral-500 sm:inline">{{ auth.user?.name }}</span>
                <button class="hidden text-sm text-neutral-500 hover:text-neutral-900 sm:inline" @click="logout">
                    {{ t('nav.logout') }}
                </button>
            </div>
        </header>

        <main class="flex-1 pb-24 sm:pb-0">
            <slot />
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 flex items-stretch justify-around border-t border-neutral-100 bg-white/95 backdrop-blur sm:hidden"
            style="padding-bottom: env(safe-area-inset-bottom)"
        >
            <router-link
                v-for="tab in tabs"
                :key="tab.name"
                :to="tab.to"
                class="flex min-w-0 flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-[11px]"
                :class="active === tab.name ? 'text-neutral-900' : 'text-neutral-400'"
            >
                <span
                    v-if="tab.name === 'new-course'"
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-neutral-900 text-white"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="icons[tab.icon]" />
                    </svg>
                </span>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="icons[tab.icon]" />
                </svg>
                <span class="truncate">{{ t(tab.labelKey) }}</span>
            </router-link>
        </nav>
    </div>
</template>
