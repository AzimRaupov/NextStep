<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCoursesStore } from '../stores/courses';
import { t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';

const router = useRouter();
const courses = useCoursesStore();

const topic = ref('');
const declaredLevel = ref('basics');
const creating = ref(false);
const error = ref('');

const levelOptions = [
    { value: 'none', labelKey: 'new_course.level_none_label', hintKey: 'new_course.level_none_hint' },
    { value: 'basics', labelKey: 'new_course.level_basics_label', hintKey: 'new_course.level_basics_hint' },
    { value: 'confident', labelKey: 'new_course.level_confident_label', hintKey: 'new_course.level_confident_hint' },
];

async function createCourse() {
    if (!topic.value.trim()) {
        return;
    }

    creating.value = true;
    error.value = '';

    try {
        const course = await courses.createCourse(topic.value.trim(), declaredLevel.value);

        if (course.status === 'generating') {
            router.push(`/courses/${course.id}`);
        } else {
            router.push(`/courses/${course.id}/test`);
        }
    } catch {
        error.value = t('new_course.error');
    } finally {
        creating.value = false;
    }
}
</script>

<template>
    <AppShell active="new-course">
        <div class="mx-auto max-w-3xl px-6 py-8 sm:py-12">
            <h1 class="text-xl font-medium text-neutral-900">{{ t('new_course.title') }}</h1>
            <p class="mt-2 text-sm text-neutral-500">{{ t('new_course.subtitle') }}</p>

            <form class="mt-6" @submit.prevent="createCourse">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-neutral-700">{{ t('new_course.topic_label') }}</span>
                    <input
                        v-model="topic"
                        type="text"
                        :placeholder="t('new_course.topic_placeholder')"
                        class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 sm:text-sm"
                    />
                </label>

                <p class="mt-4 text-xs font-medium uppercase tracking-wide text-neutral-400">{{ t('new_course.level_label') }}</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    <label
                        v-for="option in levelOptions"
                        :key="option.value"
                        class="cursor-pointer rounded-lg border px-3.5 py-2.5 text-sm transition"
                        :class="declaredLevel === option.value ? 'border-neutral-900 bg-neutral-50' : 'border-neutral-200 hover:border-neutral-300'"
                    >
                        <input
                            v-model="declaredLevel"
                            type="radio"
                            name="declared-level"
                            :value="option.value"
                            class="sr-only"
                        />
                        <p class="font-medium text-neutral-900">{{ t(option.labelKey) }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">{{ t(option.hintKey) }}</p>
                    </label>
                </div>

                <button
                    type="submit"
                    :disabled="creating"
                    class="mt-5 w-full shrink-0 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50 sm:w-auto"
                >
                    {{ creating ? t('new_course.submitting') : t('new_course.submit') }}
                </button>
            </form>
            <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
        </div>
    </AppShell>
</template>
