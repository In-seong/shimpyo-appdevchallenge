<script setup>
import ModeIcon from '../components/ModeIcon.vue';
import { minutes } from '../api';

const props = defineProps({ session: Object, remaining: Number });

/* 추천 이유와 같은 시점(충전 시작)의 값을 써야 숫자가 어긋나지 않는다 */
const planned = props.session.timer.seconds;
defineEmits(['pick', 'skip']);

const copy = {
  content: { label: '콘텐츠', desc: '남은 시간에 길이를 맞춘 재생목록을 만들어요' },
  rest:    { label: '휴식',   desc: '시트를 젖히고 완충 1분 전에 깨워드려요' },
  check:   { label: '점검',   desc: '차량 상태와 다음 구간을 함께 확인해요' },
  play:    { label: '놀이',   desc: '오늘 달린 기록으로 퀴즈를 내드려요' },
};

const top = props.session.recommend.recommended;
const others = props.session.recommend.ranked
  .filter((r) => r.mode !== top)
  .map((r) => r.mode);
</script>

<template>
  <div class="screen">
    <p class="reason">{{ session.recommend.reason }}</p>

    <div class="left">
      <div class="others">
        <span class="kicker">다른 선택</span>
        <div class="chips">
          <button v-for="m in others" :key="m" class="chip" @click="$emit('pick', m)">
            <ModeIcon :mode="m" :size="26" />
            <span>{{ copy[m].label }}</span>
          </button>
        </div>
      </div>

      <button class="skip" @click="$emit('skip')">오늘은 추천 안 받기</button>
    </div>

    <div class="card">
      <div class="card-head">
        <span class="badge">추천</span>
        <ModeIcon :mode="top" :size="40" />
      </div>

      <h1>{{ copy[top].label }}</h1>
      <p class="desc">{{ copy[top].desc }}</p>

      <p class="fit"><span class="num">{{ minutes(planned) }}</span>분에 맞춰 준비했어요</p>

      <button class="go" @click="$emit('pick', top)">시작하기</button>
    </div>
  </div>
</template>

<style scoped>
.screen {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr minmax(380px, 38%);
  grid-template-areas:
    'reason card'
    'left   card';
  grid-template-rows: auto auto;
  gap: 28px 48px;
  padding: 44px 48px;
  align-content: center;
  min-height: 0;
  overflow-y: auto;
}

.left { grid-area: left; display: flex; flex-direction: column; justify-content: flex-start; gap: 26px; }

.reason {
  grid-area: reason;
  align-self: end;
  font-size: var(--fs-title);
  font-weight: 600;
  letter-spacing: -.035em;
  line-height: 1.32;
  max-width: 24ch;
  text-wrap: balance;
}

.kicker { font-size: var(--fs-small); color: var(--quiet); display: block; margin-bottom: 12px; }

.chips { display: flex; gap: 11px; flex-wrap: wrap; }

.chip {
  min-height: var(--tap);
  display: inline-flex;
  align-items: center;
  gap: 11px;
  padding: 0 24px 0 20px;
  border-radius: var(--r-chip);
  border: 1px solid var(--line);
  background: var(--paper-2);
  cursor: pointer;
  font-size: var(--fs-body);
  font-weight: 500;
  color: var(--ink);
  transition: border-color .18s var(--ease), background .18s var(--ease);
}
.chip:hover { border-color: var(--moss); background: var(--moss-soft); }

.skip {
  align-self: flex-start;
  background: none;
  border: 0;
  padding: 10px 2px;
  color: var(--quiet);
  text-decoration: underline;
  text-underline-offset: 5px;
  cursor: pointer;
  font-size: var(--fs-small);
}

.card {
  grid-area: card;
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-radius: var(--r-card);
  padding: 34px 34px 28px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
  box-shadow: 0 1px 0 var(--line);
}

.card-head { display: flex; align-items: center; justify-content: space-between; color: var(--moss); }

.badge {
  font-size: 13px;
  font-weight: 600;
  color: #fff;
  background: var(--moss);
  padding: 5px 13px;
  border-radius: var(--r-chip);
}

h1 { font-size: clamp(34px, 3.4vw, 50px); font-weight: 700; letter-spacing: -.045em; margin-top: 10px; }
.desc { font-size: var(--fs-lead); color: var(--ink); line-height: 1.45; max-width: 24ch; }
.fit { font-size: var(--fs-small); color: var(--quiet); margin-top: 4px; }
.fit .num { font-size: 19px; font-weight: 600; color: var(--ink); }

.go {
  margin-top: 18px;
  min-height: var(--tap);
  border: 0;
  border-radius: var(--r-chip);
  background: var(--moss);
  color: #fff;
  font-size: 21px;
  font-weight: 600;
  cursor: pointer;
  transition: background .18s var(--ease);
}
.go:hover { background: #14603D; }

@media (max-width: 900px) {
  /* 추천 이유 → 추천 카드 → 다른 선택 순으로 읽힌다 */
  .screen {
    grid-template-columns: 1fr;
    grid-template-areas: 'reason' 'card' 'left';
    grid-template-rows: none;
    gap: 24px;
    padding: 26px 18px 40px;
    align-content: start;
  }
  .card { padding: 26px 22px 22px; }
  .reason { max-width: none; align-self: start; }
}
</style>
