<script setup>
import { computed, ref } from 'vue';

const props = defineProps({ session: Object });
defineEmits(['back']);

const booked = ref(false);

const tires = computed(() => props.session.tires);
const items = computed(() => props.session.telltales);
const dest  = computed(() => props.session.destination);
const socAtDest = computed(() => props.session.signals.soc_at_dest);

const warnCount = computed(() => items.value.filter((i) => i.warn).length);

const verdict = computed(() => {
  if (!dest.value) {
    return { tone: 'info', text: '목적지를 설정하면 도착 시 배터리를 미리 알려드려요' };
  }
  if (socAtDest.value === null) {
    return { tone: 'info', text: '경로 정보를 불러오는 중이에요' };
  }
  if (socAtDest.value < 20) {
    return {
      tone: 'warn',
      text: `${dest.value.name}까지 가면 배터리가 ${socAtDest.value}% 남아요. ${dest.value.nextCharger}에서 한 번 더 충전하는 걸 권해요`,
    };
  }
  return {
    tone: 'ok',
    text: `${dest.value.name} 도착 시 배터리 ${socAtDest.value}% 예상이에요. 충분합니다`,
  };
});

const pos = {
  frontLeft:  { x: 34,  y: 44,  label: '앞 좌' },
  frontRight: { x: 126, y: 44,  label: '앞 우' },
  rearLeft:   { x: 34,  y: 196, label: '뒤 좌' },
  rearRight:  { x: 126, y: 196, label: '뒤 우' },
};
</script>

<template>
  <div class="screen">
    <section class="car">
      <h2>타이어</h2>
      <svg viewBox="0 0 160 260" class="top" role="img" aria-label="타이어 4륜 상태">
        <!-- 차량 윗면 -->
        <rect x="38" y="16" width="84" height="228" rx="34" fill="var(--paper-sunk)" />
        <rect x="50" y="46" width="60" height="52" rx="12" fill="var(--paper-2)" />
        <rect x="50" y="150" width="60" height="62" rx="12" fill="var(--paper-2)" />
        <!-- 바퀴 -->
        <g v-for="(p, k) in pos" :key="k">
          <rect :x="p.x - 9" :y="p.y - 18" width="18" height="36" rx="6"
                :fill="tires[k] ? 'var(--caution)' : 'var(--moss)'" />
        </g>
      </svg>
      <ul class="legend">
        <li v-for="(p, k) in pos" :key="k">
          <i :class="tires[k] ? 'warn' : 'ok'"></i>{{ p.label }}
        </li>
      </ul>
      <p class="note">
        Pleos SDK는 실측 공기압이 아니라 저압 경고 점등 여부를 제공합니다. 수치 대신 상태로 표시합니다.
      </p>
    </section>

    <section class="panel">
      <div class="panel-head">
        <h1>{{ warnCount ? `점검할 항목이 ${warnCount}개 있어요` : '모두 정상이에요' }}</h1>
        <button class="back" @click="$emit('back')">다른 모드 고르기</button>
      </div>

      <ul class="rows">
        <li v-for="it in items" :key="it.key" :class="{ warn: it.warn }">
          <i></i>
          <span class="label">
            {{ it.label }}
            <em>{{ it.api }}</em>
          </span>
          <span class="val">{{ it.warn ? '점검 필요' : '정상' }}</span>
          <span class="detail">{{ it.detail }}</span>
        </li>
      </ul>

      <div class="verdict" :class="verdict.tone">
        <p>{{ verdict.text }}</p>
        <p v-if="dest" class="route">
          다음 충전소 {{ dest.nextCharger }} · 충전기 {{ dest.chargerCount }}기 ·
          남은 거리 <span class="num">{{ dest.remainDistance }}</span>km
        </p>
      </div>

      <button class="book" :class="{ done: booked }" @click="booked = true">
        {{ booked ? '예약 요청을 보냈어요' : '정비소 예약' }}
      </button>
    </section>
  </div>
</template>

<style scoped>
.screen {
  flex: 1;
  display: grid;
  grid-template-columns: minmax(260px, 38%) 1fr;
  gap: 26px;
  padding: 24px 40px 28px;
  min-height: 0;
}

.car {
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-radius: var(--r-card);
  padding: 20px 22px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  min-height: 0;
}
.car h2 { align-self: flex-start; font-size: var(--fs-small); color: var(--quiet); font-weight: 500; }
.top { width: 100%; max-height: 340px; flex: 1; min-height: 0; }

.legend { list-style: none; display: flex; gap: 14px; margin: 0; padding: 0; flex-wrap: wrap; justify-content: center; }
.legend li { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: var(--quiet); }
.legend i { width: 9px; height: 9px; border-radius: 50%; }
.legend .ok { background: var(--moss); }
.legend .warn { background: var(--caution); }

.note { font-size: 11.5px; color: var(--quiet); line-height: 1.5; text-align: center; }

.panel { display: flex; flex-direction: column; gap: 11px; min-height: 0; }
.panel-head { display: flex; justify-content: space-between; align-items: baseline; gap: 16px; }
h1 { font-size: var(--fs-title); font-weight: 600; letter-spacing: -.035em; }

.rows { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; overflow-y: auto; }
.rows li {
  display: grid;
  grid-template-columns: 12px 1fr auto;
  grid-template-areas: 'dot label val' '. detail detail';
  align-items: center;
  gap: 4px 14px;
  min-height: 58px;
  padding: 9px 18px;
  background: var(--paper-2);
  border: 1px solid var(--line);
  border-left: 4px solid var(--moss);
  border-radius: var(--r-row);
}
.rows li.warn { border-left-color: var(--caution); background: var(--caution-soft); }

.rows i { grid-area: dot; width: 10px; height: 10px; border-radius: 50%; background: var(--moss); }
.rows li.warn i { background: var(--caution); }

.label { grid-area: label; display: flex; flex-direction: column; font-size: var(--fs-body); font-weight: 600; }
.label em { font-style: normal; font-size: 11px; color: var(--quiet); font-weight: 400; }
.val { grid-area: val; font-size: var(--fs-body); font-weight: 600; }
.rows li.warn .val { color: #8A5B10; }
.detail { grid-area: detail; font-size: 13px; color: var(--quiet); }

.verdict { border-radius: var(--r-panel); padding: 13px 20px; display: flex; flex-direction: column; gap: 5px; }
.verdict p { font-size: var(--fs-lead); font-weight: 600; letter-spacing: -.02em; line-height: 1.4; }
.verdict .route { font-size: 13px; font-weight: 400; color: inherit; opacity: .72; }
.verdict.ok   { background: var(--moss-soft); color: #11543A; }
.verdict.warn { background: var(--caution-soft); color: #7A4E0C; }
.verdict.info { background: var(--rest-soft); color: var(--rest); }

.book {
  min-height: 68px;
  border: 1px solid var(--ink);
  border-radius: var(--r-chip);
  background: var(--ink);
  color: var(--paper);
  font-size: 19px;
  font-weight: 600;
  cursor: pointer;
}
.book.done { background: var(--moss-soft); color: #11543A; border-color: var(--moss); }

.back {
  background: none; border: 0; color: var(--quiet);
  text-decoration: underline; text-underline-offset: 5px;
  cursor: pointer; font-size: var(--fs-small); white-space: nowrap;
}

@media (max-width: 900px) {
  .screen { grid-template-columns: 1fr; padding: 18px 16px 26px; overflow-y: auto; }
  .top { max-height: 180px; }
  .rows { overflow: visible; }
}
</style>
