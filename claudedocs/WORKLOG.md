# WORKLOG — 작업 연속성 로그

> CLAUDE.md @import 또는 전역 SessionStart 훅으로 매 세션 자동 로드됨. 최신이 맨 위.
> 추가: /checkpoint · 크래시 복구: /recover · ~30개 초과 시 WORKLOG_ARCHIVE.md로 회전.

---

## 📌 현재 진행 중 / 다음 할 일
- **근거 표 출처 원문 재확인** (기획서 2장, 5건) — 현재 검색 결과 기반이며 원문 대조 전. 기획서에 경고 블록으로 표시해 둠. 제출 전 필수.
- **공모전 접수 양식에 맞춰 분량 조정 후 제출** — appdevchallenge@hyundai-partners.com, **마감 2026-11-15**. 기획서가 343줄/약 28,000자라 글자 수 제한이 있으면 9장 화면 명세부터 압축 권장(데모 주소가 있으므로).
- git 커밋 미실행 (사용자 요청 시에만). 현재 저장소에 커밋 0개.
- 선택 사항: 관리자에 로그인 없음(심사위원 접근성 위해 공개). 필요 시 추가.

---

## 2026-11-02 — 쉼표 공모전: API 검증 → 기획서 로컬화 → 동작 데모 구축·배포 → 기획서 보강

**한 일**
- **Pleos API 제공 여부 검증**: Connect SDK 공개 문서를 브라우저로 직접 열어 확인. 문서 본문이 Docusaurus JS 청크에 번들돼 있어 청크를 받아 추출하는 방식으로 시그니처 확보.
  - 핵심 발견: `ChargingPlan(duration:초, startSoc, endSoc)` **존재** → 기획서 최대 공백이던 "예상 충전 완료 시간" 해결. 단 **경로 안내 중에만** 내려옴.
  - **불가 3건**: 타이어 실측 공기압(`Tire.getLowTirePressure`는 저압 **경고 임계값(kPa)**), 급가속·급제동 누적 횟수(Brake는 현재 페달 상태만), 정비 예정일.
  - 덤: **LLM SDK 존재**(놀이 모드 퀴즈 생성), SpeechToText/TextToSpeech, `estimatedSocAtDest`, `maxChargingPower`.
  - Connect SDK(차량 내 앱, 제어 가능) vs Vehicle Data API(서버 REST, 유럽 한정·차주 동의) 구분 확인 → 쉼표는 Connect SDK.
- **기획서를 Claude Docs 아티팩트에서 로컬 `docs/`로 이전**하고 검증 결과 반영(수정 3건: 점검 모드 공기압 수치→상태등, 피로 지표 신호 제외, 타이머 폴백 설계 추가).
- **동작하는 데모 구축**: Laravel 13 + Vue 3 + MariaDB. 추천 엔진·개인화·타이머 폴백·재생목록·퀴즈·관리자 전부 실제 동작. 차량 데이터만 시뮬레이터로 모의.
- **안드로이드 웹뷰 셸앱 빌드**(APK 3.1MB) 및 서버 호스팅.
- **배포 완료**(아래 주소). 기존 사이트 12개 배포 후 확인, 전부 정상.
- **기획서 보강**: 새 10장 "구현 검증 — 동작하는 데모" 추가 + 발견 사항을 해당 장에 녹임.

**배포 주소**
- 차량 앱 https://shimpyo.revuplan.com
- 관리자 https://shimpyo-admin.revuplan.com
- APK https://shimpyo.revuplan.com/download/shimpyo.apk

**변경 파일**
- `docs/쉼표-기획서.md` — 기획서 본체(343줄). 10장 신설, 화면 명세 7장으로 재번호, 5장에 동점 처리 추가
- `shimpyo/app/Services/PleosVehicleGateway.php` — Pleos SDK 모의 게이트웨이(메서드명 1:1). **교체 지점**
- `shimpyo/app/Services/RecommendationEngine.php` — 점수 합산 + 개인화 + 동점 처리
- `shimpyo/app/Services/{PlaylistBuilder,QuizBuilder}.php` — 재생목록·퀴즈 생성
- `shimpyo/app/Http/Controllers/{Api/SessionController,Admin/AdminController,SpaController}.php`
- `shimpyo/database/migrations/2026_11_02_000100_create_shimpyo_tables.php` — 7개 테이블
- `shimpyo/database/seeders/ShimpyoSeeder.php` — 추천 규칙 11개, 콘텐츠 13개, 퀴즈 7개
- `shimpyo/resources/js/app/**` — 차량 앱 Vue (화면 7장 + 시뮬레이터)
- `shimpyo/resources/js/admin/**` — 관리자 Vue (대시보드·규칙·세션·콘텐츠·퀴즈)
- `shell-app/**` — 안드로이드 웹뷰 셸(Java, AGP 8.7.3)
- `README.md`, `.gitignore`, `CLAUDE.local.md`(git 미추적)

**결정/맥락**
- **게이트웨이 격리가 설계의 핵심**. 승인 후 `PleosVehicleGateway` 내부만 교체하면 상위 로직 무수정. 기획서 10장의 주장 근거.
- **충전 타이머 이중 구조**: 경로 안내 중 `ChargingPlan.duration` / 목적지 없으면 SoC 변화율 외삽. 보정 계수를 기획서 2장 실측치에 맞춤 → **200kW 10→80% = 22분 정확 일치**, 350kW·50kW는 2분씩 짧음(기획서에 차이와 원인 명시).
- **동점 처리 함정**: 명절 귀성에서 휴식3=놀이3 동점인데 PHP 배열 선언 순서로 **우연히** 결정되고 있었음. 안전 우선 순서(점검→휴식→콘텐츠→놀이)를 `RecommendationEngine::TIE_ORDER`로 명시. 5경우 검증 후 운영 반영.
- **규칙이 맞아도 죽은 모드가 생김**: 놀이가 한 번도 추천되지 않았는데, "짧은 주행 + 뒷좌석 동승자" 조합이 시나리오에 없어서였음. 주말 여행 프리셋을 아이들 뒷좌석 40분으로 변경.
- **초안 화면 명세에 콘텐츠 모드 화면이 누락**돼 있었음(모드 4개인데 모드 화면 3개). 화면 3으로 채우고 재번호(총 7장).
- **대시보드 수치는 난수 시드 데이터**. 기획서에 "사용자 조사 결과가 아님"을 명시 — 성과처럼 적으면 심사에서 신뢰 붕괴.
- 디자인: 색은 한국 고속도로 표지 체계(녹색/파랑/황색). 모티프는 이름 그대로 **쉼표** — 타이머 링 끝이 쉼표 꼬리처럼 말림. 밝은 톤 요청에 따르되 **휴식 모드만 어두움**(그게 그 모드의 기능).
- 차트 색은 검증기로 확정. 최초 녹색+파랑은 정상시야 ΔE 14.0으로 탈락 → `#19744A`+`#8A4F9E`(전 항목 PASS).
- 한글 줄바꿈 `word-break: keep-all` 필수(기본값은 어절 중간에서 끊김).
- **함정**: Vite 8은 Node 20.12+ 필요(로컬 기본 Node 21.6.1에 `styleText` 없어 실패 → nvm v22.14.0 사용). rsync 시 `bootstrap/cache/*` 제외 필수(로컬 dev 패키지 캐시가 서버 `--no-dev`와 충돌해 `Laravel\Pail` not found).
- **서버 격리 원칙 준수**: 새 vhost 1개, 새 DB `shimpyo`, 기존 php8.3-fpm 소켓 공유, nginx.conf 미수정. `ve-demo.revuplan.com`의 HTTPS 실패는 **원래 인증서 없는 HTTP 전용 사이트**로 본 작업과 무관(HTTP 200 확인).

**다음 할 일**
- 기획서 2장 근거 5건 출처 원문 대조
- 접수 양식에 맞춰 분량 조정 후 제출(마감 11/15)

**상태**: 완료 (데모 배포·기획서 보강까지). 제출 전 출처 검증만 남음.
