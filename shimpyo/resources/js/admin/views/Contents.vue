<script setup>
import { ref, onMounted } from 'vue';
import { get, post, put, del } from '../api';

const items = ref([]);
const err = ref(null);
const TYPES = { podcast: '팟캐스트', music: '음악', video: '영상' };

const blank = () => ({ type: 'podcast', title: '', creator: '', duration_sec: 600, category: '', accent: 'moss', active: true });
const draft = ref(blank());

const load = async () => { items.value = await get('/contents'); };
onMounted(load);

const save = async (c) => {
  err.value = null;
  try {
    await put(`/contents/${c.id}`, {
      type: c.type, title: c.title, creator: c.creator,
      duration_sec: Number(c.duration_sec), category: c.category, active: !!c.active,
    });
  } catch (e) { err.value = '저장하지 못했어요. 길이는 30초 이상 4시간 이하여야 합니다.'; }
};

const add = async () => {
  err.value = null;
  if (!draft.value.title.trim()) { err.value = '제목을 적어주세요.'; return; }
  try { await post('/contents', draft.value); draft.value = blank(); await load(); }
  catch (e) { err.value = '추가하지 못했어요. 값을 확인해 주세요.'; }
};

const remove = async (c) => {
  if (!confirm(`"${c.title}"을(를) 지울까요?`)) return;
  await del(`/contents/${c.id}`); await load();
};

const mmss = (s) => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
</script>

<template>
  <p class="intro">
    콘텐츠 모드가 남은 충전 시간에 맞춰 여기서 고릅니다. 길이가 다양할수록 시간을 빈틈없이 채웁니다.
  </p>
  <p v-if="err" class="err">{{ err }}</p>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>유형</th><th class="w">제목</th><th>만든이</th><th>길이</th><th>분류</th><th>사용</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="c in items" :key="c.id" :class="{ off: !c.active }">
          <td><select v-model="c.type" @change="save(c)"><option v-for="(l,k) in TYPES" :key="k" :value="k">{{ l }}</option></select></td>
          <td class="w"><input v-model="c.title" @change="save(c)"></td>
          <td><input v-model="c.creator" @change="save(c)"></td>
          <td class="len">
            <input class="n" type="number" v-model.number="c.duration_sec" @change="save(c)">
            <small>{{ mmss(c.duration_sec) }}</small>
          </td>
          <td><input class="s" v-model="c.category" @change="save(c)"></td>
          <td class="mid"><input type="checkbox" v-model="c.active" @change="save(c)"></td>
          <td class="mid"><button class="x" @click="remove(c)">삭제</button></td>
        </tr>
      </tbody>
    </table>
  </div>

  <section class="add">
    <h2>콘텐츠 추가</h2>
    <div class="form">
      <select v-model="draft.type"><option v-for="(l,k) in TYPES" :key="k" :value="k">{{ l }}</option></select>
      <input class="grow" v-model="draft.title" placeholder="제목">
      <input v-model="draft.creator" placeholder="만든이">
      <input class="n" type="number" v-model.number="draft.duration_sec" placeholder="초">
      <input class="s" v-model="draft.category" placeholder="분류">
      <button class="go" @click="add">추가</button>
    </div>
  </section>
</template>

<style scoped>
.intro { font-size: 14px; color: var(--quiet); margin-bottom: 14px; max-width: 72ch; line-height: 1.6; }
.table-wrap { overflow-x: auto; background: var(--paper-2); border: 1px solid var(--line); border-radius: var(--r-panel); }
table { width: 100%; border-collapse: collapse; font-size: 13.5px; min-width: 820px; }
th, td { padding: 9px 10px; border-bottom: 1px solid var(--grid); text-align: left; }
thead th { font-size: 12px; color: var(--quiet); font-weight: 500; }
tbody tr.off { opacity: .45; }
.w { min-width: 260px; }
.w input { width: 100%; }
.mid { text-align: center; }
.len { white-space: nowrap; }
.len small { color: var(--quiet); margin-left: 7px; font-variant-numeric: tabular-nums; }

input, select { font: inherit; padding: 7px 9px; border: 1px solid var(--line); border-radius: 6px; background: var(--paper); color: var(--ink); max-width: 100%; }
input:focus, select:focus { border-color: var(--moss); outline: none; }
.n { width: 82px; text-align: right; font-variant-numeric: tabular-nums; }
.s { width: 96px; }
input[type=checkbox] { width: 17px; height: 17px; accent-color: var(--moss); padding: 0; }

.x { border: 0; background: none; color: var(--bad); cursor: pointer; font-size: 13px; text-decoration: underline; text-underline-offset: 3px; }

.add { margin-top: 22px; }
h2 { font-size: 16px; font-weight: 600; margin-bottom: 10px; }
.form { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.grow { flex: 1 1 220px; }
.go { border: 0; background: var(--moss); color: #fff; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
.err { color: var(--bad); margin-bottom: 10px; font-size: 14px; }
</style>
