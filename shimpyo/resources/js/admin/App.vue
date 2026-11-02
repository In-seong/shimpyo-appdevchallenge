<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { post } from './api';

const route = useRoute();
const resetting = ref(false);

const nav = [
  { to: '/dashboard', label: '대시보드' },
  { to: '/rules',     label: '추천 규칙' },
  { to: '/sessions',  label: '충전 세션' },
  { to: '/contents',  label: '콘텐츠' },
  { to: '/quizzes',   label: '퀴즈 템플릿' },
];

const resetDemo = async () => {
  if (!confirm('세션 기록과 피드백, 개인화 가중치를 지웁니다. 추천 규칙과 콘텐츠는 그대로 둡니다. 계속할까요?')) return;
  resetting.value = true;
  await post('/reset-demo');
  resetting.value = false;
  location.reload();
};
</script>

<template>
  <div class="wrap">
    <nav class="side">
      <div class="brand">
        <span class="mark" aria-hidden="true">,</span>
        <span>
          <strong>쉼표</strong>
          <small>관리자</small>
        </span>
      </div>

      <ul>
        <li v-for="n in nav" :key="n.to">
          <RouterLink :to="n.to" :class="{ on: route.path === n.to }">{{ n.label }}</RouterLink>
        </li>
      </ul>

      <div class="foot">
        <a class="link" href="https://shimpyo.revuplan.com" target="_blank" rel="noopener">차량 앱 열기</a>
        <button class="reset" :disabled="resetting" @click="resetDemo">
          {{ resetting ? '지우는 중' : '데모 기록 초기화' }}
        </button>
      </div>
    </nav>

    <main class="main">
      <header class="head">
        <h1>{{ route.meta.title }}</h1>
      </header>
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.wrap { display: flex; height: 100%; }

.side {
  width: 232px;
  flex: 0 0 232px;
  background: var(--paper-2);
  border-right: 1px solid var(--line);
  padding: 22px 16px;
  display: flex;
  flex-direction: column;
  gap: 26px;
}

.brand { display: flex; align-items: center; gap: 10px; padding: 0 8px; }
.brand .mark { font-size: 38px; line-height: 1; font-weight: 700; color: var(--moss); align-self: flex-start; transform: translateY(-9px); }
.brand strong { display: block; font-size: 17px; letter-spacing: -.02em; }
.brand small { display: block; font-size: 12px; color: var(--quiet); }

.side ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 2px; }
.side a {
  display: block;
  padding: 11px 12px;
  border-radius: var(--r-panel);
  text-decoration: none;
  color: var(--ink);
  font-size: 14.5px;
  font-weight: 500;
}
.side a:hover { background: var(--paper); }
.side a.on { background: var(--moss-soft); color: #11543A; }

.foot { margin-top: auto; display: flex; flex-direction: column; gap: 9px; }
.link { font-size: 13px; color: var(--rest); padding: 0 12px; }
.reset {
  border: 1px solid var(--line);
  background: var(--paper);
  border-radius: var(--r-panel);
  padding: 10px;
  font-size: 13px;
  cursor: pointer;
}
.reset:disabled { opacity: .5; cursor: default; }

.main { flex: 1; min-width: 0; overflow-y: auto; padding: 26px 30px 48px; }
.head { margin-bottom: 20px; }
h1 { font-size: 27px; font-weight: 600; letter-spacing: -.03em; }

@media (max-width: 820px) {
  .wrap { flex-direction: column; height: auto; min-height: 100%; }
  .side { width: 100%; flex: none; border-right: 0; border-bottom: 1px solid var(--line); }
  .side ul { flex-direction: row; flex-wrap: wrap; }
  .foot { margin-top: 14px; flex-direction: row; align-items: center; }
  .main { padding: 20px 16px 40px; }
}
</style>
