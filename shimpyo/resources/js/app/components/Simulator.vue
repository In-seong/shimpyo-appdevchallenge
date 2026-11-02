<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({ modelValue: Object, speed: Number, scores: Object });
const emit = defineEmits(['update:modelValue', 'update:speed', 'apply', 'scenario']);

const s = reactive({ ...props.modelValue });

watch(s, () => emit('update:modelValue', { ...s }), { deep: true });

const scenarios = [
  { key: 'holiday',  label: '명절 귀성',  hint: '가족 동승 · 장거리' },
  { key: 'weekend',  label: '주말 여행',  hint: '아이들 뒷좌석 · 낮' },
  { key: 'business', label: '출장 이동',  hint: '혼자 · 시간 압박' },
  { key: 'night',    label: '심야 주행',  hint: '혼자 · 새벽 2시' },
  { key: 'warning',  label: '경고 발생',  hint: '타이어 저압' },
];

const modeLabel = { content: '콘텐츠', rest: '휴식', check: '점검', play: '놀이' };
</script>

<template>
  <aside class="sim" aria-label="차량 데이터 시뮬레이터">
    <div class="head">
      <h2>차량 데이터</h2>
      <p>Pleos Connect SDK가 읽어올 값입니다. 바꾸면 추천이 즉시 다시 계산됩니다.</p>
    </div>

    <div class="block">
      <span class="k">상황 프리셋</span>
      <div class="presets">
        <button v-for="sc in scenarios" :key="sc.key" @click="$emit('scenario', sc.key)">
          <strong>{{ sc.label }}</strong>
          <small>{{ sc.hint }}</small>
        </button>
      </div>
    </div>

    <div class="block">
      <label class="row">
        <span class="k">연속 주행 시간<em>Odometer.getTripCurrentDrivingTime()</em></span>
        <span class="v num">{{ (s.driving_time_sec / 3600).toFixed(1) }}시간</span>
      </label>
      <input type="range" min="0" max="18000" step="600" v-model.number="s.driving_time_sec">

      <label class="row">
        <span class="k">현재 시각<em>시스템 시계</em></span>
        <span class="v num">{{ String(s.hour).padStart(2, '0') }}시</span>
      </label>
      <input type="range" min="0" max="23" step="1" v-model.number="s.hour">

      <label class="row">
        <span class="k">배터리 잔량<em>EvBattery.getEvBatteryStateOfCharge()</em></span>
        <span class="v num">{{ s.soc }}%</span>
      </label>
      <input type="range" min="5" max="79" step="1" v-model.number="s.soc">

      <label class="row">
        <span class="k">목표 충전율<em>EvBattery.setEvChargePercentLimit()</em></span>
        <span class="v num">{{ s.target_soc }}%</span>
      </label>
      <input type="range" min="50" max="100" step="5" v-model.number="s.target_soc">

      <label class="row">
        <span class="k">충전기 출력<em>DrivingState.maxChargingPower</em></span>
        <span class="v num">{{ s.max_charging_power }}kW</span>
      </label>
      <input type="range" min="50" max="350" step="50" v-model.number="s.max_charging_power">

      <label class="row">
        <span class="k">도착 시 예상 배터리<em>RouteStateInfo.estimatedSocAtDest</em></span>
        <span class="v num">{{ s.soc_at_dest }}%</span>
      </label>
      <input type="range" min="5" max="80" step="1" v-model.number="s.soc_at_dest" :disabled="!s.navigating">
    </div>

    <div class="block">
      <span class="k">좌석 착좌<em>Seat.getCurrentSeatOccupancy()</em></span>
      <div class="toggles">
        <label><input type="checkbox" v-model="s.seat_passenger"><span>조수석</span></label>
        <label><input type="checkbox" v-model="s.seat_rear_left"><span>뒷좌석 좌</span></label>
        <label><input type="checkbox" v-model="s.seat_rear_right"><span>뒷좌석 우</span></label>
      </div>
    </div>

    <div class="block">
      <span class="k">경고등<em>Safety.getTelltalesInfo()</em></span>
      <div class="toggles">
        <label><input type="checkbox" v-model="s.warn_tire"><span>타이어 저압</span></label>
        <label><input type="checkbox" v-model="s.warn_washer"><span>워셔액</span></label>
        <label><input type="checkbox" v-model="s.warn_oil"><span>엔진오일</span></label>
        <label><input type="checkbox" v-model="s.warn_brake"><span>브레이크액</span></label>
      </div>
    </div>

    <div class="block">
      <span class="k">내비게이션<em>NaviHelper ChargingPlan</em></span>
      <div class="toggles">
        <label><input type="checkbox" v-model="s.navigating"><span>경로 안내 중</span></label>
      </div>
      <p class="note">
        끄면 ChargingPlan이 내려오지 않습니다. 충전 시간은 배터리 변화율로 추정한 폴백 값이 쓰입니다.
      </p>
    </div>

    <div class="block" v-if="scores">
      <span class="k">현재 모드 점수</span>
      <ul class="scores">
        <li v-for="(v, m) in scores" :key="m">
          <span>{{ modeLabel[m] }}</span>
          <span class="num">{{ v > 0 ? '+' : '' }}{{ v }}</span>
        </li>
      </ul>
    </div>

    <div class="block">
      <label class="row">
        <span class="k">데모 배속<em>타이머를 빠르게 돌려 완충까지 확인</em></span>
        <span class="v num">{{ speed }}×</span>
      </label>
      <input type="range" min="1" max="120" step="1" :value="speed"
             @input="$emit('update:speed', Number($event.target.value))">
    </div>

    <button class="apply" @click="$emit('apply')">이 상태로 다시 추천받기</button>
  </aside>
</template>

<style scoped>
.sim {
  width: 380px;
  flex: 0 0 380px;
  background: var(--paper-2);
  border-left: 1px solid var(--line);
  overflow-y: auto;
  padding: 22px;
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.head h2 { font-size: 19px; font-weight: 600; letter-spacing: -.02em; }
.head p { font-size: 13px; color: var(--quiet); margin-top: 5px; line-height: 1.5; }

.block { display: flex; flex-direction: column; gap: 9px; }

.k {
  font-size: 13px;
  font-weight: 500;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.k em {
  font-style: normal;
  font-size: 11px;
  color: var(--quiet);
  font-weight: 400;
  word-break: break-all;
}

.row { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-top: 6px; }
.v { font-size: 15px; font-weight: 600; white-space: nowrap; }

input[type=range] {
  width: 100%;
  accent-color: var(--moss);
  height: 26px;
}
input[type=range]:disabled { opacity: .35; }

.presets { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; }
.presets button {
  text-align: left;
  padding: 10px 12px;
  border-radius: var(--r-panel);
  border: 1px solid var(--line);
  background: var(--paper);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.presets button:hover { border-color: var(--moss); }
.presets strong { font-size: 14px; font-weight: 600; }
.presets small { font-size: 11px; color: var(--quiet); }

.toggles { display: flex; flex-wrap: wrap; gap: 7px; }
.toggles label {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 13px;
  border: 1px solid var(--line);
  border-radius: var(--r-chip);
  background: var(--paper);
  font-size: 13px;
  cursor: pointer;
}
.toggles input { accent-color: var(--moss); width: 16px; height: 16px; }
.toggles label:has(input:checked) { border-color: var(--moss); background: var(--moss-soft); }

.note { font-size: 11.5px; color: var(--quiet); line-height: 1.5; }

.scores { list-style: none; margin: 0; padding: 0; display: grid; gap: 4px; }
.scores li {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  padding: 7px 11px;
  background: var(--paper);
  border-radius: var(--r-row);
}

.apply {
  min-height: 52px;
  border: 0;
  border-radius: var(--r-chip);
  background: var(--moss);
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  font-size: 15px;
}

@media (max-width: 1100px) {
  .sim {
    width: 100%;
    flex: 1 1 auto;
    border-left: 0;
    border-top: 1px solid var(--line);
  }
}
</style>
