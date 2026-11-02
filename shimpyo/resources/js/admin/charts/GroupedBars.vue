<script setup>
import { computed, ref } from 'vue';

/**
 * 묶음 막대 — 범주 4개 × 계열 2개.
 * 범주 색은 고정 순서(--cat-1, --cat-2), 순환시키지 않는다.
 * 값 텍스트는 계열 색이 아니라 본문 잉크 색을 쓴다.
 */
const props = defineProps({
  rows:   { type: Array, required: true },   // [{ label, a, b }]
  series: { type: Array, required: true },   // ['추천', '실제 선택']
  unit:   { type: String, default: '건' },
});

const hover = ref(null);

const max = computed(() => Math.max(1, ...props.rows.flatMap((r) => [r.a, r.b])));

const h = (v) => `${Math.max(v > 0 ? 3 : 0, (v / max.value) * 100)}%`;

const ticks = computed(() => {
  const m = max.value;
  const step = Math.max(1, Math.ceil(m / 4));
  return Array.from({ length: 5 }, (_, i) => i * step).filter((t) => t <= m + step);
});
</script>

<template>
  <figure class="chart">
    <ul class="legend">
      <li><i class="s1"></i>{{ series[0] }}</li>
      <li><i class="s2"></i>{{ series[1] }}</li>
    </ul>

    <div class="plot">
      <div class="grid" aria-hidden="true">
        <span v-for="t in ticks" :key="t" :style="{ bottom: (t / max) * 100 + '%' }"><em>{{ t }}</em></span>
      </div>

      <div class="cols">
        <div v-for="(r, i) in rows" :key="r.label" class="col">
          <div class="pair">
            <div class="slot"
                 @mouseenter="hover = { i, s: 0 }" @mouseleave="hover = null">
              <span class="bar s1" :style="{ height: h(r.a) }"></span>
              <span v-if="hover && hover.i === i && hover.s === 0" class="tip">
                {{ series[0] }} <b>{{ r.a }}</b>{{ unit }}
              </span>
            </div>
            <div class="slot"
                 @mouseenter="hover = { i, s: 1 }" @mouseleave="hover = null">
              <span class="bar s2" :style="{ height: h(r.b) }"></span>
              <span v-if="hover && hover.i === i && hover.s === 1" class="tip">
                {{ series[1] }} <b>{{ r.b }}</b>{{ unit }}
              </span>
            </div>
          </div>
          <span class="cat">{{ r.label }}</span>
        </div>
      </div>
    </div>

    <table class="sr">
      <caption>{{ series[0] }}와 {{ series[1] }} 비교</caption>
      <thead><tr><th>모드</th><th>{{ series[0] }}</th><th>{{ series[1] }}</th></tr></thead>
      <tbody><tr v-for="r in rows" :key="r.label"><th>{{ r.label }}</th><td>{{ r.a }}</td><td>{{ r.b }}</td></tr></tbody>
    </table>
  </figure>
</template>

<style scoped>
.chart { margin: 0; display: flex; flex-direction: column; gap: 14px; }

.legend { list-style: none; display: flex; gap: 16px; margin: 0; padding: 0; font-size: 13px; color: var(--quiet); }
.legend li { display: inline-flex; align-items: center; gap: 7px; }
.legend i { width: 11px; height: 11px; border-radius: 3px; }
.s1 { background: var(--cat-1); }
.s2 { background: var(--cat-2); }

.plot { position: relative; height: 212px; padding-left: 30px; }

.grid { position: absolute; inset: 0 0 24px 30px; }
.grid span { position: absolute; left: 0; right: 0; border-top: 1px solid var(--grid); }
.grid em {
  position: absolute; left: -30px; top: -.65em;
  font-style: normal; font-size: 11px; color: var(--quiet);
  font-variant-numeric: tabular-nums;
}

.cols { position: relative; height: 100%; display: flex; gap: 10px; }
.col { flex: 1; display: flex; flex-direction: column; min-width: 0; }

.pair { flex: 1; display: flex; align-items: flex-end; justify-content: center; gap: 2px; }

.slot { position: relative; flex: 1; max-width: 34px; height: 100%; display: flex; align-items: flex-end; cursor: default; }

.bar {
  display: block;
  width: 100%;
  border-radius: 4px 4px 0 0;
  transition: height .4s var(--ease);
}

.tip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  background: var(--ink);
  color: var(--paper);
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 6px;
  pointer-events: none;
  z-index: 2;
}
.tip b { font-variant-numeric: tabular-nums; }

.cat { height: 24px; line-height: 24px; text-align: center; font-size: 13px; color: var(--ink); }

.sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
</style>
