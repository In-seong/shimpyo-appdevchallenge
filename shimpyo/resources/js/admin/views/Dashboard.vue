<script setup>
import { ref, computed, onMounted } from 'vue';
import { get, MODES, MODE_LABEL, pct } from '../api';
import GroupedBars from '../charts/GroupedBars.vue';
import TrendLine from '../charts/TrendLine.vue';

const d = ref(null);
const err = ref(null);

onMounted(async () => {
  try { d.value = await get('/stats'); }
  catch (e) { err.value = '지표를 불러오지 못했어요. 새로고침해 주세요.'; }
});

const rows = computed(() =>
  MODES.map((m) => ({
    label: MODE_LABEL[m],
    a: d.value.by_recommended[m],
    b: d.value.by_mode[m],
  }))
);

const fbRows = computed(() =>
  (d.value.feedback_by_mode ?? []).map((r) => ({
    ...r,
    label: MODE_LABEL[r.mode] ?? r.mode,
    total: Number(r.good) + Number(r.bad),
  }))
);
</script>

<template>
  <p v-if="err" class="err">{{ err }}</p>
  <p v-else-if="!d" class="loading">불러오는 중</p>

  <div v-else class="page">
    <section class="tiles">
      <article>
        <span class="k">충전 세션</span>
        <p class="v num">{{ d.sessions }}</p>
        <span class="n">모드를 고른 세션 {{ d.selected }}건</span>
      </article>
      <article>
        <span class="k">추천 수락률</span>
        <p class="v num">{{ pct(d.accept_rate) }}</p>
        <span class="n">추천한 모드를 그대로 고른 비율</span>
      </article>
      <article>
        <span class="k">만족도</span>
        <p class="v num">{{ pct(d.satisfaction) }}</p>
        <span class="n">좋았어요 {{ d.feedback.good }} · 별로였어요 {{ d.feedback.bad }}</span>
      </article>
      <article>
        <span class="k">타이머 폴백 비율</span>
        <p class="v num">{{ pct(d.fallback_rate) }}</p>
        <span class="n">ChargingPlan 없이 배터리 변화율로 추정한 세션</span>
      </article>
    </section>

    <div class="two">
      <section class="panel">
        <header>
          <h2>추천과 실제 선택</h2>
          <p>엔진이 고른 모드와 사용자가 고른 모드가 얼마나 겹치는지 봅니다.</p>
        </header>
        <GroupedBars :rows="rows" :series="['추천', '실제 선택']" />
      </section>

      <section class="panel">
        <header>
          <h2>일별 충전 세션</h2>
          <p>최근 14일</p>
        </header>
        <TrendLine :points="d.daily" />
      </section>
    </div>

    <section class="panel">
      <header>
        <h2>모드별 피드백</h2>
        <p>출발 직후 받은 응답입니다. 이 값이 다음 추천의 가중치를 바꿉니다.</p>
      </header>

      <table v-if="fbRows.length" class="tbl">
        <thead>
          <tr><th>모드</th><th>좋았어요</th><th>별로였어요</th><th>만족도</th></tr>
        </thead>
        <tbody>
          <tr v-for="r in fbRows" :key="r.mode">
            <th>{{ r.label }}</th>
            <td class="num">{{ r.good }}</td>
            <td class="num">{{ r.bad }}</td>
            <td class="num">{{ r.total ? Math.round((r.good / r.total) * 100) + '%' : '—' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="empty">아직 받은 피드백이 없어요. 차량 앱에서 충전을 끝내면 쌓입니다.</p>
    </section>
  </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 18px; }

.tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.tiles article {
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-radius: var(--r-panel);
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.k { font-size: 13px; color: var(--quiet); }
.v { font-size: 38px; font-weight: 300; letter-spacing: -.04em; line-height: 1.15; }
.n { font-size: 12px; color: var(--quiet); }

.two { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

.panel {
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-radius: var(--r-panel);
  padding: 20px 22px 22px;
}
.panel header { margin-bottom: 16px; }
h2 { font-size: 17px; font-weight: 600; letter-spacing: -.02em; }
.panel header p { font-size: 13px; color: var(--quiet); margin-top: 3px; }

.tbl { width: 100%; border-collapse: collapse; font-size: 14px; }
.tbl th, .tbl td { text-align: left; padding: 11px 10px; border-bottom: 1px solid var(--grid); }
.tbl thead th { font-size: 12px; color: var(--quiet); font-weight: 500; }
.tbl tbody th { font-weight: 600; }
.tbl td.num { text-align: right; font-variant-numeric: tabular-nums; }

.empty, .loading { color: var(--quiet); font-size: 14px; padding: 18px 0; }
.err { color: var(--bad); }

@media (max-width: 1000px) {
  .tiles { grid-template-columns: repeat(2, 1fr); }
  .two { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
  .tiles { grid-template-columns: 1fr; }
}
</style>
