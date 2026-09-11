import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../pages/Login.vue';
import Register from '../pages/Register.vue';
import Dashboard from '../pages/Dashboard.vue';
import NewCourse from '../pages/NewCourse.vue';
import Analytics from '../pages/Analytics.vue';
import Profile from '../pages/Profile.vue';
import PlacementTest from '../pages/PlacementTest.vue';
import CourseShow from '../pages/CourseShow.vue';
import StepTest from '../pages/StepTest.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
        { path: '/login', name: 'login', component: Login, meta: { guestOnly: true } },
        { path: '/register', name: 'register', component: Register, meta: { guestOnly: true } },
        { path: '/analytics', name: 'analytics', component: Analytics, meta: { requiresAuth: true } },
        { path: '/profile', name: 'profile', component: Profile, meta: { requiresAuth: true } },
        { path: '/courses/new', name: 'new-course', component: NewCourse, meta: { requiresAuth: true } },
        { path: '/courses/:id/test', name: 'placement-test', component: PlacementTest, meta: { requiresAuth: true } },
        { path: '/courses/:id', name: 'course-show', component: CourseShow, meta: { requiresAuth: true } },
        { path: '/courses/:id/steps/:stepId/test', name: 'step-test', component: StepTest, meta: { requiresAuth: true } },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.checked) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
