<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { t } from '../lib/i18n';
import AuthLayout from '../components/AuthLayout.vue';
import FormField from '../components/FormField.vue';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({ email: '', password: '' });
const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

async function submit() {
    errors.email = '';
    errors.password = '';
    generalError.value = '';
    loading.value = true;

    try {
        await auth.login(form);
        router.push('/');
    } catch (error) {
        if (error.response?.status === 422) {
            const data = error.response.data;
            Object.assign(errors, data.errors ?? {});
            generalError.value = data.message ?? '';
        } else {
            generalError.value = t('auth.error_generic');
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthLayout :title="t('auth.login_title')" :subtitle="t('auth.login_subtitle')">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField v-model="form.email" :label="t('auth.email')" type="email" autocomplete="email" :error="errors.email?.[0]" />
            <FormField v-model="form.password" :label="t('auth.password')" type="password" autocomplete="current-password" :error="errors.password?.[0]" />

            <p v-if="generalError" class="text-sm text-red-500">{{ generalError }}</p>

            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50"
            >
                {{ loading ? t('auth.login_loading') : t('auth.login_button') }}
            </button>
        </form>

        <template #footer>
            {{ t('auth.no_account') }}
            <router-link to="/register" class="font-medium text-neutral-900 hover:underline">{{ t('auth.register_link') }}</router-link>
        </template>
    </AuthLayout>
</template>
