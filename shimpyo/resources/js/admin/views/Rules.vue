<script setup>
import { ref, onMounted } from 'vue';
import { get, post, put, del, MODE_LABEL } from '../api';

const rules = ref([]);
const saving = ref(null);
const err = ref(null);

const SIGNALS = {
  driving_time_hours: '연속 주행 시간 (시간)',
  clock_hour:         '현재 시각 (시)',
  rear_occupant:      '뒷좌석 착좌 (0/1)',
  passenger_count:    '탑승 인원 (명)',
  warning_count:      '경고 항목 수 (개)',
  soc_at_dest:        '도착 시 예상 배터리 (%)',
  charge_minutes:     '예상 충전 시간 (분)',
};

const OPS = {
  gte: '이상', lte: '이하', gt: '초과', lt: '미만',
  eq: '같음', between: '사이', wrap: '사이(자정 넘김)',
};

const blank = () => ({
  signal_key: 'driving_time_hours', label: '', source: '',
  operator: 'gte', value_a: 1, value_b: null,
  target_mode: 'rest', weight: 1, is_override: false, active: true, sort: 999,
});

const draft = ref(blank());

const load = async () => { rules.value = await get('/rules'); };
onMounted(load);

const save = async (r) => {
  saving.value = r.id;
  err.value = null;
  try {
    await put(`/rules/${r.id}`, {
      signal_key: r.signal_key, label: r.label, source: r.source,
      operator: r.operator, value_a: r.value_a, value_b: r.value_b,
      target_mode: r.target_mode, weight: Number(r.weight),
      is_override: !!r.is_override, active: !!r.active, sort: Number(r.sort),
    });
  } catch (e) { err.value = '저장하지 못했어요. 값을 확인해 주세요.'; }
  saving.value = null;
};

const add = async () => {
  err.value = null;
  if (!draft.value.label.trim()) { err.value = '규칙 이름을 적어주세요.'; return; }
  try {
    await post('/rules', draft.value);
    draft.value = blank();
    await load();
  } catch (e) { err.value = '추가하지 못했어요. 값을 확인해 주세요.'; }
};

const remove = async (r) => {
  if (!confirm(`"${r.label}" 규칙을 지울까요?`)) return;
  await del(`/rules/${r.id}`);
  await load();
};
</script>

<template>
  <section class="intro">
    <p>
      충전이 시작되면 아래 규칙을 모두 훑어 조건에 맞는 것의 점수를 모드별로 더합니다.
      가장 높은 모드가 추천이 되고, <strong>최우선</strong>으로 표시된 규칙이 걸리면 점수와 무관하게 그 모드가 추천됩니다.
      바꾼 값은 저장 즉시 차량 앱에 반영됩니다.
    </p>
  </section>

  <p v-if="err" class="err">{{ err }}</p>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th class="w-name">규칙</th>
          <th>신호</th>
          <th>조건</th>
          <th>값</th>
          <th>모드</th>
          <th class="num-h">점수</th>
          <th>최우선</th>
          <th>사용</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in rules" :key="r.id" :class="{ off: !r.active }">
          <td class="w-name">
            <input v-model="r.label" @change="save(r)">
            <small>{{ r.source }}</small>
          </td>
          <td>
            <select v-model="r.signal_key" @change="save(r)">
              <option v-for="(l, k) in SIGNALS" :key="k" :value="k">{{ l }}</option>
            </select>
          </td>
          <td>
            <select v-model="r.operator" @change="save(r)">
              <option v-for="(l, k) in OPS" :key="k" :value="k">{{ l }}</option>
            </select>
          </td>
          <td>
            <div class="vals">
              <input class="n" type="number" step="any" v-model.number="r.value_a" @change="save(r)">
              <input v-if="['between','wrap'].includes(r.operator)" class="n"
                     type="number" step="any" v-model.number="r.value_b" @change="save(r)">
            </div>
          </td>
          <td>
            <select v-model="r.target_mode" @change="save(r)">
              <option v-for="(l, k) in MODE_LABEL" :key="k" :value="k">{{ l }}</option>
            </select>
          </td>
          <td class="num-c">
            <input class="n" type="number" min="-10" max="10" v-model.number="r.weight" @change="save(r)">
          </td>
          <td class="mid"><input type="checkbox" v-model="r.is_override" @change="save(r)"></td>
          <td class="mid"><input type="checkbox" v-model="r.active" @change="save(r)"></td>
          <td class="mid">
            <span v-if="saving === r.id" class="saving">저장 중</span>
            <button v-else class="x" @click="remove(r)" :aria-label="`${r.label} 삭제`">삭제</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <section class="add">
    <h2>규칙 추가</h2>
    <div class="form">
      <input class="grow" v-model="draft.label" placeholder="예: 비 오는 날 → 콘텐츠">
      <select v-model="draft.signal_key"><option v-for="(l, k) in SIGNALS" :key="k" :value="k">{{ l }}</option></select>
      <select v-model="draft.operator"><option v-for="(l, k) in OPS" :key="k" :value="k">{{ l }}</option></select>
      <input class="n" type="number" step="any" v-model.number="draft.value_a" placeholder="값">
      <input v-if="['between','wrap'].includes(draft.operator)" class="n" type="number" step="any"
             v-model.number="draft.value_b" placeholder="값2">
      <select v-model="draft.target_mode"><option v-for="(l, k) in MODE_LABEL" :key="k" :value="k">{{ l }}</option></select>
      <input class="n" type="number" v-model.number="draft.weight" placeholder="점수">
      <button class="go" @click="add">추가</button>
    </div>
  </section>
</template>

<style scoped>
.intro { margin-bottom: 16px; }
.intro p { font-size: 14px; color: var(--quiet); line-height: 1.6; max-width: 76ch; }
.intro strong { color: var(--ink); }

.table-wrap { overflow-x: auto; background: var(--paper-2); border: 1px solid var(--line); border-radius: var(--r-panel); }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 980px; }
th, td { padding: 9px 10px; border-bottom: 1px solid var(--grid); text-align: left; vertical-align: middle; }
thead th { font-size: 12px; color: var(--quiet); font-weight: 500; white-space: nowrap; }
tbody tr.off { opacity: .45; }
.w-name { min-width: 230px; }
.w-name small { display: block; font-size: 11px; color: var(--quiet); margin-top: 3px; word-break: break-all; }
.mid { text-align: center; }
.num-h, .num-c { text-align: center; }

input, select {
  font: inherit;
  padding: 7px 9px;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: var(--paper);
  color: var(--ink);
  max-width: 100%;
}
input:focus, select:focus { border-color: var(--moss); outline: none; }
.w-name input { width: 100%; }
.n { width: 74px; font-variant-numeric: tabular-nums; text-align: right; }
.vals { display: flex; gap: 5px; align-items: center; }
input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--moss); padding: 0; }

.x { border: 0; background: none; color: var(--bad); cursor: pointer; font-size: 13px; text-decoration: underline; text-underline-offset: 3px; }
.saving { font-size: 12px; color: var(--quiet); }

.add { margin-top: 22px; }
h2 { font-size: 16px; font-weight: 600; margin-bottom: 10px; }
.form { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.grow { flex: 1 1 240px; }
.go { border: 0; background: var(--moss); color: #fff; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }

.err { color: var(--bad); margin-bottom: 10px; font-size: 14px; }
</style>
