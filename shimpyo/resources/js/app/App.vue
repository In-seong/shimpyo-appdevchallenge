<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { api } from './api';
import TopBar from './components/TopBar.vue';
import Simulator from './components/Simulator.vue';
import Recommend from './screens/Recommend.vue';
import ModeSelect from './screens/ModeSelect.vue';
import ContentMode from './screens/ContentMode.vue';
import RestMode from './screens/RestMode.vue';
import CheckMode from './screens/CheckMode.vue';
import PlayMode from './screens/PlayMode.vue';
import Feedback from './screens/Feedback.vue';

/* ── 시뮬레이터 기본값: 명절 귀성 상황 */
const base = {
  driving_time_sec: 8400,
  driving_distance_km: 186.4,
  hour: 14,
  soc: 42,
  target_soc: 80,
  max_charging_power: 200,
  soc_at_dest: 34,
  seat_passenger: true,
  seat_rear_left: true,
  seat_rear_right: false,
  warn_tire: false,
  warn_washer: false,
  warn_oil: false,
  warn_brake: false,
  navigating: true,
  destination: '강릉역',
  next_charger: '안성휴게소',
  remain_distance_km: 164,
  remain_time_sec: 7200,
  charger_count: 6,
};

const presets = {
  holiday:  { driving_time_sec: 8400,  hour: 14, seat_passenger: true,  seat_rear_left: true,  seat_rear_right: true,  warn_tire: false, soc: 42 },
  weekend:  { driving_time_sec: 2400,  hour: 11, seat_passenger: true,  seat_rear_left: true,  seat_rear_right: true,  warn_tire: false, soc: 55 },
  // 내비 목적지 없음 → ChargingPlan 이 내려오지 않아 SoC 변화율 폴백이 쓰인다 (상단에 '추정' 표시)
  business: { driving_time_sec: 2400,  hour: 9,  seat_passenger: false, seat_rear_left: false, seat_rear_right: false, warn_tire: false, soc: 61, navigating: false },
  night:    { driving_time_sec: 10800, hour: 2,  seat_passenger: false, seat_rear_left: false, seat_rear_right: false, warn_tire: false, soc: 23 },
  warning:  { driving_time_sec: 5400,  hour: 16, seat_passenger: true,  seat_rear_left: false, seat_rear_right: false, warn_tire: true,  soc: 38 },
};

const sim       = reactive({ ...base });
const session   = ref(null);
const remaining = ref(0);
const total     = ref(1);
const speed     = ref(10);
const screen    = ref('recommend');
const simOpen   = ref(false);
const skipAsk   = ref(false);
const showFb    = ref(false);
const busy      = ref(true);
const error     = ref(null);

const dim = computed(() => screen.value === 'rest');

let ticker = null;

const startSession = async (scenario = null) => {
  busy.value = true;
  error.value = null;
  showFb.value = false;

  try {
    const data = await api.start({ ...sim }, scenario);
    session.value = data;
    total.value = data.timer.seconds;
    remaining.value = data.timer.seconds;
    screen.value = skipAsk.value ? 'select' : 'recommend';
  } catch (e) {
    error.value = '차량 데이터를 읽지 못했어요. 잠시 후 다시 시도해 주세요.';
  } finally {
    busy.value = false;
  }
};

const applyPreset = (key) => {
  Object.assign(sim, base, presets[key] ?? {});
  startSession(key);
};

const pick = async (mode) => {
  screen.value = mode;
  await api.select(session.value.session_id, mode);
};

const complete = () => { showFb.value = true; };

const reset = () => {
  skipAsk.value = false;
  startSession();
};

onMounted(() => {
  startSession('holiday');

  ticker = setInterval(() => {
    if (!session.value || showFb.value) return;

    remaining.value = Math.max(0, remaining.value - speed.value);

    // 완충 → 출발 → 피드백 1회
    if (remaining.value === 0 && screen.value !== 'recommend' && screen.value !== 'select') {
      showFb.value = true;
    }
  }, 1000);
});

onUnmounted(() => clearInterval(ticker));

/* 시뮬레이터 값이 바뀌면 추천을 다시 계산한다 (추천/선택 화면에서만) */
let debounce = null;
watch(sim, () => {
  if (screen.value !== 'recommend' && screen.value !== 'select') return;
  clearTimeout(debounce);
  debounce = setTimeout(() => startSession(), 400);
}, { deep: true });
</script>

<template>
  <div class="shell" :class="{ dim }">
    <TopBar
      :remaining="remaining"
      :start-soc="session?.timer.startSoc ?? 0"
      :end-soc="session?.timer.endSoc ?? 0"
      :fallback="session?.timer.fallback ?? false"
      :dim="dim"
      :sim-open="simOpen"
      @toggle-sim="simOpen = !simOpen"
      @home="reset"
    />

    <div class="body">
      <main class="stage">
        <p v-if="error" class="notice error">{{ error }}</p>
        <p v-else-if="busy && !session" class="notice">차량 데이터를 읽는 중이에요</p>

        <template v-else-if="session">
          <Recommend
            v-if="screen === 'recommend'"
            :key="'r' + session.session_id"
            :session="session" :remaining="remaining"
            @pick="pick" @skip="skipAsk = true; screen = 'select'"
          />
          <ModeSelect
            v-else-if="screen === 'select'"
            :session="session" :remaining="remaining" @pick="pick"
          />
          <ContentMode v-else-if="screen === 'content'" :session="session" :remaining="remaining" @back="screen = 'select'" />
          <RestMode    v-else-if="screen === 'rest'"    :session="session" :remaining="remaining" :total="total"
                       @done="complete" @back="screen = 'select'" />
          <CheckMode   v-else-if="screen === 'check'"   :session="session" @back="screen = 'select'" />
          <PlayMode    v-else-if="screen === 'play'"    :session="session" @back="screen = 'select'" />

          <Feedback v-if="showFb" :session="session" @close="reset" />
        </template>
      </main>

      <Simulator
        v-if="simOpen"
        v-model="sim"
        v-model:speed="speed"
        :scores="session?.recommend.scores"
        @scenario="applyPreset"
        @apply="startSession()"
      />
    </div>
  </div>
</template>

<style scoped>
.shell {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--paper);
  transition: background .6s var(--ease);
}
.shell.dim { background: #0E1714; }

.body { flex: 1; display: flex; min-height: 0; }

.stage {
  position: relative;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
  min-width: 0;
}

.notice { padding: 48px; color: var(--quiet); }
.notice.error { color: var(--alert); }

@media (max-width: 1100px) {
  .body { flex-direction: column; overflow-y: auto; }
  .stage { min-height: 70vh; }
}
</style>
