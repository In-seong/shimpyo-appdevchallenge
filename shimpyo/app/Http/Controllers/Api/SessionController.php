<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\Feedback;
use App\Models\Vehicle;
use App\Services\PleosVehicleGateway;
use App\Services\PlaylistBuilder;
use App\Services\QuizBuilder;
use App\Services\RecommendationEngine;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /** 충전 케이블 연결 감지 → 신호 읽기 → 추천 산출 */
    public function start(Request $request)
    {
        $state = $request->input('state', []);

        $gateway = PleosVehicleGateway::fromSimulator($state);
        $signals = $gateway->toSignals();
        $timer   = $gateway->resolveChargeTimer();

        $engine = new RecommendationEngine();
        $result = $engine->evaluate($signals);

        $vehicle = Vehicle::firstOrCreate(
            ['name' => '아이오닉 5'],
            ['plate' => '12가 3456', 'battery_capacity_kwh' => 77]
        );

        $session = ChargingSession::create([
            'vehicle_id'          => $vehicle->id,
            'scenario'            => $request->input('scenario'),
            'signals'             => $signals,
            'scores'              => $result['scores'],
            'breakdown'           => $result['breakdown'],
            'recommended_mode'    => $result['recommended'],
            'reason'              => $result['reason'],
            'situation_key'       => $result['situation_key'],
            'charge_duration_sec' => $timer['seconds'],
            'start_soc'           => $timer['startSoc'],
            'end_soc'             => $timer['endSoc'],
            'used_fallback'       => $timer['fallback'],
        ]);

        return response()->json([
            'session_id' => $session->id,
            'vehicle'    => $vehicle->only(['name', 'plate']),
            'signals'    => $signals,
            'timer'      => $timer,
            'recommend'  => $result,
            'telltales'  => $gateway->getTelltalesInfo(),
            'tires'      => $gateway->getTireStatus(),
            'seats'      => $gateway->getCurrentSeatOccupancy(),
            'destination'=> $gateway->getDestinationInfo(),
        ]);
    }

    /** 사용자가 고른 모드 기록 */
    public function select(Request $request, ChargingSession $session)
    {
        $mode = $request->validate([
            'mode' => 'required|in:content,rest,check,play',
        ])['mode'];

        $session->update(['selected_mode' => $mode]);

        return response()->json(['ok' => true, 'selected_mode' => $mode]);
    }

    /** 콘텐츠 모드 재생목록 */
    public function playlist(Request $request, ChargingSession $session)
    {
        $driveSec = (int) ($session->signals['remain_time_sec'] ?? 0);

        $list = (new PlaylistBuilder())->build(
            $session->charge_duration_sec,
            $driveSec ?: null,
            $request->query('type')
        );

        return response()->json($list);
    }

    /** 놀이 모드 문제 */
    public function quiz(ChargingSession $session)
    {
        return response()->json([
            'questions' => (new QuizBuilder())->build($session->signals, 10),
        ]);
    }

    /** 출발 직후 피드백 1회 → 개인화 가중치 누적 */
    public function feedback(Request $request, ChargingSession $session)
    {
        $data = $request->validate([
            'rating' => 'required|integer|in:-1,1',
            'via'    => 'nullable|in:touch,voice',
        ]);

        $mode = $session->selected_mode ?? $session->recommended_mode;

        Feedback::updateOrCreate(
            ['charging_session_id' => $session->id],
            [
                'mode'          => $mode,
                'situation_key' => $session->situation_key,
                'rating'        => $data['rating'],
                'via'           => $data['via'] ?? 'touch',
            ]
        );

        (new RecommendationEngine())->applyFeedback($session->situation_key, $mode, $data['rating']);

        $session->update(['ended_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** 휴식 모드 차량 제어 (모의) */
    public function control(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:seat,light,hvac',
            'value'  => 'required|numeric',
        ]);

        $gateway = new PleosVehicleGateway();

        $result = match ($data['action']) {
            'seat'  => $gateway->moveSeatBackrestAngle((int) $data['value']),
            'light' => $gateway->setAmbientLightBrightness((int) $data['value']),
            'hvac'  => $gateway->setHvacTemperature((float) $data['value']),
        };

        return response()->json($result);
    }
}
