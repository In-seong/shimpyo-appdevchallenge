<script setup>
import ModeIcon from '../components/ModeIcon.vue';
import { minutes } from '../api';

const props = defineProps({ session: Object, remaining: Number });
defineEmits(['pick']);

const copy = {
  content: { label: '콘텐츠', desc: '남은 시간에 맞춘 재생목록' },
  rest:    { label: '휴식',   desc: '시트·조명·공조를 함께 조절' },
  check:   { label: '점검',   desc: '차량 상태와 다음 구간 확인' },
  play:    { label: '놀이',   desc: '주행 기록으로 만든 퀴즈' },
};

const ranked = props.session.recommend.ranked;
const top = props.session.recommend.recommended;
</script>

<template>
  <div class="screen">
    <h1>무엇을 하시겠어요?</h1>
    <p class="sub"><span class="num">{{ minutes(remaining) }}</span>분 동안 할 수 있는 것들이에요</p>

    <div class="grid">
      <button v-for="r in ranked" :key="r.mode" class="tile"
              :class="{ top: r.mode === top }" @click="$emit('pick', r.mode)">
        <span v-if="r.mode === top" class="badge">추천</span>
        <ModeIcon :mode="r.mode" :size="36" />
        <strong>{{ copy[r.mode].label }}</strong>
        <span class="desc">{{ copy[r.mode].desc }}</span>
        <span class="fit">{{ minutes(remaining) }}분에 맞춤</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.screen {
  flex: 1;
  padding: 34px 48px 40px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-height: 0;
  overflow-y: auto;
}

h1 { font-size: var(--fs-title); font-weight: 600; letter-spacing: -.035em; }
.sub { color: var(--quiet); font-size: var(--fs-small); margin-bottom: 22px; }
.sub .num { font-weight: 600; color: var(--ink); font-size: 17px; }

.grid {
  flex: 1;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  grid-template-rows: repeat(2, 1fr);
  gap: 14px;
  min-height: 320px;
}

.tile {
  position: relative;
  text-align: left;
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-radius: var(--r-card);
  padding: 22px 24px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 6px;
  justify-content: center;
  color: var(--ink);
  transition: border-color .18s var(--ease), background .18s var(--ease);
}
.tile:hover { border-color: var(--moss); }
.tile.top { border-color: var(--moss); background: var(--moss-soft); }

.badge {
  position: absolute;
  top: 16px; right: 18px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
  background: var(--moss);
  padding: 4px 11px;
  border-radius: var(--r-chip);
}

.tile svg { color: var(--moss); }
strong { font-size: 25px; font-weight: 700; letter-spacing: -.03em; margin-top: 4px; }
.desc { font-size: var(--fs-body); color: var(--ink); }
.fit { font-size: 13px; color: var(--quiet); margin-top: 4px; }

@media (max-width: 760px) {
  .screen { padding: 22px 16px 36px; }
  .grid { grid-template-columns: 1fr; grid-template-rows: none; }
  .tile { padding: 18px 18px; }
  strong { font-size: 21px; }
}
</style>
