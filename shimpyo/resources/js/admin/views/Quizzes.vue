<script setup>
import { ref, onMounted } from 'vue';
import { get, post, put, del } from '../api';

const items = ref([]);
const err = ref(null);

const KEYS = {
  distance_km:    '오늘 주행 거리 (km)',
  driving_time:   '오늘 운전 시간 (분)',
  avg_speed:      '평균 속도 (km/h)',
  passengers:     '탑승 인원 (명)',
  soc:            '충전 시작 배터리 (%)',
  charge_minutes: '예상 충전 시간 (분)',
  charging_power: '충전기 출력 (kW)',
};

const blank = () => ({ question: '', data_key: 'distance_km', unit: 'km', spread: 20, active: true });
const draft = ref(blank());

const load = async () => { items.value = await get('/quiz-templates'); };
onMounted(load);

const save = async (q) => {
  err.value = null;
  try {
    await put(`/quiz-templates/${q.id}`, {
      question: q.question, data_key: q.data_key, unit: q.unit,
      spread: Number(q.spread), active: !!q.active,
    });
  } catch (e) { err.value = '저장하지 못했어요. 오답 폭은 5에서 60 사이여야 합니다.'; }
};

const add = async () => {
  err.value = null;
  if (!draft.value.question.trim()) { err.value = '문제를 적어주세요.'; return; }
  try { await post('/quiz-templates', draft.value); draft.value = blank(); await load(); }
  catch (e) { err.value = '추가하지 못했어요. 값을 확인해 주세요.'; }
};

const remove = async (q) => {
  if (!confirm('이 문제를 지울까요?')) return;
  await del(`/quiz-templates/${q.id}`); await load();
};
</script>

<template>
  <p class="intro">
    놀이 모드가 오늘 주행 데이터를 정답으로 넣어 문제를 만듭니다.
    오답 폭은 정답 주변에 얼마나 떨어진 보기를 만들지 정합니다. 값이 작을수록 어려워집니다.
  </p>
  <p v-if="err" class="err">{{ err }}</p>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th class="w">문제</th><th>정답으로 쓸 값</th><th>단위</th><th>오답 폭</th><th>사용</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="q in items" :key="q.id" :class="{ off: !q.active }">
          <td class="w"><input v-model="q.question" @change="save(q)"></td>
          <td><select v-model="q.data_key" @change="save(q)"><option v-for="(l,k) in KEYS" :key="k" :value="k">{{ l }}</option></select></td>
          <td><input class="s" v-model="q.unit" @change="save(q)"></td>
          <td class="mid"><input class="n" type="number" min="5" max="60" v-model.number="q.spread" @change="save(q)"><small>%</small></td>
          <td class="mid"><input type="checkbox" v-model="q.active" @change="save(q)"></td>
          <td class="mid"><button class="x" @click="remove(q)">삭제</button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <section class="add">
    <h2>문제 추가</h2>
    <div class="form">
      <input class="grow" v-model="draft.question" placeholder="예: 오늘 지나온 터널은 몇 개일까요?">
      <select v-model="draft.data_key"><option v-for="(l,k) in KEYS" :key="k" :value="k">{{ l }}</option></select>
      <input class="s" v-model="draft.unit" placeholder="단위">
      <input class="n" type="number" v-model.number="draft.spread" placeholder="%">
      <button class="go" @click="add">추가</button>
    </div>
  </section>
</template>

<style scoped>
.intro { font-size: 14px; color: var(--quiet); margin-bottom: 14px; max-width: 72ch; line-height: 1.6; }
.table-wrap { overflow-x: auto; background: var(--paper-2); border: 1px solid var(--line); border-radius: var(--r-panel); }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 760px; }
th, td { padding: 9px 10px; border-bottom: 1px solid var(--grid); text-align: left; }
thead th { font-size: 12px; color: var(--quiet); font-weight: 500; }
tbody tr.off { opacity: .45; }
.w { min-width: 320px; }
.w input { width: 100%; }
.mid { text-align: center; white-space: nowrap; }
.mid small { color: var(--quiet); margin-left: 4px; }

input, select { font: inherit; padding: 7px 9px; border: 1px solid var(--line); border-radius: 6px; background: var(--paper); color: var(--ink); max-width: 100%; }
input:focus, select:focus { border-color: var(--moss); outline: none; }
.n { width: 68px; text-align: right; font-variant-numeric: tabular-nums; }
.s { width: 78px; }
input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--moss); padding: 0; }

.x { border: 0; background: none; color: var(--bad); cursor: pointer; font-size: 13px; text-decoration: underline; text-underline-offset: 3px; }
.add { margin-top: 22px; }
h2 { font-size: 16px; font-weight: 600; margin-bottom: 10px; }
.form { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.grow { flex: 1 1 280px; }
.go { border: 0; background: var(--moss); color: #fff; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
.err { color: var(--bad); margin-bottom: 10px; font-size: 14px; }
</style>
