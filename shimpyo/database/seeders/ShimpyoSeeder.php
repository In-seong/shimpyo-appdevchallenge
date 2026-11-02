<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\QuizTemplate;
use App\Models\SignalRule;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class ShimpyoSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::firstOrCreate(
            ['name' => '아이오닉 5'],
            ['plate' => '12가 3456', 'battery_capacity_kwh' => 77]
        );

        // ── 추천 엔진 규칙 (기획서 5장 표 그대로)
        $rules = [
            ['driving_time_hours', '연속 주행 2시간 이상 → 휴식', 'Odometer.getTripCurrentDrivingTime()', 'gte', 2, null, 'rest', 3, false, 10],
            ['driving_time_hours', '연속 주행 1시간 미만 → 콘텐츠', 'Odometer.getTripCurrentDrivingTime()', 'lt', 1, null, 'content', 2, false, 20],
            ['driving_time_hours', '연속 주행 1시간 미만 → 놀이', 'Odometer.getTripCurrentDrivingTime()', 'lt', 1, null, 'play', 2, false, 30],
            ['clock_hour', '22시~06시 야간 → 휴식', '시스템 시계', 'wrap', 22, 6, 'rest', 2, false, 40],
            ['clock_hour', '06시~22시 주간 → 콘텐츠', '시스템 시계', 'between', 6, 21, 'content', 1, false, 50],
            ['rear_occupant', '뒷좌석 동승자 있음 → 놀이', 'Seat.getCurrentSeatOccupancy()', 'eq', 1, null, 'play', 3, false, 60],
            ['passenger_count', '혼자 탑승 → 놀이 감점', 'Seat.getCurrentSeatOccupancy()', 'eq', 1, null, 'play', -2, false, 70],
            ['warning_count', '차량 경고 1개 이상 → 점검 (최우선)', 'Safety.getTelltalesInfo()', 'gte', 1, null, 'check', 4, true, 80],
            ['soc_at_dest', '도착 시 예상 배터리 20% 미만 → 점검', 'RouteStateInfo.estimatedSocAtDest', 'lt', 20, null, 'check', 3, false, 90],
            ['charge_minutes', '충전 15분 이하 → 콘텐츠', 'ChargingPlan.duration', 'lte', 15, null, 'content', 1, false, 100],
            ['charge_minutes', '충전 30분 이상 → 휴식', 'ChargingPlan.duration', 'gte', 30, null, 'rest', 1, false, 110],
        ];

        foreach ($rules as [$key, $label, $src, $op, $a, $b, $mode, $w, $ovr, $sort]) {
            SignalRule::firstOrCreate(
                ['signal_key' => $key, 'target_mode' => $mode, 'operator' => $op, 'value_a' => $a],
                [
                    'label' => $label, 'source' => $src, 'value_b' => $b,
                    'weight' => $w, 'is_override' => $ovr, 'active' => true, 'sort' => $sort,
                ]
            );
        }

        // ── 콘텐츠 풀
        $contents = [
            ['podcast', '지구 반대편의 아침', '여행하는 라디오', 14 * 60 + 20, '여행', 'amber'],
            ['podcast', '전기차, 어디까지 왔나', '모빌리티 브리핑', 22 * 60, '자동차', 'sky'],
            ['podcast', '10분 만에 듣는 오늘의 뉴스', '데일리 브리핑', 9 * 60 + 40, '뉴스', 'rose'],
            ['podcast', '고속도로 위의 작은 역사', '길 위의 인문학', 27 * 60 + 10, '교양', 'violet'],
            ['podcast', '잠들기 전 5분 명상', '마음챙김', 5 * 60, '휴식', 'emerald'],
            ['podcast', '운전하며 듣는 재즈 이야기', '뮤직 라운지', 18 * 60 + 30, '음악', 'amber'],
            ['music', '드라이브 플레이리스트 — 가을', '쉼표 큐레이션', 31 * 60, '음악', 'sky'],
            ['music', '조용한 피아노 모음', '쉼표 큐레이션', 42 * 60, '음악', 'violet'],
            ['music', '90년대 가요 베스트', '쉼표 큐레이션', 25 * 60, '음악', 'rose'],
            ['music', '빗소리와 함께', '쉼표 큐레이션', 12 * 60, '휴식', 'emerald'],
            ['video', '5분 요약 — 이번 주 영화', '시네마 다이제스트', 5 * 60 + 30, '영화', 'rose'],
            ['video', '휴게소 음식 랭킹 TOP 10', '여행 채널', 11 * 60, '여행', 'amber'],
            ['video', '전기차 충전 꿀팁 모음', 'EV 라이프', 7 * 60 + 50, '자동차', 'sky'],
        ];

        foreach ($contents as [$type, $title, $creator, $dur, $cat, $accent]) {
            Content::firstOrCreate(
                ['title' => $title],
                ['type' => $type, 'creator' => $creator, 'duration_sec' => $dur,
                 'category' => $cat, 'accent' => $accent, 'active' => true]
            );
        }

        // ── 퀴즈 템플릿
        $quizzes = [
            ['오늘 우리가 달려온 거리는 몇 km일까요?', 'distance_km', 'km', 15],
            ['오늘 운전한 시간은 몇 분일까요?', 'driving_time', '분', 20],
            ['오늘 평균 속도는 몇 km/h였을까요?', 'avg_speed', 'km/h', 15],
            ['지금 차에 타고 있는 사람은 몇 명일까요?', 'passengers', '명', 50],
            ['충전을 시작할 때 배터리는 몇 %였을까요?', 'soc', '%', 25],
            ['이번 충전은 몇 분 걸릴까요?', 'charge_minutes', '분', 30],
            ['지금 충전기의 최대 출력은 몇 kW일까요?', 'charging_power', 'kW', 40],
        ];

        foreach ($quizzes as [$q, $key, $unit, $spread]) {
            QuizTemplate::firstOrCreate(
                ['question' => $q],
                ['data_key' => $key, 'unit' => $unit, 'spread' => $spread, 'active' => true]
            );
        }
    }
}
