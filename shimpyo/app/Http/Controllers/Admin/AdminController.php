<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChargingSession;
use App\Models\Content;
use App\Models\Feedback;
use App\Models\PersonalWeight;
use App\Models\QuizTemplate;
use App\Models\SignalRule;
use App\Services\RecommendationEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /** 대시보드 지표 */
    public function stats()
    {
        $sessions = ChargingSession::count();
        $withPick = ChargingSession::whereNotNull('selected_mode')->count();
        $accepted = ChargingSession::whereNotNull('selected_mode')
            ->whereColumn('selected_mode', 'recommended_mode')->count();

        $byMode = ChargingSession::whereNotNull('selected_mode')
            ->select('selected_mode', DB::raw('count(*) as c'))
            ->groupBy('selected_mode')->pluck('c', 'selected_mode')->toArray();

        $recommended = ChargingSession::select('recommended_mode', DB::raw('count(*) as c'))
            ->groupBy('recommended_mode')->pluck('c', 'recommended_mode')->toArray();

        $fb = Feedback::select('rating', DB::raw('count(*) as c'))
            ->groupBy('rating')->pluck('c', 'rating')->toArray();

        $good = (int) ($fb[1] ?? 0);
        $bad  = (int) ($fb[-1] ?? 0);

        $byModeFb = Feedback::select('mode',
                DB::raw('sum(case when rating = 1 then 1 else 0 end) as good'),
                DB::raw('sum(case when rating = -1 then 1 else 0 end) as bad'))
            ->groupBy('mode')->get();

        $daily = ChargingSession::select(
                DB::raw('date(created_at) as d'),
                DB::raw('count(*) as c'))
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('d')->orderBy('d')->get();

        return response()->json([
            'sessions'        => $sessions,
            'selected'        => $withPick,
            'accept_rate'     => $withPick ? round($accepted / $withPick, 3) : null,
            'by_mode'         => $this->fill($byMode),
            'by_recommended'  => $this->fill($recommended),
            'feedback'        => ['good' => $good, 'bad' => $bad],
            'satisfaction'    => ($good + $bad) ? round($good / ($good + $bad), 3) : null,
            'feedback_by_mode'=> $byModeFb,
            'fallback_rate'   => $sessions
                ? round(ChargingSession::where('used_fallback', true)->count() / $sessions, 3) : 0,
            'daily'           => $daily,
            'mode_label'      => RecommendationEngine::MODE_LABEL,
        ]);
    }

    private function fill(array $counts): array
    {
        $out = [];
        foreach (RecommendationEngine::MODES as $m) {
            $out[$m] = (int) ($counts[$m] ?? 0);
        }

        return $out;
    }

    // ── 추천 규칙 CRUD

    public function rules()
    {
        return response()->json(SignalRule::orderBy('sort')->orderBy('id')->get());
    }

    public function storeRule(Request $request)
    {
        return response()->json(SignalRule::create($this->ruleData($request)), 201);
    }

    public function updateRule(Request $request, SignalRule $rule)
    {
        $rule->update($this->ruleData($request, false));

        return response()->json($rule);
    }

    public function destroyRule(SignalRule $rule)
    {
        $rule->delete();

        return response()->json(['ok' => true]);
    }

    private function ruleData(Request $request, bool $required = true): array
    {
        $r = $required ? 'required' : 'sometimes';

        return $request->validate([
            'signal_key'  => "$r|string|max:60",
            'label'       => "$r|string|max:255",
            'source'      => 'nullable|string|max:255',
            'operator'    => "$r|in:gte,lte,gt,lt,eq,between,wrap",
            'value_a'     => 'nullable|numeric',
            'value_b'     => 'nullable|numeric',
            'target_mode' => "$r|in:content,rest,check,play",
            'weight'      => "$r|integer|min:-10|max:10",
            'is_override' => 'boolean',
            'active'      => 'boolean',
            'sort'        => 'integer',
        ]);
    }

    // ── 세션 로그

    public function sessions(Request $request)
    {
        $q = ChargingSession::with('feedback')->latest();

        if ($mode = $request->query('mode')) {
            $q->where('recommended_mode', $mode);
        }

        return response()->json($q->paginate(20));
    }

    public function session(ChargingSession $session)
    {
        return response()->json($session->load('feedback', 'vehicle'));
    }

    // ── 콘텐츠 CRUD

    public function contents()
    {
        return response()->json(Content::orderBy('type')->orderBy('duration_sec')->get());
    }

    public function storeContent(Request $request)
    {
        return response()->json(Content::create($this->contentData($request)), 201);
    }

    public function updateContent(Request $request, Content $content)
    {
        $content->update($this->contentData($request, false));

        return response()->json($content);
    }

    public function destroyContent(Content $content)
    {
        $content->delete();

        return response()->json(['ok' => true]);
    }

    private function contentData(Request $request, bool $required = true): array
    {
        $r = $required ? 'required' : 'sometimes';

        return $request->validate([
            'type'         => "$r|in:podcast,music,video",
            'title'        => "$r|string|max:255",
            'creator'      => 'nullable|string|max:255',
            'duration_sec' => "$r|integer|min:30|max:14400",
            'category'     => 'nullable|string|max:40',
            'accent'       => 'nullable|string|max:20',
            'active'       => 'boolean',
        ]);
    }

    // ── 퀴즈 템플릿 CRUD

    public function quizTemplates()
    {
        return response()->json(QuizTemplate::orderBy('id')->get());
    }

    public function storeQuiz(Request $request)
    {
        return response()->json(QuizTemplate::create($this->quizData($request)), 201);
    }

    public function updateQuiz(Request $request, QuizTemplate $quizTemplate)
    {
        $quizTemplate->update($this->quizData($request, false));

        return response()->json($quizTemplate);
    }

    public function destroyQuiz(QuizTemplate $quizTemplate)
    {
        $quizTemplate->delete();

        return response()->json(['ok' => true]);
    }

    private function quizData(Request $request, bool $required = true): array
    {
        $r = $required ? 'required' : 'sometimes';

        return $request->validate([
            'question' => "$r|string|max:255",
            'data_key' => "$r|in:distance_km,driving_time,avg_speed,passengers,soc,charge_minutes,charging_power",
            'unit'     => 'nullable|string|max:20',
            'spread'   => 'integer|min:5|max:60',
            'active'   => 'boolean',
        ]);
    }

    // ── 개인화 가중치

    public function personalWeights()
    {
        return response()->json(
            PersonalWeight::orderBy('situation_key')->orderBy('mode')->get()
        );
    }

    public function resetPersonal()
    {
        PersonalWeight::query()->delete();

        return response()->json(['ok' => true]);
    }

    /** 데모 데이터 초기화 (세션·피드백·개인화만 삭제, 규칙/콘텐츠는 유지) */
    public function resetDemo()
    {
        Feedback::query()->delete();
        ChargingSession::query()->delete();
        PersonalWeight::query()->delete();

        return response()->json(['ok' => true]);
    }
}
