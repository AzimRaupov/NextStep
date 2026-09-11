<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { t } from '../lib/i18n';
import AuthLayout from '../components/AuthLayout.vue';
import FormField from '../components/FormField.vue';

const router = useRouter();
const auth = useAuthStore();

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = reactive({});
const generalError = ref('');
const loading = ref(false);

async function submit() {
    errors.name = '';
    errors.email = '';
    errors.password = '';
    generalError.value = '';
    loading.value = true;

    try {
        await auth.register(form);
        router.push('/');
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(errors, error.response.data.errors ?? {});
        } else {
            generalError.value = t('auth.error_generic');
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthLayout :title="t('auth.register_title')" :subtitle="t('auth.register_subtitle')">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField v-model="form.name" :label="t('auth.name')" autocomplete="name" :error="errors.name?.[0]" />
            <FormField v-model="form.email" :label="t('auth.email')" type="email" autocomplete="email" :error="errors.email?.[0]" />
            <FormField v-model="form.password" :label="t('auth.password')" type="password" autocomplete="new-password" :error="errors.password?.[0]" />
            <FormField v-model="form.password_confirmation" :label="t('auth.password_confirmation')" type="password" autocomplete="new-password" />

            <p v-if="generalError" class="text-sm text-red-500">{{ generalError }}</p>

            <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50"
            >
                {{ loading ? t('auth.register_loading') : t('auth.register_button') }}
            </button>
        </form>

        <template #footer>
            {{ t('auth.has_account') }}
            <router-link to="/login" class="font-medium text-neutral-900 hover:underline">{{ t('auth.login_link') }}</router-link>
        </template>
    </AuthLayout>
</template>
