# 쉼표 (Shimpyo)

**장거리 여정의 쉼표 — 충전 시간을 차량이 스스로 채워드립니다.**

급속 충전이 시작되는 순간 차량 디스플레이에서 자동으로 열리는 Pleos 앱입니다. 차량이 주행 이력·시각·동승자·배터리 상태를 읽어 지금 가장 알맞은 휴식 방법을 **먼저 제안**하고, 사용자는 네 가지 모드 중 하나를 고릅니다.

> 현대자동차그룹 차량용 앱 개발 공모전 출품작 · 기획서: [`docs/쉼표-기획서.md`](docs/쉼표-기획서.md)

---

## 바로 보기

| | 주소 |
|---|---|
| 차량 앱 (웹뷰) | **https://shimpyo.revuplan.com** |
| 관리자 | **https://shimpyo-admin.revuplan.com** |
| APK 내려받기 | **https://shimpyo.revuplan.com/download/shimpyo.apk** |

차량 디스플레이(1920×720) 기준으로 설계했고 PC·휴대폰에서도 같은 화면이 열립니다.
우측 상단 **차량 데이터** 버튼으로 주행 시간·시각·동승자·경고등·배터리를 바꾸면 추천이 즉시 다시 계산됩니다.

## APK 파일 위치

- 저장소 내: [`apk/shimpyo_release.apk`](apk/) — 서명된 release 빌드
- 또는 위 내려받기 주소

## 실행 방법

### 1) APK 설치해서 보기

```bash
adb install -r apk/shimpyo_release.apk
```

설치 후 **쉼표** 아이콘을 실행하면 웹뷰가 위 차량 앱 주소를 엽니다. 네트워크 연결이 필요합니다.

### 2) 서버를 직접 띄워서 보기

```bash
cd shimpyo
composer install
npm install                 # Node 20.12 이상 필요 (Vite 8)
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm run build
php artisan serve
```

- 차량 앱 `http://127.0.0.1:8000`
- 관리자 `http://127.0.0.1:8000/admin`

### 3) 셸앱 직접 빌드

```bash
cd shell-app
echo "sdk.dir=$HOME/Library/Android/sdk" > local.properties
gradle assembleRelease       # 서명하려면 keystore.properties 필요. 없으면 assembleDebug
```

산출물: `app/build/outputs/apk/release/app-release.apk`

## 테스트 환경 및 필요한 설정

| 항목 | 값 |
|---|---|
| 셸앱 | Android **minSdk 28** / targetSdk 35, Java 17, Gradle 8.11.1, AGP 8.7.3 |
| 백엔드 | PHP 8.3, Laravel 13, MariaDB 10.6 (로컬은 SQLite 가능) |
| 프런트 | Node 20.12 이상, Vue 3, Vite 8 |
| 배포 | Ubuntu 22.04, nginx, Let's Encrypt |
| 권한 | `INTERNET`, `ACCESS_NETWORK_STATE` 만 사용 |
| 네트워크 | 웹뷰가 `*.revuplan.com` 만 앱 안에서 열고 그 외 링크는 외부 브라우저로 넘깁니다 |

별도 계정이나 API 키 설정은 필요 없습니다.

---

## 구현 범위

### 상황 인식 추천 엔진
충전 케이블 연결을 감지하면 6개 신호를 한 번에 읽어 모드별 점수를 합산하고 1위를 추천합니다. 동점이면 안전 우선 순서(점검 → 휴식 → 콘텐츠 → 놀이)로 가릅니다. 차량 경고가 있으면 점수와 무관하게 점검이 최우선으로 추천됩니다. 규칙은 DB에 있고 관리자 화면에서 실시간으로 바꿉니다.

### 네 가지 모드
| 모드 | 내용 |
|---|---|
| 콘텐츠 | 남은 충전 시간에 길이를 맞춘 재생목록 생성. 출발 후 이어 들을 분량까지 구성 |
| 휴식 | 쉼표 모양 원형 타이머, 시트·조명·공조 제어, 완충 1분 전 화면이 밝아지며 깨움 |
| 점검 | 타이어 4륜 상태등, 경고 항목 4종, 도착 시 예상 배터리 판정 |
| 놀이 | 당일 주행 데이터로 만든 퀴즈 10문제, 좌석별 점수판 |

### 피드백 기반 개인화
출발 직후 1회 "좋았어요 / 별로였어요"를 받아 `시간대 × 동승자 × 주행시간` 조합별 가중치에 ±1씩 누적합니다. 다음 추천부터 반영됩니다.

### 관리자
대시보드(추천 수락률·만족도·폴백 비율, 추천 대 실제 선택, 일별 세션), 추천 규칙 CRUD, 충전 세션 로그(규칙별 점수 산출 내역까지 추적), 콘텐츠 CRUD, 퀴즈 템플릿 CRUD.

---

## 구조

```
shimpyo/                      Laravel 13 + Vue 3 — 한 앱이 두 SPA를 호스트명으로 분기
  app/Services/
    PleosVehicleGateway.php   Pleos Connect SDK 모의 게이트웨이  ★ 교체 지점
    RecommendationEngine.php  추천 점수 합산 + 동점 처리 + 개인화
    PlaylistBuilder.php       남은 시간에 맞춘 재생목록
    QuizBuilder.php           주행 데이터 → 퀴즈
  resources/js/app/           차량 앱 (화면 7장 + 시뮬레이터)
  resources/js/admin/         관리자
shell-app/                    안드로이드 웹뷰 셸 (Java)
apk/                          서명된 release APK
docs/쉼표-기획서.md            기획서
```

### `PleosVehicleGateway` 가 핵심입니다

차량 데이터는 이 클래스 한 곳으로만 들어오고, 메서드 이름을 실제 SDK와 1:1로 맞췄습니다.

```php
$gateway->getEvChargeSwitchState();      // EvBattery  — 충전 중 여부
$gateway->getEvBatteryStateOfCharge();   // EvBattery  — 배터리 잔량 (%)
$gateway->getChargingPlan();             // NaviHelper — duration / startSoc / endSoc
$gateway->getCurrentSeatOccupancy();     // Seat       — 좌석 착좌
$gateway->getTelltalesInfo();            // Safety     — 경고등
$gateway->moveSeatBackrestAngle(38);     // Seat       — 등받이 제어
```

추천 엔진·재생목록·퀴즈·관리자는 이 데이터가 어디서 왔는지 모릅니다. API 승인 후에는 이 클래스의 내부 구현만 실제 SDK 호출로 바꾸면 됩니다.

### 충전 타이머의 이중 구조

1. **주 경로** — 경로 안내 중이면 `ChargingPlan.duration`(초)을 그대로 사용
2. **폴백** — 목적지가 없으면 SoC 변화율로 외삽. 80% 이후 충전 감속을 보정 계수로 반영

보정 계수는 한국도로공사 실측치에 맞췄습니다. 200kW 충전기로 10→80%가 **22분**으로 나옵니다.

---

## 현재 데모의 한계

- **실제 Pleos API 미연동** (승인 필요). 차량 데이터는 시뮬레이터가 공급합니다
- 관리자 대시보드의 수락률·만족도 수치는 **샘플 데이터**이며 사용자 조사 결과가 아닙니다
- 콘텐츠 모드의 재생은 화면만 있고 실제 오디오 스트리밍은 없습니다
- 관리자에 로그인이 없습니다 (심사 접근성을 위해 공개, `noindex` 적용)
