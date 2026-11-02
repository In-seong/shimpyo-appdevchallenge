import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Dashboard from './views/Dashboard.vue';
import Rules from './views/Rules.vue';
import Sessions from './views/Sessions.vue';
import Contents from './views/Contents.vue';
import Quizzes from './views/Quizzes.vue';

/**
 * 관리자는 두 가지 주소에서 열린다.
 *   shimpyo-admin.revuplan.com/  → base '/'
 *   shimpyo.revuplan.com/admin/  → base '/admin'  (로컬 개발용)
 */
const base = location.hostname.startsWith('shimpyo-admin') ? '/' : '/admin';

const router = createRouter({
  history: createWebHistory(base),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/dashboard', component: Dashboard, meta: { title: '대시보드' } },
    { path: '/rules',     component: Rules,     meta: { title: '추천 규칙' } },
    { path: '/sessions',  component: Sessions,  meta: { title: '충전 세션' } },
    { path: '/contents',  component: Contents,  meta: { title: '콘텐츠' } },
    { path: '/quizzes',   component: Quizzes,   meta: { title: '퀴즈 템플릿' } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
});

createApp(App).use(router).mount('#admin');
