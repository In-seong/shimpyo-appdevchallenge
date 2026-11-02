<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 데모 차량
        Schema::create('vehicles', function (Blueprint $t) {
            $t->id();
            $t->string('name');               // 아이오닉 5
            $t->string('plate')->nullable();  // 12가 3456
            $t->unsignedInteger('battery_capacity_kwh')->default(77);
            $t->timestamps();
        });

        // 추천 엔진 규칙 (관리자가 편집)
        Schema::create('signal_rules', function (Blueprint $t) {
            $t->id();
            $t->string('signal_key', 60);            // driving_time, clock, occupancy, warning, soc_at_dest, charge_duration
            $t->string('label');                     // 화면 표시용
            $t->string('source')->nullable();        // 대응되는 Pleos API
            $t->string('operator', 10);              // gte, lte, between, eq
            $t->decimal('value_a', 10, 2)->nullable();
            $t->decimal('value_b', 10, 2)->nullable();
            $t->string('target_mode', 20);           // content, rest, check, play
            $t->integer('weight');                   // +3, -2 ...
            $t->boolean('is_override')->default(false); // 최우선(점검 +4) 여부
            $t->boolean('active')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        // 충전 세션 1건 = 쉼표가 열린 1회
        Schema::create('charging_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $t->string('scenario', 40)->nullable();   // holiday, weekend, business, custom
            $t->json('signals');                      // 입력 신호 스냅샷
            $t->json('scores');                       // 모드별 점수
            $t->json('breakdown')->nullable();        // 어떤 규칙이 몇 점 줬는지
            $t->string('recommended_mode', 20);
            $t->string('reason')->nullable();
            $t->string('selected_mode', 20)->nullable();
            $t->string('situation_key', 60);          // night|passenger|long 조합
            $t->unsignedInteger('charge_duration_sec');
            $t->unsignedTinyInteger('start_soc');
            $t->unsignedTinyInteger('end_soc');
            $t->boolean('used_fallback')->default(false); // ChargingPlan 없이 SoC 외삽했는지
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
        });

        // 출발 직후 1회 피드백
        Schema::create('feedbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('charging_session_id')->constrained()->cascadeOnDelete();
            $t->string('mode', 20);
            $t->string('situation_key', 60);
            $t->tinyInteger('rating');            // +1 / -1
            $t->string('via', 10)->default('touch'); // touch | voice
            $t->timestamps();
        });

        // 상황조합별 누적 개인화 가중치
        Schema::create('personal_weights', function (Blueprint $t) {
            $t->id();
            $t->string('profile', 40)->default('demo');
            $t->string('situation_key', 60);
            $t->string('mode', 20);
            $t->integer('delta')->default(0);
            $t->timestamps();
            $t->unique(['profile', 'situation_key', 'mode']);
        });

        // 콘텐츠 모드 재생목록 풀
        Schema::create('contents', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);              // podcast | music | video
            $t->string('title');
            $t->string('creator')->nullable();
            $t->unsignedInteger('duration_sec');
            $t->string('category', 40)->nullable();
            $t->string('accent', 20)->nullable(); // 카드 색상 키
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        // 놀이 모드 문제 템플릿
        Schema::create('quiz_templates', function (Blueprint $t) {
            $t->id();
            $t->string('question');              // "오늘 우리가 달린 거리는 몇 km일까요?"
            $t->string('data_key', 40);          // distance_km, avg_speed, waypoints, driving_time
            $t->string('unit', 20)->nullable();
            $t->unsignedTinyInteger('spread')->default(20); // 오답 생성 폭 (%)
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_templates');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('personal_weights');
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('charging_sessions');
        Schema::dropIfExists('signal_rules');
        Schema::dropIfExists('vehicles');
    }
};
