<script setup>
defineProps({ mode: String, size: { type: Number, default: 34 } });
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 32 32" fill="none"
       stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"
       aria-hidden="true">
    <!-- 콘텐츠: 재생 + 음파 -->
    <g v-if="mode === 'content'">
      <path d="M4 12v8M9 8v16M14 14v4" />
      <circle cx="23" cy="16" r="7" />
      <path d="M21 13l5 3-5 3z" fill="currentColor" stroke="none" />
    </g>
    <!-- 휴식: 젖혀진 시트 -->
    <g v-else-if="mode === 'rest'">
      <path d="M6 25h17" />
      <path d="M9 25v-5l12-9" />
      <path d="M21 11l3 3-3 3" />
      <path d="M24 22c2-1 3-3 3-5" />
    </g>
    <!-- 점검: 차량 + 확인 -->
    <g v-else-if="mode === 'check'">
      <path d="M4 20l2-7 4-3h8l4 3 2 7" />
      <path d="M4 20v3M26 20v3" />
      <circle cx="9" cy="20" r="2" />
      <circle cx="21" cy="20" r="2" />
      <path d="M13 15.5l2.5 2.5 5-5" />
    </g>
    <!-- 놀이: 주사위 -->
    <g v-else>
      <rect x="5" y="5" width="22" height="22" rx="5" />
      <circle cx="11.5" cy="11.5" r="1.6" fill="currentColor" stroke="none" />
      <circle cx="20.5" cy="20.5" r="1.6" fill="currentColor" stroke="none" />
      <circle cx="16" cy="16" r="1.6" fill="currentColor" stroke="none" />
    </g>
  </svg>
</template>
