<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { locales, t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';
import FormField from '../components/FormField.vue';

const router = useRouter();
const auth = useAuthStore();

const profileForm = reactive({
    name: auth.user?.name ?? '',
    email: auth.user?.email ?? '',
    locale: auth.user?.locale ?? 'ru',
});
const profileErrors = reactive({});
const profileError = ref('');
const profileSaved = ref(false);
const savingProfile = ref(false);

const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordErrors = reactive({});
const passwordError = ref('');
const passwordSaved = ref(false);
const savingPassword = ref(false);

async function saveProfile() {
    profileErrors.name = '';
    profileErrors.email = '';
    profileErrors.locale = '';
    profileError.value = '';
    profileSaved.value = false;
    savingProfile.value = true;

    try {
        await auth.updateProfile(profileForm);
        profileSaved.value = true;
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(profileErrors, error.response.data.errors ?? {});
        } else {
            profileError.value = t('profile.error_generic');
        }
    } finally {
        savingProfile.value = false;
    }
}

async function savePassword() {
    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordError.value = '';
    passwordSaved.value = false;
    savingPassword.value = true;

    try {
        await auth.updatePassword(passwordForm);
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
        passwordSaved.value = true;
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(passwordErrors, error.response.data.errors ?? {});
        } else {
            passwordError.value = t('profile.error_generic');
        }
    } finally {
        savingPassword.value = false;
    }
}

async function logout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <AppShell active="profile">
        <div class="mx-auto max-w-3xl px-6 py-8 sm:py-12">
            <h1 class="text-xl font-medium text-neutral-900">{{ t('profile.title') }}</h1>

            <section class="mt-8">
                <h2 class="text-sm font-medium text-neutral-900">{{ t('profile.section_details') }}</h2>

                <form class="mt-4 space-y-4" @submit.prevent="saveProfile">
                    <FormField v-model="profileForm.name" :label="t('profile.name')" autocomplete="name" :error="profileErrors.name?.[0]" />
                    <FormField v-model="profileForm.email" :label="t('profile.email')" type="email" autocomplete="email" :error="profileErrors.email?.[0]" />

                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-neutral-700">{{ t('profile.language') }}</span>
                        <select
                            v-model="profileForm.locale"
                            class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-base text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 sm:text-sm"
                        >
                            <option v-for="option in locales" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>

                    <p v-if="profileError" class="text-sm text-red-500">{{ profileError }}</p>
                    <p v-if="profileSaved" class="text-sm text-neutral-500">{{ t('profile.saved') }}</p>

                    <button
                        type="submit"
                        :disabled="savingProfile"
                        class="rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50"
                    >
                        {{ savingProfile ? t('common.saving') : t('profile.save') }}
                    </button>
                </form>
            </section>

            <section class="mt-10 border-t border-neutral-100 pt-8">
                <h2 class="text-sm font-medium text-neutral-900">{{ t('profile.section_password') }}</h2>

                <form class="mt-4 space-y-4" @submit.prevent="savePassword">
                    <FormField
                        v-model="passwordForm.current_password"
                        :label="t('profile.current_password')"
                        type="password"
                        autocomplete="current-password"
                        :error="passwordErrors.current_password?.[0]"
                    />
                    <FormField
                        v-model="passwordForm.password"
                        :label="t('profile.new_password')"
                        type="password"
                        autocomplete="new-password"
                        :error="passwordErrors.password?.[0]"
                    />
                    <FormField
                        v-model="passwordForm.password_confirmation"
                        :label="t('profile.confirm_password')"
                        type="password"
                        autocomplete="new-password"
                    />

                    <p v-if="passwordError" class="text-sm text-red-500">{{ passwordError }}</p>
                    <p v-if="passwordSaved" class="text-sm text-neutral-500">{{ t('profile.password_changed') }}</p>

                    <button
                        type="submit"
                        :disabled="savingPassword"
                        class="rounded-lg border border-neutral-200 px-4 py-2.5 text-sm font-medium text-neutral-700 transition hover:border-neutral-900 disabled:opacity-50"
                    >
                        {{ savingPassword ? t('common.saving') : t('profile.change_password') }}
                    </button>
                </form>
            </section>

            <button
                class="mt-10 text-sm text-neutral-500 hover:text-neutral-900 sm:hidden"
                @click="logout"
            >
                {{ t('profile.logout') }}
            </button>
        </div>
    </AppShell>
</template>
