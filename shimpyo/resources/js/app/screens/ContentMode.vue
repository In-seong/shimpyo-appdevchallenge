<script setup>
import { ref, onMounted, computed } from 'vue';
import { api, minutes } from '../api';

const props = defineProps({ session: Object, remaining: Number });
defineEmits(['back']);

const list    = ref(null);
const filter  = ref('');
const playing = ref(null);
const loading = ref(true);

const load = async () => {
  loading.value = true;
  list.value = await api.playlist(props.session.session_id, filter.value || undefined);
  playing.value = list.value.items[0] ?? null;
  loading.value = false;
};

onMounted(load);

const pick = (t) => { filter.value = filter.value === t ? '' : t; load(); };

const dur = (s) => `${Math.floor(s / 60)}분 ${String(s % 60).padStart(2, '0')}초`;

const duringCharge = computed(() => list.value?.items.filter((i) => !i.after_charge) ?? []);
const afterCharge  = computed(() => list.value?.items.filter((i) => i.after_charge) ?? []);

const typeLabel = { podcast: '팟캐스트', music: '음악', video: '영상' };
</script>

<template>
  <div class="screen">
    <div class="head">
      <div>
        <h1>들을 거리를 길이에 맞췄어요</h1>
        <p v-if="list" class="sub">
          충전 {{ minutes(remaining) }}분에 <span class="num">{{ duringCharge.length }}</span>편,
          채움 <span class="num">{{ Math.round(list.fill_rate * 100) }}</span>%
          <template v-if="afterCharge.length">
            · 출발 후 이어 들을 <span class="num">{{ afterCharge.length }}</span>편
          </template>
        </p>
      </div>
      <div class="filters">
        <button v-for="t in ['podcast','music','video']" :key="t"
                :class="{ on: filter === t }" @click="pick(t)">{{ typeLabel[t] }}</button>
      </div>
    </div>

    <p v-if="loading" class="empty">재생목록을 만드는 중이에요</p>

    <div v-else class="body">
      <div class="now" v-if="playing">
        <span class="now-k">지금 재생</span>
        <strong>{{ playing.title }}</strong>
        <span class="now-meta">{{ playing.creator }} · {{ dur(playing.duration_sec) }}</span>
        <div class="wave" aria-hidden="true"><i v-for="n in 28" :key="n" :style="{ animationDelay: n * 60 + 'ms' }"></i></div>
      </div>

      <ol class="list">
        <li v-for="(it, i) in list.items" :key="it.id"
            :class="{ after: it.after_charge, on: playing && playing.id === it.id }"
            @click="playing = it">
          <span class="no num">{{ String(i + 1).padStart(2, '0') }}</span>
          <span class="meta">
            <strong>{{ it.title }}</strong>
            <small>{{ it.creator }} · {{ typeLabel[it.type] }}</small>
          </span>
          <span class="len num">{{ dur(it.duration_sec) }}</span>
          <span v-if="it.after_charge" class="after-tag">출발 후</span>
        </li>
      </ol>
    </div>

    <button class="back" @click="$emit('back')">다른 모드 고르기</button>
  </div>
</template>

<style scoped>
.screen { flex: 1; display: flex; flex-direction: column; padding: 26px 40px 28px; gap: 16px; min-height: 0; }

.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap; }
h1 { font-size: var(--fs-title); font-weight: 600; letter-spacing: -.035em; }
.sub { color: var(--quiet); font-size: var(--fs-small); margin-top: 5px; }
.sub .num { color: var(--ink); font-weight: 600; }

.filters { display: flex; gap: 7px; }
.filters button {
  min-height: 46px; padding: 0 18px;
  border-radius: var(--r-chip); border: 1px solid var(--line);
  background: var(--paper-2); cursor: pointer; font-size: var(--fs-small);
}
.filters .on { background: var(--ink); color: var(--paper); border-color: var(--ink); }

.body { flex: 1; display: grid; grid-template-columns: minmax(260px, 30%) 1fr; gap: 20px; min-height: 0; }

.now {
  background: var(--moss);
  color: #fff;
  border-radius: var(--r-card);
  padding: 26px 24px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  justify-content: center;
}
.now-k { font-size: 13px; opacity: .75; }
.now strong { font-size: 24px; font-weight: 700; letter-spacing: -.03em; line-height: 1.3; }
.now-meta { font-size: var(--fs-small); opacity: .8; }

.wave { display: flex; align-items: flex-end; gap: 3px; height: 34px; margin-top: 14px; }
.wave i {
  width: 3px; border-radius: 2px; background: rgba(255,255,255,.6);
  height: 30%; animation: bob 1.3s ease-in-out infinite alternate;
}
@keyframes bob { to { height: 100%; } }

.list { list-style: none; margin: 0; padding: 0; overflow-y: auto; display: flex; flex-direction: column; gap: 2px; }
.list li {
  display: grid;
  grid-template-columns: 40px 1fr auto auto;
  align-items: center;
  gap: 14px;
  min-height: 66px;
  padding: 0 16px;
  border-radius: var(--r-panel);
  cursor: pointer;
  border: 1px solid transparent;
}
.list li:hover { background: var(--paper-2); }
.list li.on { background: var(--moss-soft); border-color: var(--moss); }
.list li.after { opacity: .72; }

.no { color: var(--quiet); font-size: 14px; }
.meta { display: flex; flex-direction: column; min-width: 0; }
.meta strong { font-size: var(--fs-body); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.meta small { font-size: 13px; color: var(--quiet); }
.len { font-size: 14px; color: var(--quiet); }
.after-tag {
  font-size: 11.5px; padding: 3px 9px; border-radius: var(--r-chip);
  background: var(--rest-soft); color: var(--rest);
}

.empty { color: var(--quiet); padding: 40px 0; }

.back {
  align-self: flex-start;
  background: none; border: 0; padding: 8px 2px;
  color: var(--quiet); text-decoration: underline; text-underline-offset: 5px;
  cursor: pointer; font-size: var(--fs-small);
}

@media (max-width: 900px) {
  .screen { padding: 20px 16px 26px; }
  .body { grid-template-columns: 1fr; }
  .list { max-height: none; }
  .list li { grid-template-columns: 30px 1fr auto; }
  .after-tag { display: none; }
}
</style>
