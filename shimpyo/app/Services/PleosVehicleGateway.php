<?php

namespace App\Services;

/**
 * Pleos Connect SDK 모의 게이트웨이.
 *
 * 메서드명은 실제 SDK와 1:1로 맞췄다. 공모전 승인 후에는 이 클래스의 내부 구현만
 * 실제 SDK 호출로 교체하면 되고, 상위 로직(RecommendationEngine 등)은 손대지 않는다.
 *
 *  - Vehicle SDK     : EvBattery / Seat / HVAC / Light / Safety / Odometer / Tire
 *  - NaviHelper SDK  : ChargingPlan / DrivingState / RouteStateInfo
 *
 * 참고: https://document.pleos.ai/docs/pleos-only/connect-sdk-pleos/Vehicle/intro
 */
class PleosVehicleGateway
{
    /** 시뮬레이터가 넘겨준 차량 상태 */
    public function __construct(private array $s = [])
    {
    }

    public static function fromSimulator(array $state): self
    {
        return new self($state);
    }

    private function v(string $k, mixed $default = null): mixed
    {
        return $this->s[$k] ?? $default;
    }

    // ───────────────────────── EvBattery (permission: CAR_ENERGY)

    /** EvBattery.getEvChargeSwitchState() → Boolean? — 현재 충전 중 여부 */
    public function getEvChargeSwitchState(): bool
    {
        return (bool) $this->v('charging', true);
    }

    /** EvBattery.getEvBatteryStateOfCharge() → Int? — 배터리 잔량 (%) */
    public function getEvBatteryStateOfCharge(): int
    {
        return (int) $this->v('soc', 42);
    }

    /** EvBattery.setEvChargePercentLimit() 로 설정된 목표 충전율 (%) */
    public function getEvChargePercentLimit(): int
    {
        return (int) $this->v('target_soc', 80);
    }

    // ───────────────────────── NaviHelper (ChargingPlan / DrivingState)

    /**
     * NaviHelper DrivingState.chargingPlan → ChargingPlan(duration, startSoc, endSoc)
     *
     * 경로 안내 중일 때만 내려온다. 목적지 미설정이면 null.
     * duration 단위는 초(sec).
     */
    public function getChargingPlan(): ?array
    {
        if (! $this->v('navigating', true)) {
            return null;
        }

        return [
            'duration' => (int) ($this->v('charge_duration_sec') ?? $this->estimateSeconds()),
            'startSoc' => $this->getEvBatteryStateOfCharge(),
            'endSoc'   => $this->getEvChargePercentLimit(),
        ];
    }

    /** DrivingState.maxChargingPower → Int (kW) */
    public function getMaxChargingPower(): int
    {
        return (int) $this->v('max_charging_power', 200);
    }

    /** RouteStateInfo.estimatedSocAtDest → 목적지 도착 시 예상 배터리 (%) */
    public function getEstimatedSocAtDest(): ?int
    {
        return $this->v('navigating', true) ? (int) $this->v('soc_at_dest', 34) : null;
    }

    /** NaviHelper.getDestinationInfo() */
    public function getDestinationInfo(): ?array
    {
        if (! $this->v('navigating', true)) {
            return null;
        }

        return [
            'name'          => $this->v('destination', '강릉역'),
            'remainDistance' => (int) $this->v('remain_distance_km', 164),
            'remainTime'    => (int) $this->v('remain_time_sec', 7200),
            'nextCharger'   => $this->v('next_charger', '안성휴게소'),
            'chargerCount'  => (int) $this->v('charger_count', 6),
        ];
    }

    // ───────────────────────── Odometer

    /** Odometer.getTripCurrentDrivingTime() → 시동 후 연속 주행 시간 (초) */
    public function getTripCurrentDrivingTime(): int
    {
        return (int) $this->v('driving_time_sec', 2 * 3600 + 20 * 60);
    }

    /** Odometer.getTripCurrentDrivingDistance() → 당일 주행 거리 (km) */
    public function getTripCurrentDrivingDistance(): float
    {
        return (float) $this->v('driving_distance_km', 186.4);
    }

    // ───────────────────────── Seat (착좌 / 안전벨트)

    /** Seat.getCurrentSeatOccupancy(area) → 좌석 착좌 여부 */
    public function getCurrentSeatOccupancy(): array
    {
        return [
            'driver'     => true,
            'passenger'  => (bool) $this->v('seat_passenger', true),
            'rearLeft'   => (bool) $this->v('seat_rear_left', true),
            'rearRight'  => (bool) $this->v('seat_rear_right', false),
        ];
    }

    public function passengerCount(): int
    {
        return count(array_filter($this->getCurrentSeatOccupancy()));
    }

    public function hasRearOccupant(): bool
    {
        $o = $this->getCurrentSeatOccupancy();

        return $o['rearLeft'] || $o['rearRight'];
    }

    // ───────────────────────── Safety / Tire (점검 모드)

    /**
     * Safety.getTelltalesInfo() + Tire / 워셔액 / 오일 경고.
     *
     * 주의: Tire.getLowTirePressure(area) 는 "저압 경고 임계값(kPa)"을 주며
     * 실측 공기압이 아니다. 따라서 수치가 아닌 경고 점등 여부로만 판단한다.
     */
    public function getTelltalesInfo(): array
    {
        return [
            [
                'key'    => 'tire',
                'label'  => '타이어 공기압',
                'warn'   => (bool) $this->v('warn_tire', false),
                'detail' => $this->v('warn_tire', false) ? '앞 우측 저압 경고' : '경고 없음',
                'api'    => 'Tire.getLowTirePressure() / Safety.getTelltalesInfo()',
            ],
            [
                'key'    => 'washer',
                'label'  => '워셔액',
                'warn'   => (bool) $this->v('warn_washer', false),
                'detail' => $this->v('warn_washer', false) ? '보충 필요' : '충분',
                'api'    => 'Safety.hasLowWasherFluidWarning()',
            ],
            [
                'key'    => 'oil',
                'label'  => '엔진오일 레벨',
                'warn'   => (bool) $this->v('warn_oil', false),
                'detail' => $this->v('warn_oil', false) ? '점검 필요' : '정상',
                'api'    => 'Safety.getEngineOilLevelState()',
            ],
            [
                'key'    => 'brake',
                'label'  => '브레이크액 레벨',
                'warn'   => (bool) $this->v('warn_brake', false),
                'detail' => $this->v('warn_brake', false) ? '점검 필요' : '정상',
                'api'    => 'Safety.getBrakeFluidLevelState()',
            ],
        ];
    }

    public function warningCount(): int
    {
        return count(array_filter($this->getTelltalesInfo(), fn ($t) => $t['warn']));
    }

    /** 타이어 4륜 상태 (수치 아님 — 경고 점등 여부) */
    public function getTireStatus(): array
    {
        $warn = (bool) $this->v('warn_tire', false);

        return [
            'frontLeft'  => false,
            'frontRight' => $warn,
            'rearLeft'   => false,
            'rearRight'  => false,
        ];
    }

    // ───────────────────────── 차량 시계

    public function getClockHour(): int
    {
        return (int) $this->v('hour', (int) date('G'));
    }

    // ───────────────────────── 충전 타이머 (주 경로 + 폴백)

    /**
     * 남은 충전 시간(초)을 구한다.
     *
     *  1순위  NaviHelper ChargingPlan.duration        — 경로 안내 중
     *  폴백   SoC 실시간 변화율 기반 선형 외삽         — 목적지 미설정
     *
     * 폴백에서는 80% 이후 충전 속도 감소를 보정 계수로 반영한다.
     *
     * @return array{seconds:int, fallback:bool, startSoc:int, endSoc:int}
     */
    public function resolveChargeTimer(): array
    {
        if ($plan = $this->getChargingPlan()) {
            return [
                'seconds'  => $plan['duration'],
                'fallback' => false,
                'startSoc' => $plan['startSoc'],
                'endSoc'   => $plan['endSoc'],
            ];
        }

        return [
            'seconds'  => $this->estimateSeconds(),
            'fallback' => true,
            'startSoc' => $this->getEvBatteryStateOfCharge(),
            'endSoc'   => $this->getEvChargePercentLimit(),
        ];
    }

    /**
     * SoC 변화율 기반 소요 시간 추정 (초).
     *
     * 충전 곡선 보정 계수는 기획서 실측치(200kW 충전기로 10→80% 22분)에 맞췄다.
     */
    private function estimateSeconds(): int
    {
        $soc      = $this->getEvBatteryStateOfCharge();
        $target   = $this->getEvChargePercentLimit();
        $power    = max(1, $this->getMaxChargingPower());
        $capacity = (float) $this->v('battery_capacity_kwh', 77);

        $seconds = 0;
        for ($p = $soc; $p < $target; $p++) {
            $kwh    = $capacity / 100;
            $derate = $p >= 80 ? 0.30 : ($p >= 50 ? 0.62 : 0.85);
            $seconds += (int) round($kwh / ($power * $derate) * 3600);
        }

        return max(60, $seconds);
    }

    // ───────────────────────── 제어 (휴식 모드)

    /** Seat.moveSeatBackrestAngle() */
    public function moveSeatBackrestAngle(int $angle): array
    {
        return ['ok' => true, 'api' => 'Seat.moveSeatBackrestAngle', 'value' => $angle];
    }

    /** Light.setAmbientLightBrightness() */
    public function setAmbientLightBrightness(int $percent): array
    {
        return ['ok' => true, 'api' => 'Light.setAmbientLightBrightness', 'value' => $percent];
    }

    /** HVAC.setHvacTemperature() */
    public function setHvacTemperature(float $celsius): array
    {
        return ['ok' => true, 'api' => 'HVAC.setHvacTemperature', 'value' => $celsius];
    }

    // ───────────────────────── 추천 엔진 입력 신호로 정규화

    public function toSignals(): array
    {
        $timer = $this->resolveChargeTimer();
        $dest  = $this->getDestinationInfo();

        return [
            'driving_time_hours' => round($this->getTripCurrentDrivingTime() / 3600, 2),
            'clock_hour'         => $this->getClockHour(),
            'rear_occupant'      => $this->hasRearOccupant() ? 1 : 0,
            'passenger_count'    => $this->passengerCount(),
            'warning_count'      => $this->warningCount(),
            'soc_at_dest'        => $this->getEstimatedSocAtDest(),
            'charge_minutes'     => (int) round($timer['seconds'] / 60),
            // 참고용 (점수에는 직접 쓰이지 않음)
            'soc'                => $this->getEvBatteryStateOfCharge(),
            'target_soc'         => $this->getEvChargePercentLimit(),
            'max_charging_power' => $this->getMaxChargingPower(),
            'driving_distance_km'=> $this->getTripCurrentDrivingDistance(),
            'destination'        => $dest['name'] ?? null,
            // 콘텐츠 모드가 '출발 후 이어 들을 분량'을 계산할 때 쓴다
            'remain_time_sec'    => $dest['remainTime'] ?? null,
            'remain_distance_km' => $dest['remainDistance'] ?? null,
            'navigating'         => $dest !== null,
        ];
    }
}
