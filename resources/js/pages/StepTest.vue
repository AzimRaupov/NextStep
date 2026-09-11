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
const stepId = route.params.stepId;

const phase = ref('loading');
const submitting = ref(false);
const error = ref('');
const questions = ref([]);
const answers = reactive({});
const result = ref(null);

onMounted(loadTest);

async function loadTest() {
    phase.value = 'loading';

    const first = await courses.fetchStepTest(courseId, stepId);

    if (first.status === 'generating') {
        phase.value = 'generating';

        try {
            const done = await courses.pollStepTest(courseId, stepId);
            finishLoading(done);
        } catch {
            phase.value = 'failed';
        }

        return;
    }

    finishLoading(first);
}

function finishLoading(response) {
    if (response.status !== 'pending') {
        phase.value = 'failed';
        return;
    }

    questions.value = response.questions;
    phase.value = 'form';
}

function select(questionId, option) {
    answers[questionId] = option;
}

async function submit() {
    if (Object.keys(answers).length !== questions.value.length) {
        error.value = t('step_test.answer_all_error');
        return;
    }

    error.value = '';
    submitting.value = true;

    try {
        const payload = questions.value.map((q) => ({ question_id: q.id, selected_option: answers[q.id] }));
        result.value = await courses.submitStepTest(courseId, stepId, payload);
    } catch {
        error.value = t('step_test.submit_error');
    } finally {
        submitting.value = false;
    }
}

function backToRoadmap() {
    router.push(`/courses/${courseId}`);
}
</script>

<template>
    <AppShell>
        <div class="mx-auto max-w-3xl px-6 py-8 sm:py-12">
            <div v-if="phase === 'loading'" class="text-sm text-neutral-400">{{ t('step_test.loading') }}</div>

            <div v-else-if="phase === 'generating'" class="flex flex-col items-center gap-3 py-24 text-center">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900"></div>
                <p class="text-sm text-neutral-500">{{ t('step_test.generating') }}</p>
            </div>

            <div v-else-if="phase === 'failed'" class="flex flex-col items-center gap-3 py-24 text-center">
                <p class="text-sm text-neutral-900">{{ t('step_test.failed') }}</p>
                <button
                    class="mt-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                    @click="loadTest"
                >
                    {{ t('step_test.retry') }}
                </button>
            </div>

            <div v-else-if="result" class="py-16 text-center">
                <p class="text-2xl font-medium text-neutral-900">{{ result.score }}%</p>
                <p class="mt-2 text-sm text-neutral-500">
                    {{ result.passed ? t('step_test.passed') : t('step_test.failed_result') }}
                </p>
                <p class="mt-1 text-xs text-neutral-400">{{ t('step_test.correct_count', { correct: result.correct_count, total: result.total }) }}</p>

                <button
                    class="mt-8 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                    @click="backToRoadmap"
                >
                    {{ t('step_test.back_button') }}
                </button>
            </div>

            <div v-else>
                <h1 class="text-xl font-medium text-neutral-900">{{ t('step_test.title') }}</h1>

                <div class="mt-8 space-y-8">
                    <div v-for="question in questions" :key="question.id">
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
                    :disabled="submitting"
                    class="mt-8 w-full rounded-lg bg-neutral-900 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50"
                    @click="submit"
                >
                    {{ submitting ? t('step_test.submitting') : t('step_test.submit') }}
                </button>
            </div>
        </div>
    </AppShell>
</template>
