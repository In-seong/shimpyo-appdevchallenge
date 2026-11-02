<script setup>
import { computed } from 'vue';
import { mmss } from '../api';

const props = defineProps({
  remaining: { type: Number, required: true },
  total:     { type: Number, required: true },
  dim:       { type: Boolean, default: false },
  size:      { type: Number, default: 320 },
});

const R = 128;
const C = 2 * Math.PI * R;

const progress = computed(() => {
  if (!props.total) return 0;
  return Math.min(1, Math.max(0, props.remaining / props.total));
});

const dash = computed(() => `${C * progress.value} ${C}`);

/* 링의 남은 끝점 — 여기서 쉼표 꼬리가 시작된다 */
const endAngle = computed(() => -90 + 360 * progress.value);

const endPoint = computed(() => {
  const rad = (endAngle.value * Math.PI) / 180;
  return { x: 160 + R * Math.cos(rad), y: 160 + R * Math.sin(rad) };
});

/**
 * 쉼표 꼬리.
 * 링 끝점에서 시작해 안쪽으로 말려 들어가는 곡선.
 * 접선 방향으로 나가다가 원 중심 쪽으로 휘어 쉼표의 내리뻗음을 만든다.
 */
const tail = computed(() => {
  const rad = (endAngle.value * Math.PI) / 180;
  const { x, y } = endPoint.value;
  const tx = -Math.sin(rad); const ty = Math.cos(rad);   // 접선(진행 방향)
  const nx = Math.cos(rad);  const ny = Math.sin(rad);   // 법선(바깥쪽)

  const c1x = x + tx * 20 + nx * 2;
  const c1y = y + ty * 20 + ny * 2;
  const ex  = x + tx * 26 - nx * 30;
  const ey  = y + ty * 26 - ny * 30;
  const c2x = x + tx * 30 - nx * 10;
  const c2y = y + ty * 30 - ny * 10;

  return `M ${x} ${y} C ${c1x} ${c1y} ${c2x} ${c2y} ${ex} ${ey}`;
});

const label = computed(() => mmss(props.remaining));
</script>

<template>
  <figure class="comma" :class="{ dim }" :style="{ width: size + 'px', height: size + 'px' }">
    <svg viewBox="0 0 320 320" role="img" :aria-label="`남은 충전 시간 ${label}`">
      <circle cx="160" cy="160" :r="R" class="track" />
      <g class="spin">
        <circle
          cx="160" cy="160" :r="R"
          class="arc"
          :stroke-dasharray="dash"
          transform="rotate(-90 160 160)"
        />
      </g>
      <path :d="tail" class="tail" />
      <circle :cx="endPoint.x" :cy="endPoint.y" r="9" class="head" />
    </svg>

    <figcaption>
      <span class="num time">{{ label }}</span>
      <span class="unit">남음</span>
    </figcaption>
  </figure>
</template>

<style scoped>
.comma { position: relative; display: grid; place-items: center; margin: 0; }
svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; }

.track { fill: none; stroke: var(--paper-sunk); stroke-width: 10; }

.arc {
  fill: none;
  stroke: var(--moss);
  stroke-width: 10;
  stroke-linecap: round;
  transition: stroke-dasharray .9s linear;
}

.tail {
  fill: none;
  stroke: var(--moss);
  stroke-width: 10;
  stroke-linecap: round;
  transition: d .9s linear;
}

.head { fill: var(--moss); }

figcaption {
  position: relative;
  display: flex;
  align-items: baseline;
  gap: 10px;
}

.time {
  font-size: clamp(42px, 4.4vw, 68px);
  font-weight: 300;
  letter-spacing: -.035em;
  line-height: 1;
}

.unit { font-size: var(--fs-body); color: var(--quiet); }

.dim .track { stroke: rgba(255,255,255,.14); }
.dim .arc, .dim .tail { stroke: #8FD3AE; }
.dim .head { fill: #8FD3AE; }
.dim .time { color: #F2F6F3; }
.dim .unit { color: rgba(242,246,243,.6); }
</style>
