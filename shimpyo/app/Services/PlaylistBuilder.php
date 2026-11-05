<?php

namespace App\Services;

use App\Models\Content;

/**
 * 남은 충전 시간에 길이를 맞춘 재생목록을 만든다.
 *
 * 충전 시간만으로 부족하면 목적지까지 남은 주행 시간까지 합쳐 긴 목록을 구성한다.
 */
class PlaylistBuilder
{
    /**
     * @return array{items:array,total_sec:int,fill_rate:float,spill_sec:int}
     */
    public function build(int $availableSec, ?int $driveSec = null, ?string $type = null): array
    {
        $pool = Content::where('active', true)
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('duration_sec')
            ->get();

        if ($pool->isEmpty()) {
            return ['items' => [], 'total_sec' => 0, 'fill_rate' => 0.0, 'spill_sec' => 0];
        }

        $budget = $availableSec;
        $items  = [];
        $total  = 0;

        /*
         * 남은 시간을 채우되 "여러 편"이 되도록 고른다.
         *
         * 단순히 들어가는 가장 긴 것을 고르면 12분 예산에 12분짜리 한 편이 뽑혀
         * 채움률은 높아도 재생목록이라 부르기 어려워진다. 그래서 뒤에 최소 한 편이
         * 더 들어갈 여지를 남기는 선에서 가장 긴 것을 고르고, 더는 쪼갤 수 없을 때만
         * 남은 예산을 가장 크게 채우는 것으로 마무리한다.
         */
        $remaining = $pool->all();
        $shortest  = $pool->min('duration_sec');

        while ($budget > 60 && $remaining) {
            // 한 편 더 들어갈 여유가 있으면 그만큼 상한을 낮춘다
            $cap = ($budget >= $shortest * 2) ? $budget - $shortest : $budget;

            $pick = null;
            foreach ($remaining as $i => $c) {
                if ($c->duration_sec <= $cap && ($pick === null || $c->duration_sec > $pick[1]->duration_sec)) {
                    $pick = [$i, $c];
                }
            }

            // 상한을 낮춰서 아무것도 못 고르면 원래 예산으로 한 번 더 본다
            if ($pick === null && $cap < $budget) {
                foreach ($remaining as $i => $c) {
                    if ($c->duration_sec <= $budget && ($pick === null || $c->duration_sec > $pick[1]->duration_sec)) {
                        $pick = [$i, $c];
                    }
                }
            }

            if ($pick === null) {
                break;
            }

            [$idx, $content] = $pick;
            unset($remaining[$idx]);

            $items[] = $this->row($content, false);
            $budget -= $content->duration_sec;
            $total  += $content->duration_sec;
        }

        // 충전이 끝나도 이어서 들을 분량 (주행 구간)
        $spill = 0;
        if ($driveSec && $driveSec > 0) {
            $driveBudget = $driveSec;
            foreach ($remaining as $c) {
                if ($c->duration_sec > $driveBudget) {
                    continue;
                }
                $items[] = $this->row($c, true);
                $driveBudget -= $c->duration_sec;
                $spill += $c->duration_sec;
            }
        }

        return [
            'items'     => $items,
            'total_sec' => $total,
            'fill_rate' => $availableSec > 0 ? round($total / $availableSec, 3) : 0.0,
            'spill_sec' => $spill,
        ];
    }

    private function row(Content $c, bool $afterCharge): array
    {
        return [
            'id'           => $c->id,
            'type'         => $c->type,
            'title'        => $c->title,
            'creator'      => $c->creator,
            'duration_sec' => $c->duration_sec,
            'category'     => $c->category,
            'accent'       => $c->accent,
            'after_charge' => $afterCharge,
        ];
    }
}
