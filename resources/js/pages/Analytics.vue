<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCoursesStore } from '../stores/courses';
import { describePace, formatDays, formatDeadline } from '../lib/format';
import { t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';
import PaceGauge from '../components/PaceGauge.vue';

const router = useRouter();
const courses = useCoursesStore();
const loading = ref(true);

onMounted(async () => {
    await courses.fetchList();
    loading.value = false;
});

const stats = computed(() => {
    const list = courses.list;
    const started = list.filter((c) => (c.progress?.total ?? 0) > 0);
    const stepsCompleted = started.reduce((sum, c) => sum + c.progress.completed, 0);
    const stepsTotal = started.reduce((sum, c) => sum + c.progress.total, 0);
    const inProgress = started.filter((c) => c.progress.completed < c.progress.total).length;
    const behind = list.filter((c) => describePace(c.pace)?.tone === 'behind').length;

    return {
        total: list.length,
        inProgress,
        stepsCompleted,
        stepsTotal,
        behind,
    };
});

// Courses that are still being worked on, closest deadline first — this is
// the view meant to answer "what should I focus on to stay on schedule".
const activeCourses = computed(() =>
    courses.list
        .filter((c) => c.status === 'ready' && c.progress && c.progress.completed < c.progress.total)
        .slice()
        .sort((a, b) => (a.pace?.remaining_days ?? Infinity) - (b.pace?.remaining_days ?? Infinity)),
);

function progressPercent(course) {
    if (!course.progress || course.progress.total === 0) {
        return 0;
    }

    return Math.round((course.progress.completed / course.progress.total) * 100);
}

function openCourse(course) {
    router.push(`/courses/${course.id}`);
}
</script>

<template>
    <AppShell active="analytics">
        <div class="mx-auto max-w-5xl px-6 py-8 sm:py-12">
            <h1 class="text-xl font-medium text-neutral-900">{{ t('analytics.title') }}</h1>
            <p class="mt-2 text-sm text-neutral-500">{{ t('analytics.subtitle') }}</p>

            <p v-if="loading" class="mt-8 text-sm text-neutral-400">{{ t('common.loading') }}</p>

            <template v-else>
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-neutral-200 p-3.5">
                        <p class="font-mono text-xl text-neutral-900">{{ stats.total }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">{{ t('analytics.stat_courses') }}</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200 p-3.5">
                        <p class="font-mono text-xl text-neutral-900">{{ stats.inProgress }}</p>
                        <p class="mt-0.5 text-xs text-neutral-500">{{ t('analytics.stat_in_progress') }}</p>
                    </div>
                    <div class="rounded-xl border border-neutral-200 p-3.5">
                        <p class="font-mono text-xl text-neutral-900">{{ stats.stepsCompleted }}<span class="text-neutral-400">/{{ stats.stepsTotal }}</span></p>
                        <p class="mt-0.5 text-xs text-neutral-500">{{ t('analytics.stat_completed_steps') }}</p>
                    </div>
                    <div class="rounded-xl border p-3.5" :class="stats.behind > 0 ? 'border-amber-200 bg-amber-50' : 'border-neutral-200'">
                        <p class="font-mono text-xl" :class="stats.behind > 0 ? 'text-amber-700' : 'text-neutral-900'">{{ stats.behind }}</p>
                        <p class="mt-0.5 text-xs" :class="stats.behind > 0 ? 'text-amber-700' : 'text-neutral-500'">{{ t('analytics.stat_behind') }}</p>
                    </div>
                </div>

                <div class="mt-10">
                    <h2 class="text-sm font-medium text-neutral-900">{{ t('analytics.deadlines_title') }}</h2>

                    <p v-if="activeCourses.length === 0" class="mt-4 text-sm text-neutral-400">{{ t('analytics.no_active_courses') }}</p>

                    <ul v-else class="mt-4 grid gap-3 lg:grid-cols-2">
                        <li
                            v-for="course in activeCourses"
                            :key="course.id"
                            class="cursor-pointer rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-900"
                            @click="openCourse(course)"
                        >
                            <p class="text-sm font-medium text-neutral-900">{{ course.title || course.topic }}</p>

                            <div class="mt-3">
                                <div class="flex items-center justify-between font-mono text-xs text-neutral-400">
                                    <span>{{ course.progress.completed }} / {{ course.progress.total }}</span>
                                    <span>{{ progressPercent(course) }}%</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                                    <div class="h-full rounded-full bg-neutral-900" :style="{ width: progressPercent(course) + '%' }"></div>
                                </div>
                            </div>

                            <p v-if="course.pace?.remaining_days" class="mt-3 text-xs text-neutral-500">
                                {{ t('analytics.remaining_prefix', { days: formatDays(course.pace.remaining_days) }) }}
                                <template v-if="course.pace.deadline_date">
                                    · {{ t('analytics.deadline_by', { date: formatDeadline(course.pace.deadline_date) }) }}
                                </template>
                            </p>
                            <p v-else class="mt-3 text-xs text-neutral-400">{{ t('analytics.no_deadline') }}</p>

                            <div v-if="course.pace && course.pace.overall_ratio !== null" class="mt-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-neutral-400">{{ t('analytics.pace_label') }}</span>
                                    <span class="font-medium" :class="describePace(course.pace)?.tone === 'behind' ? 'text-amber-700' : 'text-neutral-600'">
                                        {{ describePace(course.pace)?.text }}
                                    </span>
                                </div>
                                <PaceGauge class="mt-1.5" :ratio="course.pace.overall_ratio" />
                            </div>
                        </li>
                    </ul>
                </div>
            </template>
        </div>
    </AppShell>
</template>
