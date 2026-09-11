<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCoursesStore } from '../stores/courses';
import { t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';

const route = useRoute();
const router = useRouter();
const courses = useCoursesStore();

const courseId = route.params.id;
const phase = ref('loading');
const error = ref('');
const answers = reactive({});

onMounted(loadCourse);

async function loadCourse() {
    phase.value = 'loading';
    error.value = '';

    let course = await courses.fetchCourse(courseId);

    if (course.status === 'queued') {
        phase.value = 'generating_test';

        try {
            course = await courses.pollCourse(courseId, { pending: ['queued'] });
        } catch {
            error.value = t('placement_test.generation_error');
            phase.value = 'failed';
            return;
        }
    }

    if (course.status === 'failed') {
        phase.value = 'failed';
        return;
    }

    if (course.status !== 'pending_test') {
        router.replace(`/courses/${courseId}`);
        return;
    }

    phase.value = 'form';
}

async function retry() {
    phase.value = 'loading';

    try {
        await courses.retryCourse(courseId);
        await loadCourse();
    } catch {
        error.value = t('placement_test.retry_error');
        phase.value = 'failed';
    }
}

function select(questionId, option) {
    answers[questionId] = option;
}

async function submit() {
    const questions = courses.current.placement_test.questions;

    if (Object.keys(answers).length !== questions.length) {
        error.value = t('placement_test.answer_all_error');
        return;
    }

    error.value = '';
    phase.value = 'submitting';

    try {
        const payload = questions.map((q) => ({ question_id: q.id, selected_option: answers[q.id] }));
        await courses.submitPlacementTest(courseId, payload);

        phase.value = 'generating_roadmap';

        const finalCourse = await courses.pollCourse(courseId, { pending: ['generating'] });

        if (finalCourse.status === 'ready') {
            router.push(`/courses/${courseId}`);
        } else {
            error.value = t('placement_test.roadmap_error');
            phase.value = 'failed';
        }
    } catch {
        error.value = t('placement_test.submit_error');
        phase.value = 'form';
    }
}
</script>

<template>
    <AppShell>
        <div class="mx-auto max-w-3xl px-6 py-8 sm:py-12">
            <div v-if="phase === 'loading'" class="text-sm text-neutral-400">{{ t('placement_test.loading') }}</div>

            <div v-else-if="phase === 'generating_test'" class="flex flex-col items-center gap-3 py-24 text-center">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900"></div>
                <p class="text-sm text-neutral-500">{{ t('placement_test.generating_test') }}</p>
            </div>

            <div v-else-if="phase === 'submitting' || phase === 'generating_roadmap'" class="flex flex-col items-center gap-3 py-24 text-center">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900"></div>
                <p class="text-sm text-neutral-500">
                    {{ phase === 'submitting' ? t('placement_test.submitting') : t('placement_test.generating_roadmap') }}
                </p>
            </div>

            <div v-else-if="phase === 'failed'" class="flex flex-col items-center gap-3 py-24 text-center">
                <p class="text-sm text-neutral-900">{{ t('placement_test.failed') }}</p>
                <p v-if="error" class="text-sm text-neutral-500">{{ error }}</p>
                <button
                    class="mt-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                    @click="retry"
                >
                    {{ t('placement_test.retry') }}
                </button>
            </div>

            <div v-else>
                <h1 class="text-xl font-medium text-neutral-900">{{ t('placement_test.title_prefix', { topic: courses.current.topic }) }}</h1>
                <p class="mt-2 text-sm text-neutral-500">{{ t('placement_test.subtitle') }}</p>

                <div class="mt-8 space-y-8">
                    <div v-for="question in courses.current.placement_test.questions" :key="question.id">
                        <p class="text-sm font-medium text-neutral-900">{{ question.order }}. {{ question.question }}</p>
                        <div class="mt-3 space-y-2">
                            <label
                                v-for="option in question.options"
                                :key="option"
                                class="flex cursor-pointer items-center gap-2.5 rounded-lg border px-3.5 py-2.5 text-sm transition"
                                :class="
                                    answers[question.id] === option
                                        ? 'border-neutral-900 bg-neutral-50'
                                        : 'border-neutral-200 hover:border-neutral-300'
                                "
                            >
                                <input
                                    type="radio"
                                    :name="`q-${question.id}`"
                                    class="accent-neutral-900"
                                    :checked="answers[question.id] === option"
                                    @change="select(question.id, option)"
                                />
                                <span class="text-neutral-800">{{ option }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <p v-if="error" class="mt-6 text-sm text-red-500">{{ error }}</p>

                <button
                    class="mt-8 w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                    @click="submit"
                >
                    {{ t('placement_test.submit') }}
                </button>
            </div>
        </div>
    </AppShell>
</template>
