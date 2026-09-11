<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCoursesStore } from '../stores/courses';
import { describePace, formatDays, formatDeadline } from '../lib/format';
import { t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';
import StepChatModal from '../components/StepChatModal.vue';
import PaceGauge from '../components/PaceGauge.vue';

const route = useRoute();
const router = useRouter();
const courses = useCoursesStore();

const courseId = route.params.id;
const phase = ref('loading');
const completingId = ref(null);
const chatStep = ref(null);

onMounted(loadCourse);

async function loadCourse() {
    phase.value = 'loading';

    let course = await courses.fetchCourse(courseId);

    if (course.status === 'queued' || course.status === 'pending_test') {
        router.replace(`/courses/${courseId}/test`);
        return;
    }

    if (course.status === 'generating') {
        phase.value = 'generating';

        try {
            course = await courses.pollCourse(courseId, { pending: ['generating'] });
        } catch {
            phase.value = 'failed';
            return;
        }
    }

    phase.value = course.status === 'ready' ? 'ready' : 'failed';
}

async function retry() {
    phase.value = 'loading';
    await courses.retryCourse(courseId);
    await loadCourse();
}

function goToStepTest(step) {
    router.push(`/courses/${courseId}/steps/${step.id}/test`);
}

async function completeStep(step) {
    completingId.value = step.id;

    try {
        await courses.completeStep(courseId, step.id);
        await courses.fetchCourse(courseId);
    } finally {
        completingId.value = null;
    }
}

const resourceTypeLabelKeys = {
    article: 'course.resource_article',
    video: 'course.resource_video',
    docs: 'course.resource_docs',
    other: 'course.resource_other',
};

const nodeStyle = {
    locked: 'border-neutral-200 text-neutral-300 bg-white',
    available: 'border-neutral-900 text-neutral-900 bg-white',
    completed: 'border-neutral-900 bg-neutral-900 text-white',
};

const pacingLine = computed(() => describePace(courses.current?.pace));

const deadline = computed(() => {
    const pace = courses.current?.pace;

    if (!pace || !pace.remaining_days) {
        return null;
    }

    return {
        days: formatDays(pace.remaining_days),
        date: formatDeadline(pace.deadline_date),
    };
});

const progressPercent = computed(() => {
    const progress = courses.current?.progress;

    if (!progress || progress.total === 0) {
        return 0;
    }

    return Math.round((progress.completed / progress.total) * 100);
});
</script>

<template>
    <AppShell>
        <div class="mx-auto max-w-5xl px-6 py-8 sm:py-12">
            <div v-if="phase === 'loading'" class="text-sm text-neutral-400">{{ t('course.loading') }}</div>

            <div v-else-if="phase === 'generating'" class="flex flex-col items-center gap-3 py-24 text-center">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900"></div>
                <p class="text-sm text-neutral-500">{{ t('course.generating') }}</p>
            </div>

            <div v-else-if="phase === 'failed'" class="flex flex-col items-center gap-3 py-24 text-center">
                <p class="text-sm text-neutral-900">{{ t('course.failed') }}</p>
                <button
                    class="mt-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800"
                    @click="retry"
                >
                    {{ t('course.retry') }}
                </button>
            </div>

            <template v-else>
                <p class="text-xs font-medium text-neutral-400 uppercase">{{ courses.current.level_label }}</p>
                <h1 class="mt-1 text-xl font-medium text-neutral-900">{{ courses.current.title }}</h1>
                <p class="mt-2 text-sm text-neutral-500">{{ courses.current.summary }}</p>

                <div class="mt-8 lg:grid lg:grid-cols-[1fr_300px] lg:items-start lg:gap-10">
                    <aside class="lg:sticky lg:top-8 lg:order-2">
                        <div>
                            <div class="flex items-center justify-between font-mono text-xs text-neutral-400">
                                <span>{{ t('course.progress_label') }}</span>
                                <span>{{ t('course.steps_word', { completed: courses.current.progress.completed, total: courses.current.progress.total, percent: progressPercent }) }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                                <div class="h-full rounded-full bg-neutral-900" :style="{ width: progressPercent + '%' }"></div>
                            </div>
                        </div>

                        <p v-if="deadline" class="mt-3 text-sm text-neutral-500">
                            {{ t('course.remaining_prefix', { days: deadline.days }) }}
                            <template v-if="deadline.date"> {{ t('course.deadline_by', { date: deadline.date }) }}</template>
                        </p>

                        <div
                            v-if="pacingLine"
                            class="mt-4 rounded-xl border p-4"
                            :class="pacingLine.tone === 'behind' ? 'border-amber-200 bg-amber-50' : 'border-neutral-200'"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium uppercase tracking-wide text-neutral-400">{{ t('course.pace_label') }}</span>
                                <span class="font-medium" :class="pacingLine.tone === 'behind' ? 'text-amber-700' : 'text-neutral-700'">
                                    {{ pacingLine.text }}
                                </span>
                            </div>
                            <PaceGauge class="mt-2.5" :ratio="courses.current.pace.overall_ratio" />
                        </div>
                    </aside>

                    <div class="relative mt-10 lg:order-1 lg:mt-0">
                        <div class="absolute bottom-5 left-5 top-5 w-px bg-neutral-200"></div>

                        <div
                            v-for="(step, index) in courses.current.steps"
                            :key="step.id"
                            class="relative flex gap-4 py-4"
                        >
                            <div
                                class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 font-mono text-sm"
                                :class="[nodeStyle[step.status], step.requires_test ? 'rounded-md rotate-45' : '']"
                            >
                                <span :class="step.requires_test ? '-rotate-45' : ''">
                                    {{ step.status === 'completed' ? '✓' : index + 1 }}
                                </span>
                            </div>

                            <div
                                class="flex-1 rounded-xl border p-4"
                                :class="[
                                    step.status === 'locked' ? 'border-neutral-100' : 'border-neutral-200 shadow-sm',
                                    index % 2 === 1 ? 'sm:ml-10' : '',
                                ]"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm font-medium" :class="step.status === 'locked' ? 'text-neutral-300' : 'text-neutral-900'">
                                        {{ step.title }}
                                    </p>
                                    <span v-if="step.estimated_days && step.status !== 'locked'" class="shrink-0 font-mono text-xs text-neutral-400">
                                        ~{{ formatDays(step.estimated_days) }}
                                    </span>
                                </div>

                                <p class="mt-1 text-sm" :class="step.status === 'locked' ? 'text-neutral-300' : 'text-neutral-500'">
                                    {{ step.description }}
                                </p>

                                <template v-if="step.status !== 'locked'">
                                    <span
                                        v-if="step.pace && step.pace.status === 'behind'"
                                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 font-mono text-[11px] uppercase tracking-wide text-amber-700"
                                    >
                                        {{ t('course.behind_step_prefix', { days: formatDays(step.pace.delta_days) }) }}
                                    </span>
                                    <span
                                        v-else-if="step.pace && step.pace.status === 'ahead'"
                                        class="mt-3 inline-flex items-center gap-1 rounded-full bg-neutral-100 px-2.5 py-1 font-mono text-[11px] uppercase tracking-wide text-neutral-600"
                                    >
                                        {{ t('course.ahead_step') }}
                                    </span>

                                    <ul class="mt-3 space-y-1.5">
                                        <li v-for="resource in step.resources" :key="resource.id">
                                            <a
                                                :href="resource.url"
                                                target="_blank"
                                                rel="noopener"
                                                class="text-sm text-neutral-600 underline decoration-neutral-300 underline-offset-2 hover:text-neutral-900"
                                            >
                                                {{ resource.title }}
                                            </a>
                                            <span class="ml-1.5 text-xs text-neutral-400">{{ t(resourceTypeLabelKeys[resource.type]) }}</span>
                                        </li>
                                    </ul>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <button
                                            v-if="step.requires_test && step.status === 'available'"
                                            class="rounded-lg bg-neutral-900 px-4 py-2 text-xs font-medium text-white transition hover:bg-neutral-800"
                                            @click="goToStepTest(step)"
                                        >
                                            {{ t('course.test_button') }}
                                        </button>

                                        <button
                                            v-else-if="!step.requires_test && step.status === 'available'"
                                            :disabled="completingId === step.id"
                                            class="rounded-lg border border-neutral-200 px-4 py-2 text-xs font-medium text-neutral-700 transition hover:border-neutral-900 disabled:opacity-50"
                                            @click="completeStep(step)"
                                        >
                                            {{ completingId === step.id ? t('course.completing') : t('course.complete_button') }}
                                        </button>

                                        <button
                                            class="rounded-lg border border-neutral-200 px-4 py-2 text-xs font-medium text-neutral-700 transition hover:border-neutral-900"
                                            @click="chatStep = step"
                                        >
                                            {{ t('course.ask_ai') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <StepChatModal v-if="chatStep" :course-id="courseId" :step="chatStep" @close="chatStep = null" />
    </AppShell>
</template>
