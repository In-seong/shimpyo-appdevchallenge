<script setup>
import { ref, onMounted, computed } from 'vue';
import { api } from '../api';

const props = defineProps({ session: Object });
defineEmits(['back']);

const questions = ref([]);
const idx       = ref(0);
const chosen    = ref(null);
const loading   = ref(true);

/** 좌석별 점수 — 누가 맞혔는지 번갈아 기록 */
const score = ref({ front: 0, rear: 0 });
const turn  = ref('front');

const seats = computed(() => props.session.seats);
const hasRear = computed(() => seats.value.rearLeft || seats.value.rearRight);

const q = computed(() => questions.value[idx.value] ?? null);
const done = computed(() => !loading.value && idx.value >= questions.value.length);

onMounted(async () => {
  questions.value = (await api.quiz(props.session.session_id)).questions;
  loading.value = false;
});

const answer = (c) => {
  if (chosen.value !== null) return;
  chosen.value = c;

  if (c === q.value.answer) score.value[turn.value] += 1;

  setTimeout(() => {
    chosen.value = null;
    idx.value += 1;
    if (hasRear.value) turn.value = turn.value === 'front' ? 'rear' : 'front';
  }, 1100);
};

const restart = () => { idx.value = 0; chosen.value = null; score.value = { front: 0, rear: 0 }; };

const stateOf = (c) => {
  if (chosen.value === null) return '';
  if (c === q.value.answer) return 'right';
  if (c === chosen.value) return 'wrong';
  return 'faded';
};
</script>

<template>
  <div class="screen">
    <div class="head">
      <div class="progress" v-if="q">
        <span class="num">{{ q.no }}</span><span class="of">/ {{ questions.length }}</span>
        <div class="bar"><span :style="{ width: (idx / questions.length) * 100 + '%' }"></span></div>
      </div>
      <button class="back" @click="$emit('back')">다른 모드 고르기</button>
    </div>

    <p v-if="loading" class="empty">오늘 주행 기록으로 문제를 만드는 중이에요</p>

    <div v-else-if="done" class="result">
      <p class="result-k">모두 풀었어요</p>
      <p class="result-score">
        <span class="num">{{ score.front }}</span><span class="of">점</span>
        <template v-if="hasRear">
          <span class="vs">대</span>
          <span class="num">{{ score.rear }}</span><span class="of">점</span>
        </template>
      </p>
      <p class="result-sub" v-if="hasRear">앞좌석 {{ score.front }}점 · 뒷좌석 {{ score.rear }}점</p>
      <button class="again" @click="restart">한 판 더</button>
    </div>

    <div v-else-if="q" class="quiz">
      <p v-if="hasRear" class="turn">{{ turn === 'front' ? '앞좌석' : '뒷좌석' }} 차례</p>
      <h1>{{ q.question }}</h1>

      <div class="choices">
        <button v-for="c in q.choices" :key="c" :class="stateOf(c)" @click="answer(c)">
          <span class="num">{{ c }}</span><span class="unit">{{ q.unit }}</span>
        </button>
      </div>
    </div>

    <div class="board" v-if="!loading && !done">
      <span>앞좌석 <b class="num">{{ score.front }}</b></span>
      <span v-if="hasRear">뒷좌석 <b class="num">{{ score.rear }}</b></span>
    </div>
  </div>
</template>

<style scoped>
.screen { flex: 1; display: flex; flex-direction: column; padding: 22px 40px 26px; gap: 16px; min-height: 0; overflow-y: auto; }

.head { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.progress { display: flex; align-items: baseline; gap: 8px; min-width: 220px; }
.progress .num { font-size: 26px; font-weight: 600; }
.progress .of { font-size: var(--fs-small); color: var(--quiet); }
.bar { flex: 1; height: 5px; background: var(--paper-sunk); border-radius: 999px; overflow: hidden; margin-left: 10px; align-self: center; }
.bar span { display: block; height: 100%; background: var(--moss); transition: width .5s var(--ease); }

.quiz { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 22px; }
.turn { font-size: var(--fs-small); color: var(--moss); font-weight: 600; }
h1 { font-size: clamp(26px, 3.1vw, 44px); font-weight: 600; letter-spacing: -.035em; line-height: 1.3; text-wrap: balance; max-width: 22ch; }

.choices { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.choices button {
  min-height: var(--tap);
  border-radius: var(--r-panel);
  border: 1px solid var(--line);
  background: var(--paper-2);
  cursor: pointer;
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 5px;
  transition: background .2s var(--ease), border-color .2s var(--ease), opacity .2s;
}
.choices .num { font-size: 27px; font-weight: 600; letter-spacing: -.03em; }
.choices .unit { font-size: 14px; color: var(--quiet); }
.choices button:hover { border-color: var(--moss); }
.choices .right { background: var(--moss); border-color: var(--moss); color: #fff; }
.choices .right .unit { color: rgba(255,255,255,.75); }
.choices .wrong { background: #F6E2DC; border-color: var(--alert); color: var(--alert); }
.choices .faded { opacity: .4; }

.board { display: flex; gap: 20px; justify-content: flex-end; font-size: var(--fs-small); color: var(--quiet); }
.board b { color: var(--ink); font-size: 18px; margin-left: 4px; }

.result { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
.result-k { color: var(--quiet); font-size: var(--fs-small); }
.result-score { display: flex; align-items: baseline; gap: 7px; }
.result-score .num { font-size: clamp(48px, 6vw, 86px); font-weight: 300; letter-spacing: -.045em; }
.result-score .of { font-size: 19px; color: var(--quiet); }
.result-score .vs { font-size: 19px; color: var(--quiet); margin: 0 10px; }
.result-sub { color: var(--quiet); font-size: var(--fs-small); }
.again {
  margin-top: 18px; min-height: var(--tap); padding: 0 40px;
  border: 0; border-radius: var(--r-chip); background: var(--moss); color: #fff;
  font-size: 19px; font-weight: 600; cursor: pointer;
}

.empty { color: var(--quiet); padding: 40px 0; }
.back { background: none; border: 0; color: var(--quiet); text-decoration: underline; text-underline-offset: 5px; cursor: pointer; font-size: var(--fs-small); white-space: nowrap; }

@media (max-width: 760px) {
  .screen { padding: 18px 16px 24px; }
  .choices { grid-template-columns: repeat(2, 1fr); }
}
</style>
