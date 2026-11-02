<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

/** 일별 세션 수 — 단일 계열이므로 범례 없이 제목이 계열을 말한다. */
const props = defineProps({
  points: { type: Array, required: true },  // [{ d: '2026-11-02', c: 4 }]
  label:  { type: String, default: '세션' },
});

const box = ref(null);
const w = ref(640);
const H = 190;
const PAD = { t: 14, r: 14, b: 26, l: 30 };

let ro;
onMounted(() => {
  ro = new ResizeObserver(([e]) => { w.value = Math.max(260, e.contentRect.width); });
  ro.observe(box.value);
});
onUnmounted(() => ro?.disconnect());

const max = computed(() => Math.max(1, ...props.points.map((p) => p.c)));

const xy = computed(() => {
  const n = props.points.length;
  const iw = w.value - PAD.l - PAD.r;
  const ih = H - PAD.t - PAD.b;
  return props.points.map((p, i) => ({
    ...p,
    x: PAD.l + (n === 1 ? iw / 2 : (i / (n - 1)) * iw),
    y: PAD.t + ih - (p.c / max.value) * ih,
  }));
});

const path = computed(() => xy.value.map((p, i) => `${i ? 'L' : 'M'} ${p.x} ${p.y}`).join(' '));

const active = ref(null);

const onMove = (e) => {
  const r = e.currentTarget.getBoundingClientRect();
  const x = e.clientX - r.left;
  let best = null;
  xy.value.forEach((p, i) => {
    const d = Math.abs(p.x - x);
    if (!best || d < best.d) best = { d, i };
  });
  active.value = best ? best.i : null;
};

const md = (s) => { const [, m, d] = s.split('-'); return `${Number(m)}/${Number(d)}`; };
</script>

<template>
  <figure class="chart" ref="box">
    <svg :viewBox="`0 0 ${w} ${H}`" :height="H" role="img"
         :aria-label="`최근 ${points.length}일 일별 ${label} 추이`"
         @mousemove="onMove" @mouseleave="active = null">
      <line :x1="PAD.l" :x2="w - PAD.r" :y1="PAD.t" :y2="PAD.t" class="grid" />
      <line :x1="PAD.l" :x2="w - PAD.r" :y1="H - PAD.b" :y2="H - PAD.b" class="axis" />
      <text :x="PAD.l - 8" :y="PAD.t + 4" class="tick" text-anchor="end">{{ max }}</text>
      <text :x="PAD.l - 8" :y="H - PAD.b + 4" class="tick" text-anchor="end">0</text>

      <path :d="path" class="line" />

      <g v-if="active !== null">
        <line :x1="xy[active].x" :x2="xy[active].x" :y1="PAD.t" :y2="H - PAD.b" class="cross" />
      </g>

      <circle v-for="(p, i) in xy" :key="p.d" :cx="p.x" :cy="p.y"
              :r="active === i ? 6 : 4" class="dot" />

      <text v-for="(p, i) in xy" :key="'t' + p.d"
            v-show="i === 0 || i === xy.length - 1 || active === i"
            :x="p.x" :y="H - 8" class="tick" text-anchor="middle">{{ md(p.d) }}</text>
    </svg>

    <div v-if="active !== null" class="tip"
         :style="{ left: Math.min(Math.max(xy[active].x, 56), w - 56) + 'px' }">
      {{ md(xy[active].d) }} <b>{{ xy[active].c }}</b>건
    </div>

    <p v-if="!points.length" class="empty">아직 기록이 없어요. 차량 앱에서 충전을 시작하면 쌓입니다.</p>
  </figure>
</template>

<style scoped>
.chart { position: relative; margin: 0; width: 100%; }
svg { width: 100%; display: block; }

.grid { stroke: var(--grid); stroke-width: 1; }
.axis { stroke: var(--line); stroke-width: 1; }
.tick { font-size: 11px; fill: var(--quiet); font-variant-numeric: tabular-nums; }

.line { fill: none; stroke: var(--cat-1); stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
.dot { fill: var(--cat-1); stroke: var(--paper-2); stroke-width: 2; transition: r .15s var(--ease); }
.cross { stroke: var(--line); stroke-width: 1; stroke-dasharray: 3 3; }

.tip {
  position: absolute;
  top: 0;
  transform: translateX(-50%);
  background: var(--ink);
  color: var(--paper);
  font-size: 12px;
  padding: 5px 10px;
  border-radius: 6px;
  pointer-events: none;
  white-space: nowrap;
}
.tip b { font-variant-numeric: tabular-nums; }

.empty { position: absolute; inset: 0; display: grid; place-items: center; color: var(--quiet); font-size: 13px; }
</style>
