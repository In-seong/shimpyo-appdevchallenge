<script setup>
import { computed } from 'vue';
import { minutes } from '../api';

const props = defineProps({
  remaining: Number,
  startSoc: Number,
  endSoc: Number,
  fallback: Boolean,
  dim: Boolean,
  simOpen: Boolean,
});

defineEmits(['toggle-sim', 'home']);

const left = computed(() => minutes(props.remaining));
</script>

<template>
  <header class="bar" :class="{ dim }">
    <button class="brand" @click="$emit('home')" aria-label="쉼표 처음으로">
      <span class="mark" aria-hidden="true">,</span>
      <span class="name">쉼표</span>
    </button>

    <div class="status">
      <span class="num big">{{ left }}</span><span class="suffix">분 남음</span>
      <span class="divider" aria-hidden="true"></span>
      <span class="num soc">{{ startSoc }}%</span>
      <span class="arrow" aria-hidden="true">→</span>
      <span class="num soc">{{ endSoc }}%</span>
      <span v-if="fallback" class="tag" title="내비 목적지가 없어 배터리 변화율로 추정한 값입니다">추정</span>
    </div>

    <button class="sim" :class="{ on: simOpen }" @click="$emit('toggle-sim')">
      차량 데이터
    </button>
  </header>
</template>

<style scoped>
.bar {
  height: var(--bar);
  flex: 0 0 var(--bar);
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 0 22px;
  background: var(--paper-2);
  border-bottom: 1px solid var(--line);
  transition: background .6s var(--ease), border-color .6s var(--ease);
}

.brand {
  display: flex;
  align-items: baseline;
  gap: 7px;
  background: none;
  border: 0;
  padding: 6px 4px;
  cursor: pointer;
}

.mark {
  font-size: 40px;
  line-height: .55;
  font-weight: 700;
  color: var(--moss);
}

.name { font-size: 21px; font-weight: 600; letter-spacing: -.02em; }

.status {
  margin-left: auto;
  display: flex;
  align-items: baseline;
  gap: 8px;
}

.big { font-size: 28px; font-weight: 600; letter-spacing: -.03em; }
.suffix { font-size: var(--fs-small); color: var(--quiet); margin-right: 4px; }

.divider {
  width: 1px; height: 20px;
  background: var(--line);
  align-self: center;
  margin: 0 6px;
}

.soc { font-size: 18px; font-weight: 500; }
.arrow { color: var(--quiet); font-size: 15px; }

.tag {
  margin-left: 6px;
  font-size: 12px;
  padding: 3px 8px;
  border-radius: var(--r-chip);
  background: var(--caution-soft);
  color: #8A5B10;
  border: 1px solid #EBD6AC;
}

.sim {
  min-height: 46px;
  padding: 0 18px;
  border-radius: var(--r-chip);
  border: 1px solid var(--line);
  background: var(--paper);
  cursor: pointer;
  font-size: var(--fs-small);
  font-weight: 500;
  white-space: nowrap;
}
.sim.on { background: var(--ink); color: var(--paper); border-color: var(--ink); }

/* 휴식 모드에서 상단 바도 함께 어두워진다 */
.dim { background: #16211B; border-bottom-color: rgba(255,255,255,.08); color: #EAF0EC; }
.dim .mark, .dim .tag { color: #8FD3AE; }
.dim .tag { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.14); }
.dim .suffix, .dim .arrow { color: rgba(234,240,236,.55); }
.dim .divider { background: rgba(255,255,255,.16); }
.dim .sim { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.16); color: #EAF0EC; }
.dim .sim.on { background: #EAF0EC; color: #16211B; }

@media (max-width: 640px) {
  .bar { gap: 10px; padding: 0 14px; }
  .name { display: none; }
  /* 로고 글자가 사라지면 쉼표만 남으므로 기준선 정렬을 가운데로 바꾼다 */
  .brand { align-items: center; padding: 0; }
  .mark { font-size: 34px; line-height: 1; transform: translateY(-6px); }
  .big { font-size: 23px; }
  .soc { font-size: 15px; }
  .sim { padding: 0 12px; font-size: 13px; min-height: 42px; }
}
</style>
