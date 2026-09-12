import { defineStore } from 'pinia';
import api, { ensureCsrfCookie } from '../lib/api';
import { setLocale } from '../lib/i18n';
import { startAiRelay, stopAiRelay } from '../lib/aiRelay';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        checked: false,
    }),
    getters: {
        isAuthenticated: (state) => state.user !== null,
    },
    actions: {
        async fetchUser() {
            try {
                const { data } = await api.get('/api/user');
                this.user = data.user;
                this.syncLocale();
                startAiRelay();
            } catch {
                this.user = null;
            } finally {
                this.checked = true;
            }
        },
        async login(payload) {
            await ensureCsrfCookie();
            const { data } = await api.post('/api/login', payload);
            this.user = data.user;
            this.syncLocale();
            startAiRelay();
        },
        async register(payload) {
            await ensureCsrfCookie();
            const { data } = await api.post('/api/register', payload);
            this.user = data.user;
            this.syncLocale();
            startAiRelay();
        },
        async logout() {
            await api.post('/api/logout');
            this.user = null;
            stopAiRelay();
        },
        async updateProfile(payload) {
            const { data } = await api.patch('/api/profile', payload);
            this.user = data.user;
            this.syncLocale();
            return this.user;
        },
        async updatePassword(payload) {
            await api.put('/api/profile/password', payload);
        },
        // Carries the account's saved language preference into the UI so it
        // follows the student across devices, not just this browser.
        syncLocale() {
            if (this.user?.locale) {
                setLocale(this.user.locale);
            }
        },
    },
});
