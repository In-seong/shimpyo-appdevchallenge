<script setup>
import { ref } from 'vue';
import { api } from '../api';

const props = defineProps({ session: Object });
const emit = defineEmits(['close']);

const sent = ref(false);

const send = async (rating, via = 'touch') => {
  if (sent.value) return;
  sent.value = true;
  await api.feedback(props.session.session_id, rating, via);
  setTimeout(() => emit('close'), 1300);
};

const label = { content: '콘텐츠', rest: '휴식', check: '점검', play: '놀이' };
const mode = props.session.recommend.recommended;
</script>

<template>
  <div class="sheet" role="dialog" aria-label="방금 쓴 모드에 대한 의견">
    <div v-if="!sent" class="ask">
      <div class="text">
        <p class="q">아까 {{ label[mode] }} 모드, 도움이 됐나요?</p>
        <p class="hint">
          <span class="mic" aria-hidden="true">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
              <rect x="9" y="2" width="6" height="12" rx="3" /><path d="M5 11a7 7 0 0 0 14 0M12 18v4" />
            </svg>
          </span>
          음성으로 "응" 또는 "아니"라고 답해도 돼요
        </p>
      </div>

      <div class="buttons">
        <button class="good" @click="send(1)">좋았어요</button>
        <button class="bad"  @click="send(-1)">별로였어요</button>
      </div>
    </div>

    <p v-else class="thanks">다음부터 이 상황에서는 더 잘 맞춰드릴게요</p>
  </div>
</template>

<style scoped>
.sheet {
  position: absolute;
  left: 0; right: 0; bottom: 0;
  min-height: 30%;
  background: var(--paper-2);
  border-top: 1px solid var(--line);
  border-radius: var(--r-card) var(--r-card) 0 0;
  box-shadow: 0 -12px 32px rgba(24,35,29,.1);
  padding: 26px 40px;
  display: flex;
  align-items: center;
  animation: rise .5s var(--ease);
}

@keyframes rise { from { transform: translateY(100%); } }

.ask { flex: 1; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }

.q { font-size: var(--fs-title); font-weight: 600; letter-spacing: -.03em; }
.hint { font-size: var(--fs-small); color: var(--quiet); display: flex; align-items: center; gap: 7px; margin-top: 6px; }
.mic { display: inline-flex; color: var(--moss); }

.buttons { display: flex; gap: 11px; }
.buttons button {
  min-height: var(--tap);
  padding: 0 34px;
  border-radius: var(--r-chip);
  font-size: 19px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid var(--line);
  background: var(--paper);
}
.good { background: var(--moss); color: #fff; border-color: var(--moss); }

.thanks { font-size: var(--fs-lead); font-weight: 500; color: var(--moss); }

@media (max-width: 760px) {
  .sheet { padding: 20px 18px; }
  .buttons { width: 100%; }
  .buttons button { flex: 1; padding: 0 12px; font-size: 16px; }
}
</style>
