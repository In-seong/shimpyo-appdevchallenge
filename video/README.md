# 시연 영상

`황인성_쉼표_시연영상.mp4` — 2분 6초, 1920×880, h264, 2.6MB

공개 주소: https://shimpyo.revuplan.com/download/shimpyo-demo.mp4

## 구성

| 구간 | 내용 |
|---|---|
| 01 | 충전 케이블 연결 → 쉼표 자동 실행, 명절 귀성 상황에서 휴식 추천 (동점 시 안전 우선 순서) |
| 02 | 휴식 모드 — 쉼표 모양 타이머, 차량 제어가 2단계인 이유 |
| 03 | 차량 데이터 시뮬레이터 — 타이어 경고를 켜면 추천이 즉시 점검으로 바뀜 |
| 04 | 점검 모드 — 공기압 수치가 아니라 경고 상태로 표시 |
| 05 | 주말 여행 — 짧은 주행 + 뒷좌석 동승자 → 놀이 추천, 주행 데이터 퀴즈 |
| 06 | 출장 이동 — 목적지 없음 → ChargingPlan 대신 폴백 추정, 상단에 "추정" 표시 |
| 07 | 콘텐츠 모드 — 남은 시간에 길이를 맞춘 재생목록 + 출발 후 이어 듣기 |
| 08 | 운영 관리자 — 대시보드와 세션별 점수 산출 내역 |

## 다시 만들려면

실제 배포 사이트를 iframe 으로 띄우고 위에 자막 띠를 얹어 Playwright 로 녹화한다.
상단 자막이 앱 화면을 가리지 않는 구조다.

```bash
npm install playwright-core
node 녹화스크립트_record.js          # out/*.webm 생성
ffmpeg -i out/*.webm -f lavfi -i anullsrc=channel_layout=stereo:sample_rate=48000 \
  -shortest -c:v libx264 -preset slow -crf 21 -pix_fmt yuv420p -r 25 \
  -c:a aac -b:a 64k -movflags +faststart 황인성_쉼표_시연영상.mp4
```

Chromium 실행 파일 경로와 Playwright 번들 ffmpeg 가 필요하다
(`npx playwright install chromium ffmpeg`).
