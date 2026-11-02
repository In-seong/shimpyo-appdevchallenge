<?php

namespace App\Services;

use App\Models\PersonalWeight;
use App\Models\SignalRule;

/**
 * 상황 인식 추천 엔진.
 *
 * 머신러닝 없이 "규칙 점수 합산 + 피드백 누적 가중치"만으로 동작한다.
 * 규칙은 DB(signal_rules)에서 읽으므로 관리자 화면에서 실시간으로 조정된다.
 */
class RecommendationEngine
{
    public const MODES = ['content', 'rest', 'check', 'play'];

    /**
     * 동점일 때의 우선순위 — 안전이 먼저다.
     * 점검(차량 상태) > 휴식(운전자 상태) > 콘텐츠 > 놀이.
     * 명시하지 않으면 배열 선언 순서로 우연히 결정돼 설명할 수 없는 추천이 나온다.
     */
    public const TIE_ORDER = ['check', 'rest', 'content', 'play'];

    public const MODE_LABEL = [
        'content' => '콘텐츠',
        'rest'    => '휴식',
        'check'   => '점검',
        'play'    => '놀이',
    ];

    public function __construct(private string $profile = 'demo')
    {
    }

    /**
     * @param  array  $signals  PleosVehicleGateway::toSignals() 결과
     * @return array{scores:array,breakdown:array,recommended:string,reason:string,ranked:array,situation_key:string,personal:array}
     */
    public function evaluate(array $signals): array
    {
        $scores    = array_fill_keys(self::MODES, 0);
        $breakdown = [];
        $override  = null;

        $rules = SignalRule::where('active', true)->orderBy('sort')->get();

        foreach ($rules as $rule) {
            $value = $signals[$rule->signal_key] ?? null;

            if (! $rule->matches($value)) {
                continue;
            }

            $scores[$rule->target_mode] += $rule->weight;

            $breakdown[] = [
                'rule_id' => $rule->id,
                'label'   => $rule->label,
                'signal'  => $rule->signal_key,
                'value'   => $value,
                'mode'    => $rule->target_mode,
                'weight'  => $rule->weight,
                'source'  => $rule->source,
                'override'=> $rule->is_override,
            ];

            if ($rule->is_override && $rule->weight > 0) {
                $override = $rule->target_mode;
            }
        }

        $situationKey = $this->situationKey($signals);
        $personal     = $this->personalWeights($situationKey);

        foreach ($personal as $mode => $delta) {
            if (! isset($scores[$mode])) {
                continue;
            }

            $scores[$mode] += $delta;

            $breakdown[] = [
                'rule_id' => null,
                'label'   => '개인화 누적 (' . $situationKey . ')',
                'signal'  => 'feedback',
                'value'   => $delta,
                'mode'    => $mode,
                'weight'  => $delta,
                'source'  => 'personal_weights',
                'override'=> false,
            ];
        }

        // 점수 내림차순, 동점이면 TIE_ORDER 순서로 가른다
        uksort($scores, function (string $a, string $b) use ($scores): int {
            return $scores[$b] <=> $scores[$a]
                ?: array_search($a, self::TIE_ORDER, true) <=> array_search($b, self::TIE_ORDER, true);
        });

        $recommended = $override ?? array_key_first($scores);

        $ranked = [];
        foreach ($scores as $mode => $score) {
            $ranked[] = [
                'mode'  => $mode,
                'label' => self::MODE_LABEL[$mode],
                'score' => $score,
            ];
        }

        return [
            'scores'        => $scores,
            'breakdown'     => $breakdown,
            'recommended'   => $recommended,
            'reason'        => $this->reason($recommended, $signals, $breakdown),
            'ranked'        => $ranked,
            'situation_key' => $situationKey,
            'personal'      => $personal,
        ];
    }

    /** 시간대 × 동승자 × 주행시간 조합 — 개인화 가중치의 키 */
    public function situationKey(array $s): string
    {
        $hour = (int) ($s['clock_hour'] ?? 12);
        $time = match (true) {
            $hour >= 22 || $hour < 6 => 'night',
            $hour < 12               => 'morning',
            $hour < 18               => 'afternoon',
            default                  => 'evening',
        };

        $who  = ($s['rear_occupant'] ?? 0) ? 'rear'
              : (($s['passenger_count'] ?? 1) > 1 ? 'pair' : 'solo');

        $hours = (float) ($s['driving_time_hours'] ?? 0);
        $trip  = $hours >= 2 ? 'long' : ($hours >= 1 ? 'mid' : 'short');

        return "{$time}|{$who}|{$trip}";
    }

    private function personalWeights(string $key): array
    {
        return PersonalWeight::where('profile', $this->profile)
            ->where('situation_key', $key)
            ->pluck('delta', 'mode')
            ->toArray();
    }

    /** 추천 카드에 한 줄로 보여줄 이유 */
    private function reason(string $mode, array $s, array $breakdown): string
    {
        $minutes = (int) ($s['charge_minutes'] ?? 20);

        // 그 모드에 가장 크게 기여한 규칙을 근거로 삼는다.
        $top = null;
        foreach ($breakdown as $b) {
            if ($b['mode'] === $mode && $b['weight'] > 0) {
                if ($top === null || $b['weight'] > $top['weight']) {
                    $top = $b;
                }
            }
        }

        $hours = (float) ($s['driving_time_hours'] ?? 0);
        $hourText = $hours >= 1 ? floor($hours) . '시간' : (int) round($hours * 60) . '분';

        return match ($mode) {
            'rest' => ($s['clock_hour'] >= 22 || $s['clock_hour'] < 6)
                ? "늦은 시간이네요. {$minutes}분 동안 눈 붙이고 가시겠어요?"
                : "{$hourText} 넘게 달리셨어요. {$minutes}분 동안 쉬어가는 건 어때요?",
            'check' => ($s['warning_count'] ?? 0) > 0
                ? "점검할 항목이 " . $s['warning_count'] . "개 있어요. {$minutes}분이면 충분해요."
                : "도착 시 배터리가 " . ($s['soc_at_dest'] ?? 0) . "% 예상이에요. 다음 구간을 함께 확인해요.",
            'play' => "뒷좌석에 동승자가 있네요. {$minutes}분 동안 같이 퀴즈 한 판 어때요?",
            'content' => $minutes <= 15
                ? "{$minutes}분이면 짧아요. 딱 맞는 에피소드 하나 골라뒀어요."
                : "{$minutes}분 동안 들을 거리를 길이에 맞춰 준비했어요.",
            default => "{$minutes}분 동안 무엇을 하시겠어요?",
        };
    }

    /** 피드백 1건을 개인화 가중치에 누적 */
    public function applyFeedback(string $situationKey, string $mode, int $rating): void
    {
        $row = PersonalWeight::firstOrCreate(
            ['profile' => $this->profile, 'situation_key' => $situationKey, 'mode' => $mode],
            ['delta' => 0]
        );

        $row->delta += ($rating >= 0 ? 1 : -1);
        $row->save();
    }
}
