<?php

namespace App\Services;

use App\Models\QuizTemplate;

/**
 * 당일 주행 데이터를 문제로 바꾼다.
 *
 * 실제 구현에서는 Pleos LLM SDK(generateContent)에 주행 요약을 넘겨 문장을 생성하지만,
 * 데모에서는 템플릿 + 실제 주행 수치로 동일한 결과를 만든다.
 * (LLM 호출 지점은 buildQuestion() 한 곳으로 격리해 두었다)
 */
class QuizBuilder
{
    public function build(array $signals, int $count = 10): array
    {
        $templates = QuizTemplate::where('active', true)->get();

        if ($templates->isEmpty()) {
            return [];
        }

        $facts = $this->facts($signals);
        $out   = [];
        $i     = 0;

        while (count($out) < $count && $i < $count * 3) {
            $t = $templates[$i % $templates->count()];
            $i++;

            $answer = $facts[$t->data_key] ?? null;
            if ($answer === null) {
                continue;
            }

            $out[] = $this->buildQuestion($t, $answer, count($out) + 1);
        }

        return $out;
    }

    /** 주행 데이터에서 정답이 될 수 있는 값들 */
    private function facts(array $s): array
    {
        $distance = (float) ($s['driving_distance_km'] ?? 0);
        $hours    = (float) ($s['driving_time_hours'] ?? 0);

        return [
            'distance_km'   => (int) round($distance),
            'driving_time'  => (int) round($hours * 60),
            'avg_speed'     => $hours > 0 ? (int) round($distance / $hours) : 0,
            'passengers'    => (int) ($s['passenger_count'] ?? 1),
            'soc'           => (int) ($s['soc'] ?? 0),
            'charge_minutes'=> (int) ($s['charge_minutes'] ?? 0),
            'charging_power'=> (int) ($s['max_charging_power'] ?? 0),
        ];
    }

    private function buildQuestion(QuizTemplate $t, int $answer, int $no): array
    {
        $choices = $this->choices($answer, $t->spread);

        return [
            'no'       => $no,
            'question' => $t->question,
            'unit'     => $t->unit,
            'answer'   => $answer,
            'choices'  => $choices,
            'data_key' => $t->data_key,
        ];
    }

    /** 정답 주변으로 그럴듯한 오답 3개를 만든다 */
    private function choices(int $answer, int $spreadPercent): array
    {
        $step = max(1, (int) round($answer * $spreadPercent / 100));
        $set  = [$answer];

        $offsets = [-2, -1, 1, 2, 3, -3];
        foreach ($offsets as $o) {
            if (count($set) >= 4) {
                break;
            }
            $cand = $answer + ($o * $step);
            if ($cand > 0 && ! in_array($cand, $set, true)) {
                $set[] = $cand;
            }
        }

        while (count($set) < 4) {
            $set[] = max(1, $answer + count($set) * $step);
        }

        sort($set);

        return array_values(array_unique($set));
    }
}
