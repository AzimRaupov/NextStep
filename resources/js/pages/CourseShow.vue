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
const expandedStepId = ref(null);
const expandedModuleId = ref(null);

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
    expandCurrentModule(course);
}

// The section containing the lesson the student is actually working on
// should be open by default — everything else stays collapsed.
function expandCurrentModule(course) {
    expandedModuleId.value = course?.steps?.find((module) => module.status === 'available')?.id ?? null;
}

function toggleModule(moduleId) {
    expandedModuleId.value = expandedModuleId.value === moduleId ? null : moduleId;
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
        const course = await courses.fetchCourse(courseId);
        expandCurrentModule(course);
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

const cardStyle = {
    locked: 'border-neutral-100',
    available: 'border-neutral-900 shadow-sm',
    completed: 'border-neutral-200',
};

function toggleStep(stepId) {
    expandedStepId.value = expandedStepId.value === stepId ? null : stepId;
}

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

                    <div class="relative mt-10 space-y-1 lg:order-1 lg:mt-0">
                        <div
                            v-for="(module, moduleIndex) in courses.current.steps"
                            :key="module.id"
                            class="relative"
                        >
                            <!-- Section (parent step): a topical block of the roadmap, collapsed
                                 unless it's the one the student is currently working through. -->
                            <div class="relative flex gap-3 py-2 sm:gap-4">
                                <div
                                    class="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 font-mono text-sm"
                                    :class="nodeStyle[module.status]"
                                >
                                    {{ module.status === 'completed' ? '✓' : moduleIndex + 1 }}
                                </div>

                                <button
                                    type="button"
                                    class="flex flex-1 items-center gap-2 rounded-xl border px-3.5 py-3 text-left"
                                    :class="cardStyle[module.status]"
                                    @click="toggleModule(module.id)"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold" :class="module.status === 'locked' ? 'text-neutral-300' : 'text-neutral-900'">
                                            {{ module.title }}
                                        </p>
                                        <p class="mt-0.5 text-xs" :class="module.status === 'locked' ? 'text-neutral-200' : 'text-neutral-400'">
                                            {{ t('course.module_progress', { completed: module.steps.filter((s) => s.status === 'completed').length, total: module.steps.length }) }}
                                        </p>
                                    </div>
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-4 w-4 shrink-0 text-neutral-300 transition-transform"
                                        :class="expandedModuleId === module.id ? 'rotate-180' : ''"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Lessons (child steps) that belong to this section, indented and
                                 connected to it so the parent/child relationship is unmistakable. -->
                            <div v-if="expandedModuleId === module.id" class="ml-5 space-y-2 border-l border-neutral-200 py-1 pl-5">
                                <div
                                    v-for="(step, stepIndex) in module.steps"
                                    :key="step.id"
                                    class="relative flex gap-2.5"
                                >
                                    <div
                                        class="relative z-10 mt-2.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 font-mono text-[11px]"
                                        :class="nodeStyle[step.status]"
                                    >
                                        {{ step.status === 'completed' ? '✓' : stepIndex + 1 }}
                                    </div>

                                    <div class="flex-1 overflow-hidden rounded-xl border" :class="cardStyle[step.status]">
                                        <!-- Locked steps stay collapsed to one line — nothing to act on yet. -->
                                        <div v-if="step.status === 'locked'" class="flex items-center gap-2 px-3.5 py-3">
                                            <p class="flex-1 truncate text-sm text-neutral-300">{{ step.title }}</p>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-3.5 w-3.5 shrink-0 text-neutral-200">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"
                                                />
                                            </svg>
                                        </div>

                                        <!-- Completed steps collapse to a row too, expandable for a recap. -->
                                        <template v-else-if="step.status === 'completed'">
                                            <button
                                                type="button"
                                                class="flex w-full items-center gap-2 px-3.5 py-3 text-left"
                                                @click="toggleStep(step.id)"
                                            >
                                                <p class="flex-1 truncate text-sm font-medium text-neutral-900">{{ step.title }}</p>
                                                <span
                                                    v-if="step.requires_test"
                                                    class="shrink-0 rounded-full bg-neutral-100 px-2 py-0.5 font-mono text-[10px] uppercase tracking-wide text-neutral-500"
                                                >
                                                    {{ t('course.test_required_badge') }}
                                                </span>
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    class="h-4 w-4 shrink-0 text-neutral-300 transition-transform"
                                                    :class="expandedStepId === step.id ? 'rotate-180' : ''"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>

                                            <div v-if="expandedStepId === step.id" class="space-y-3 border-t border-neutral-100 px-3.5 py-3">
                                                <p class="text-sm text-neutral-500">{{ step.description }}</p>

                                                <ul class="space-y-1.5">
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

                                                <button
                                                    class="rounded-lg border border-neutral-200 px-4 py-2 text-xs font-medium text-neutral-700 transition hover:border-neutral-900"
                                                    @click="chatStep = step"
                                                >
                                                    {{ t('course.ask_ai') }}
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Available: the step the student is actually working on right now. -->
                                        <div v-else class="p-3.5 sm:p-4">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex items-center rounded-full bg-neutral-900 px-2 py-0.5 font-mono text-[10px] uppercase tracking-wide text-white">
                                                    {{ t('course.current_step_badge') }}
                                                </span>
                                                <span
                                                    v-if="step.requires_test"
                                                    class="inline-flex items-center rounded-full bg-neutral-100 px-2 py-0.5 font-mono text-[10px] uppercase tracking-wide text-neutral-500"
                                                >
                                                    {{ t('course.test_required_badge') }}
                                                </span>
                                            </div>

                                            <div class="mt-2 flex items-start justify-between gap-3">
                                                <p class="text-sm font-medium text-neutral-900">{{ step.title }}</p>
                                                <span v-if="step.estimated_days" class="shrink-0 font-mono text-xs text-neutral-400">
                                                    ~{{ formatDays(step.estimated_days) }}
                                                </span>
                                            </div>

                                            <p class="mt-1 text-sm text-neutral-500">{{ step.description }}</p>

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
                                                    v-if="step.requires_test"
                                                    class="rounded-lg bg-neutral-900 px-4 py-2 text-xs font-medium text-white transition hover:bg-neutral-800"
                                                    @click="goToStepTest(step)"
                                                >
                                                    {{ t('course.test_button') }}
                                                </button>

                                                <button
                                                    v-else
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <StepChatModal v-if="chatStep" :course-id="courseId" :step="chatStep" @close="chatStep = null" />
    </AppShell>
</template>
