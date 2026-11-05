const { chromium } = require('playwright-core');
const path = require('path');

const EXE = process.env.HOME +
  '/Library/Caches/ms-playwright/chromium-1243/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing';
const APP   = 'https://shimpyo.revuplan.com/';
const ADMIN = 'https://shimpyo-admin.revuplan.com/';
const W = 1920, H = 880;

const sleep = (ms) => new Promise(r => setTimeout(r, ms));

(async () => {
  const browser = await chromium.launch({ executablePath: EXE, headless: true });
  const ctx = await browser.newContext({
    viewport: { width: W, height: H },
    deviceScaleFactor: 1,
    recordVideo: { dir: path.join(__dirname, 'out'), size: { width: W, height: H } },
    locale: 'ko-KR',
    timezoneId: 'Asia/Seoul',
  });
  const page = await ctx.newPage();
  await page.goto('file://' + path.join(__dirname, 'studio.html'));
  await sleep(1200);

  // 자막을 띄운 뒤 읽을 시간을 준다
  const cap = async (a, b, c) => {
    await page.evaluate(([a, b, c]) => window.cap(a, b, c), [a, b, c]);
    await sleep(1800);
  };
  const load  = (u) => page.evaluate((u) => window.loadApp(u), u);
  const frame = () => page.frameLocator('#frame');

  // 프레임 안의 버튼을 글자로 찾아 누른다
  async function tap(label, wait = 1600) {
    await frame().locator(`button:has-text("${label}")`).first().click({ timeout: 15000 });
    await sleep(wait);
  }

  // ── 타이틀 카드
  await sleep(2600);
  await page.evaluate(() => window.hideCover());
  await sleep(900);

  // ── 1. 추천 화면
  await cap('충전 케이블을 꽂으면 쉼표가 스스로 열립니다',
            '차량이 주행 이력·시각·동승자·차량 상태를 한 번에 읽고 지금 가장 알맞은 모드를 먼저 제안합니다', '01');
  await load(APP);
  await sleep(5200);

  await cap('2시간 20분을 달린 명절 귀성 상황',
            '휴식 3점, 놀이 3점으로 동점이지만 안전 우선 순서에 따라 휴식이 추천됩니다', '01');
  await sleep(4200);

  // ── 2. 휴식 모드
  await cap('휴식 모드 — 남은 충전 시간이 그대로 타이머가 됩니다',
            '링의 끝이 쉼표 꼬리처럼 말립니다. 완충 1분 전에 화면이 서서히 밝아지며 깨웁니다', '02');
  await tap('시작하기', 6000);

  await cap('차량 제어는 2단계입니다',
            'Vehicle Control SDK가 준비 중이라 시트·조명·공조 제어는 분리했습니다. 타이머와 기상 알림은 조회 API만으로 동작합니다', '02');
  await sleep(4600);

  // ── 3. 시뮬레이터 → 경고 발생
  await tap('지금 일어나기', 1500);
  await cap('차량 데이터를 직접 바꿔 보실 수 있습니다',
            '슬라이더마다 대응되는 실제 Pleos SDK 메서드명을 적어 두었습니다', '03');
  await tap('차량 데이터', 3200);

  await cap('타이어 경고를 켜면 추천이 즉시 바뀝니다',
            '놀이가 5점으로 가장 높지만 경고가 있으면 점검이 최우선 규칙으로 이깁니다', '03');
  await frame().locator('button:has-text("경고 발생")').first().click();
  await sleep(6000);

  // ── 4. 점검 모드
  await cap('점검 모드 — 수치가 아니라 상태로 보여줍니다',
            'Pleos SDK는 실측 공기압이 아니라 저압 경고 점등 여부를 제공합니다. 문서를 확인하고 설계를 바꾼 부분입니다', '04');
  await tap('차량 데이터', 800);     // 패널 닫기
  await tap('시작하기', 6500);

  // ── 5. 놀이 모드
  await tap('다른 모드 고르기', 1200);
  await cap('아이들을 뒤에 태운 주말 여행이라면',
            '짧은 주행 + 뒷좌석 동승자 조합에서는 놀이가 1위가 됩니다', '05');
  await tap('차량 데이터', 1500);
  await frame().locator('button:has-text("주말 여행")').first().click();
  await sleep(4200);
  await tap('차량 데이터', 800);
  await tap('시작하기', 3000);

  await cap('오늘 달린 기록이 그대로 문제가 됩니다',
            '주행 거리·평균 속도·탑승 인원을 문제로 바꿔 동승자와 함께 풉니다. 좌석별로 점수가 쌓입니다', '05');
  const choices = frame().locator('.choices button');
  for (let i = 0; i < 3; i++) {
    try { await choices.nth(i % 4).click({ timeout: 4000 }); } catch (e) {}
    await sleep(1800);
  }

  // ── 6. 폴백
  await tap('다른 모드 고르기', 1200);
  await cap('목적지를 설정하지 않으면 어떻게 될까요',
            'ChargingPlan이 내려오지 않으므로 배터리 변화율로 추정합니다. 상단에 "추정" 표시가 붙습니다', '06');
  await tap('차량 데이터', 1500);
  await frame().locator('button:has-text("출장 이동")').first().click();
  await sleep(4800);
  await tap('차량 데이터', 800);

  // ── 7. 콘텐츠 모드 — 재생목록이 충분히 보이도록 충전 시간을 되돌린다
  await cap('콘텐츠 모드 — 남은 시간에 길이를 맞춥니다',
            '충전 시간을 빈틈없이 채우고, 넘치는 분량은 출발 후 이어 듣도록 나눕니다', '07');
  await tap('차량 데이터', 1200);
  await frame().locator('button:has-text("명절 귀성")').first().click();
  await sleep(3200);
  await tap('차량 데이터', 900);
  await frame().locator('button:has-text("콘텐츠")').first().click();
  await sleep(7000);

  // ── 8. 관리자
  await load(ADMIN);
  await sleep(2600);
  await cap('운영 관리자도 함께 만들었습니다',
            '추천 수락률·만족도·폴백 비율, 추천과 실제 선택의 차이를 추적합니다', '08');
  await sleep(5200);

  await page.evaluate(() => window.loadApp('https://shimpyo-admin.revuplan.com/sessions'));
  await sleep(2400);
  await cap('어떤 규칙이 몇 점을 줬는지까지 되짚을 수 있습니다',
            '머신러닝이 아니라 규칙 점수 합산이라 모든 추천을 설명할 수 있습니다', '08');
  await sleep(1600);
  try { await frame().locator('.row').first().click({ timeout: 6000 }); } catch (e) {}
  await sleep(5500);

  // ── 마무리
  await page.evaluate(() => window.showCover('쉼표', '충전 대기 20분을, 차가 알아서 채웁니다'));
  await sleep(3600);

  await ctx.close();
  await browser.close();
  console.log('녹화 완료');
})().catch(e => { console.error('실패:', e.message); process.exit(1); });
