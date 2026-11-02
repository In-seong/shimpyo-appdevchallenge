<script setup>
import { ref, onMounted } from 'vue';
import { get, MODE_LABEL } from '../api';

const page = ref(null);
const open = ref(null);
const filter = ref('');

const load = async (url = '/sessions') => {
  const q = filter.value ? `${url}${url.includes('?') ? '&' : '?'}mode=${filter.value}` : url;
  page.value = await get(q);
};

onMounted(() => load());

const go = (link) => {
  if (!link) return;
  const u = new URL(link);
  load(`/sessions?${u.searchParams.toString()}`);
};

const setFilter = (m) => { filter.value = filter.value === m ? '' : m; load(); };

const when = (s) => new Date(s).toLocaleString('ko-KR', { month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' });
const mins = (s) => Math.round(s / 60);
</script>

<template>
  <div class="bar">
    <div class="filters">
      <button v-for="(l, k) in MODE_LABEL" :key="k" :class="{ on: filter === k }" @click="setFilter(k)">{{ l }}</button>
    </div>
    <span v-if="page" class="count">전체 {{ page.total }}건</span>
  </div>

  <p v-if="page && !page.data.length" class="empty">
    아직 기록이 없어요. 차량 앱에서 충전을 시작하면 세션이 쌓입니다.
  </p>

  <ul v-else-if="page" class="list">
    <li v-for="s in page.data" :key="s.id" :class="{ open: open === s.id }">
      <button class="row" @click="open = open === s.id ? null : s.id">
        <span class="t">{{ when(s.created_at) }}</span>
        <span class="mode rec">{{ MODE_LABEL[s.recommended_mode] }}</span>
        <span class="arrow" aria-hidden="true">→</span>
        <span class="mode pick" :class="{ none: !s.selected_mode, match: s.selected_mode === s.recommended_mode }">
          {{ s.selected_mode ? MODE_LABEL[s.selected_mode] : '선택 안 함' }}
        </span>
        <span class="meta num">{{ mins(s.charge_duration_sec) }}분</span>
        <span class="meta">{{ s.start_soc }}% → {{ s.end_soc }}%</span>
        <span v-if="s.used_fallback" class="tag">추정</span>
        <span v-if="s.feedback" class="fb" :class="s.feedback.rating > 0 ? 'good' : 'bad'">
          {{ s.feedback.rating > 0 ? '좋았어요' : '별로였어요' }}
        </span>
        <span class="sit">{{ s.situation_key }}</span>
      </button>

      <div v-if="open === s.id" class="detail">
        <p class="reason">{{ s.reason }}</p>

        <div class="cols">
          <div>
            <h3>점수 산출</h3>
            <table class="bd">
              <tbody>
                <tr v-for="(b, i) in s.breakdown" :key="i" :class="{ ovr: b.override }">
                  <td class="l">{{ b.label }}</td>
                  <td class="m">{{ MODE_LABEL[b.mode] }}</td>
                  <td class="w num">{{ b.weight > 0 ? '+' : '' }}{{ b.weight }}</td>
                </tr>
              </tbody>
              <tfoot>
                <tr v-for="(v, m) in s.scores" :key="m">
                  <td class="l">합계</td><td class="m">{{ MODE_LABEL[m] }}</td><td class="w num">{{ v }}</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div>
            <h3>입력 신호</h3>
            <dl class="sig">
              <template v-for="(v, k) in s.signals" :key="k">
                <dt>{{ k }}</dt>
                <dd class="num">{{ v === true ? '예' : v === false ? '아니오' : (v ?? '—') }}</dd>
              </template>
            </dl>
          </div>
        </div>
      </div>
    </li>
  </ul>

  <nav v-if="page && page.last_page > 1" class="pager">
    <button :disabled="!page.prev_page_url" @click="go(page.prev_page_url)">이전</button>
    <span>{{ page.current_page }} / {{ page.last_page }}</span>
    <button :disabled="!page.next_page_url" @click="go(page.next_page_url)">다음</button>
  </nav>
</template>

<style scoped>
.bar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 14px; flex-wrap: wrap; }
.filters { display: flex; gap: 6px; }
.filters button { padding: 8px 15px; border: 1px solid var(--line); background: var(--paper-2); border-radius: var(--r-chip); cursor: pointer; font-size: 13px; }
.filters .on { background: var(--ink); color: var(--paper); border-color: var(--ink); }
.count { font-size: 13px; color: var(--quiet); }

.list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 5px; }
.list li { background: var(--paper-2); border: 1px solid var(--line); border-radius: var(--r-panel); overflow: hidden; }
.list li.open { border-color: var(--moss); }

.row {
  width: 100%;
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
  padding: 13px 16px; background: none; border: 0; cursor: pointer; text-align: left;
  font-size: 13.5px;
}
.t { color: var(--quiet); min-width: 104px; font-variant-numeric: tabular-nums; }
.mode { font-weight: 600; }
.rec { color: var(--cat-1); }
.pick { color: var(--cat-2); }
.pick.match { color: var(--cat-1); }
.pick.none { color: var(--quiet); font-weight: 400; }
.arrow { color: var(--quiet); font-size: 12px; }
.meta { color: var(--quiet); }
.tag { font-size: 11px; padding: 2px 8px; border-radius: var(--r-chip); background: var(--caution-soft); color: #8A5B10; }
.fb { font-size: 12px; padding: 3px 10px; border-radius: var(--r-chip); }
.fb.good { background: var(--moss-soft); color: #11543A; }
.fb.bad { background: #F6E2DC; color: var(--bad); }
.sit { margin-left: auto; font-size: 11.5px; color: var(--quiet); }

.detail { padding: 4px 16px 18px; border-top: 1px solid var(--grid); }
.reason { font-size: 15px; font-weight: 500; margin: 14px 0; }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
h3 { font-size: 12px; color: var(--quiet); font-weight: 500; margin-bottom: 7px; }

.bd { width: 100%; border-collapse: collapse; font-size: 13px; }
.bd td { padding: 6px 8px; border-bottom: 1px solid var(--grid); }
.bd .m { color: var(--quiet); width: 60px; }
.bd .w { text-align: right; width: 50px; font-variant-numeric: tabular-nums; }
.bd tr.ovr .l::after { content: ' 최우선'; color: var(--caution); font-size: 11px; }
.bd tfoot td { font-weight: 600; border-bottom: 0; border-top: 1px solid var(--line); }

.sig { display: grid; grid-template-columns: auto 1fr; gap: 4px 14px; font-size: 12.5px; margin: 0; }
.sig dt { color: var(--quiet); }
.sig dd { margin: 0; text-align: right; }

.pager { display: flex; align-items: center; gap: 14px; justify-content: center; margin-top: 18px; font-size: 13px; }
.pager button { padding: 8px 16px; border: 1px solid var(--line); background: var(--paper-2); border-radius: 6px; cursor: pointer; }
.pager button:disabled { opacity: .4; cursor: default; }

.empty { color: var(--quiet); font-size: 14px; padding: 30px 0; }

@media (max-width: 760px) {
  .cols { grid-template-columns: 1fr; }
  .sit { margin-left: 0; width: 100%; }
}
</style>
