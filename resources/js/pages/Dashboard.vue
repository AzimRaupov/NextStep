<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCoursesStore } from '../stores/courses';
import { describePace } from '../lib/format';
import { t } from '../lib/i18n';
import AppShell from '../components/AppShell.vue';
import PaceGauge from '../components/PaceGauge.vue';

const router = useRouter();
const courses = useCoursesStore();

const loadingList = ref(true);

onMounted(async () => {
    await courses.fetchList();
    loadingList.value = false;
});

function openCourse(course) {
    if (course.status === 'queued' || course.status === 'pending_test') {
        router.push(`/courses/${course.id}/test`);
    } else {
        router.push(`/courses/${course.id}`);
    }
}

function progressPercent(course) {
    if (!course.progress || course.progress.total === 0) {
        return 0;
    }

    return Math.round((course.progress.completed / course.progress.total) * 100);
}

const statusStyle = {
    queued: 'bg-neutral-100 text-neutral-500',
    generating: 'bg-neutral-100 text-neutral-500',
    pending_test: 'bg-amber-50 text-amber-700',
    ready: 'bg-neutral-900 text-white',
    failed: 'bg-red-50 text-red-600',
};

const statusLabelKeys = {
    queued: 'dashboard.status_queued',
    generating: 'dashboard.status_generating',
    pending_test: 'dashboard.status_pending_test',
    ready: 'dashboard.status_ready',
    failed: 'dashboard.status_failed',
};
</script>

<template>
    <AppShell active="dashboard">
        <div class="mx-auto max-w-5xl px-6 py-8 sm:py-12">
            <router-link
                to="/courses/new"
                class="flex items-center justify-between gap-4 rounded-2xl border border-neutral-200 bg-neutral-50 p-5 transition hover:border-neutral-900 lg:p-6"
            >
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-neutral-400">{{ t('dashboard.cta_eyebrow') }}</p>
                    <h1 class="mt-1 text-lg font-medium text-neutral-900 lg:text-xl">{{ t('dashboard.cta_title') }}</h1>
                    <p class="mt-1 text-sm text-neutral-500">{{ t('dashboard.cta_subtitle') }}</p>
                </div>
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-lg text-white">+</span>
            </router-link>

            <div v-if="!loadingList && courses.list.length > 0" class="mt-6 flex justify-end">
                <router-link to="/analytics" class="text-xs font-medium text-neutral-500 hover:text-neutral-900">
                    {{ t('dashboard.view_analytics') }}
                </router-link>
            </div>

            <div class="mt-8">
                <h2 class="text-sm font-medium text-neutral-900">{{ t('dashboard.your_courses') }}</h2>

                <p v-if="loadingList" class="mt-4 text-sm text-neutral-400">{{ t('common.loading') }}</p>
                <p v-else-if="courses.list.length === 0" class="mt-4 text-sm text-neutral-400">{{ t('dashboard.no_courses') }}</p>

                <ul v-else class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <li
                        v-for="course in courses.list"
                        :key="course.id"
                        class="cursor-pointer rounded-xl border border-neutral-200 p-4 transition hover:border-neutral-900"
                        @click="openCourse(course)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-neutral-900">{{ course.title || course.topic }}</p>
                                <p v-if="course.level_label" class="mt-0.5 text-xs text-neutral-500">{{ t('dashboard.level_prefix') }} {{ course.level_label }}</p>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium" :class="statusStyle[course.status]">
                                {{ t(statusLabelKeys[course.status]) }}
                            </span>
                        </div>

                        <div v-if="course.progress && course.progress.total > 0" class="mt-3">
                            <div class="flex items-center justify-between font-mono text-xs text-neutral-400">
                                <span>{{ t('dashboard.steps_of', { completed: course.progress.completed, total: course.progress.total }) }}</span>
                                <span>{{ progressPercent(course) }}%</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100">
                                <div class="h-full rounded-full bg-neutral-900" :style="{ width: progressPercent(course) + '%' }"></div>
                            </div>
                        </div>

                        <div v-if="course.pace && course.pace.overall_ratio !== null" class="mt-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-neutral-400">{{ t('dashboard.pace_label') }}</span>
                                <span class="font-medium" :class="describePace(course.pace)?.tone === 'behind' ? 'text-amber-700' : 'text-neutral-600'">
                                    {{ describePace(course.pace)?.text }}
                                </span>
                            </div>
                            <PaceGauge class="mt-1.5" :ratio="course.pace.overall_ratio" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AppShell>
</template>
