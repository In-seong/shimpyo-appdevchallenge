<script setup>
import { ref, computed } from 'vue';
import CommaTimer from '../components/CommaTimer.vue';
import { api } from '../api';

const props = defineProps({ session: Object, remaining: Number, total: Number });
defineEmits(['done', 'back']);

const seatAngle = ref(38);
const light     = ref(10);
const temp      = ref(23);
const lightOff  = ref(false);

/** 완충 1분 전 — 화면이 서서히 밝아지며 깨운다 */
const waking = computed(() => props.remaining <= 60);

const effectiveLight = computed(() => (lightOff.value ? 0 : light.value));

const nudge = async (kind) => {
  if (kind === 'light') {
    lightOff.value = !lightOff.value;
    await api.control('light', lightOff.value ? 0 : light.value);
  }
  if (kind === 'seat') {
    seatAngle.value = seatAngle.value > 30 ? 12 : 38;
    await api.control('seat', seatAngle.value);
  }
  if (kind === 'temp') {
    temp.value = temp.value >= 25 ? 21 : temp.value + 1;
    await api.control('hvac', temp.value);
  }
};
</script>

<template>
  <div class="screen" :class="{ waking }">
    <div class="stage">
      <CommaTimer :remaining="remaining" :total="total" dim :size="300" />

      <p class="state">
        시트 {{ seatAngle }}° 젖힘<span class="dot"></span>조명 {{ effectiveLight }}%<span class="dot"></span>{{ temp }}°C
      </p>
    </div>

    <transition name="wake">
      <div v-if="waking" class="wake-card">
        <p class="wake-title">곧 충전이 끝나요</p>
        <p class="wake-sub">조명을 서서히 밝히고 공조를 켰어요</p>
        <button class="primary" @click="$emit('done')">출발 준비</button>
      </div>
    </transition>

    <div v-if="!waking" class="controls">
      <button @click="nudge('light')">{{ lightOff ? '조명 켜기' : '조명 끄기' }}</button>
      <button @click="nudge('seat')">{{ seatAngle > 30 ? '시트 세우기' : '시트 젖히기' }}</button>
      <button @click="nudge('temp')">온도 {{ temp >= 25 ? '낮추기' : '올리기' }}</button>
      <button class="ghost" @click="$emit('back')">지금 일어나기</button>
    </div>

    <p v-if="!waking" class="stage-note">
      차량 제어 3종은 Vehicle Control SDK가 준비 중이라 2단계 기능입니다. 타이머와 기상 알림은 조회 API만으로 동작합니다.
    </p>
  </div>
</template>

<style scoped>
.screen {
  flex: 1;
  background: #0E1714;
  color: #EAF0EC;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 34px;
  padding: 34px 24px;
  min-height: 0;
  overflow-y: auto;
  transition: background 1.6s var(--ease);
}
.screen.waking { background: #1F2E27; }

.stage { display: flex; flex-direction: column; align-items: center; gap: 18px; }

.state {
  font-size: var(--fs-body);
  color: rgba(234,240,236,.62);
  display: flex;
  align-items: center;
  gap: 12px;
}
.dot { width: 3px; height: 3px; border-radius: 50%; background: rgba(234,240,236,.35); }

.controls { display: flex; gap: 11px; flex-wrap: wrap; justify-content: center; }

.controls button {
  min-height: var(--tap);
  padding: 0 26px;
  border-radius: var(--r-chip);
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.07);
  color: #EAF0EC;
  font-size: var(--fs-body);
  font-weight: 500;
  cursor: pointer;
  transition: background .18s var(--ease);
}
.controls button:hover { background: rgba(255,255,255,.14); }
.controls .ghost { border-color: transparent; color: rgba(234,240,236,.55); }

.wake-card { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 7px; }
.wake-title { font-size: var(--fs-title); font-weight: 600; letter-spacing: -.03em; }
.wake-sub { color: rgba(234,240,236,.6); font-size: var(--fs-body); }

.primary {
  margin-top: 16px;
  min-height: var(--tap);
  padding: 0 46px;
  border: 0;
  border-radius: var(--r-chip);
  background: #8FD3AE;
  color: #0E1714;
  font-size: 20px;
  font-weight: 700;
  cursor: pointer;
}

.stage-note {
  font-size: 13px;
  color: rgba(234,240,236,.45);
  text-align: center;
  max-width: 54ch;
  line-height: 1.6;
}

.wake-enter-active { transition: opacity .9s var(--ease), transform .9s var(--ease); }
.wake-enter-from { opacity: 0; transform: translateY(14px); }
</style>
