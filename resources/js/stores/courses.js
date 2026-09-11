import { defineStore } from 'pinia';
import api from '../lib/api';

export const useCoursesStore = defineStore('courses', {
    state: () => ({
        list: [],
        current: null,
    }),
    actions: {
        async fetchList() {
            const { data } = await api.get('/api/courses');
            this.list = data.data;
        },
        async fetchCourse(id) {
            const { data } = await api.get(`/api/courses/${id}`);
            this.current = data.data;
            return this.current;
        },
        async createCourse(topic, declaredLevel) {
            const { data } = await api.post('/api/courses', { topic, declared_level: declaredLevel });
            this.current = data.data;
            return this.current;
        },
        async submitPlacementTest(courseId, answers) {
            const { data } = await api.post(`/api/courses/${courseId}/placement-test/submit`, { answers });
            this.current = data.data;
            return this.current;
        },
        async fetchStepTest(courseId, stepId) {
            const { data } = await api.get(`/api/courses/${courseId}/steps/${stepId}/test`);
            return data;
        },
        async submitStepTest(courseId, stepId, answers) {
            const { data } = await api.post(`/api/courses/${courseId}/steps/${stepId}/test/submit`, { answers });
            return data;
        },
        async completeStep(courseId, stepId) {
            const { data } = await api.post(`/api/courses/${courseId}/steps/${stepId}/complete`);
            return data;
        },
        async fetchStepChat(courseId, stepId) {
            const { data } = await api.get(`/api/courses/${courseId}/steps/${stepId}/chat`);
            return data.data;
        },
        async sendStepChatMessage(courseId, stepId, message) {
            const { data } = await api.post(`/api/courses/${courseId}/steps/${stepId}/chat`, { message });
            return data.data;
        },
        async retryCourse(courseId) {
            const { data } = await api.post(`/api/courses/${courseId}/retry`);
            this.current = data.data;
            return this.current;
        },
        async pollCourse(courseId, { intervalMs = 2500, timeoutMs = 180000, pending = ['queued', 'generating'] } = {}) {
            const startedAt = Date.now();

            while (true) {
                const course = await this.fetchCourse(courseId);

                if (!pending.includes(course.status)) {
                    return course;
                }

                if (Date.now() - startedAt > timeoutMs) {
                    throw new Error('Generation timed out.');
                }

                await new Promise((resolve) => setTimeout(resolve, intervalMs));
            }
        },
        async pollStepTest(courseId, stepId, { intervalMs = 2500, timeoutMs = 180000 } = {}) {
            const startedAt = Date.now();

            while (true) {
                const result = await this.fetchStepTest(courseId, stepId);

                if (result.status !== 'generating') {
                    return result;
                }

                if (Date.now() - startedAt > timeoutMs) {
                    throw new Error('Generation timed out.');
                }

                await new Promise((resolve) => setTimeout(resolve, intervalMs));
            }
        },
    },
});
