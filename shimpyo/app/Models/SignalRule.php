<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SignalRule extends Model {
    protected $fillable = ['signal_key','label','source','operator','value_a','value_b','target_mode','weight','is_override','active','sort'];
    protected $casts = ['is_override'=>'boolean','active'=>'boolean','value_a'=>'float','value_b'=>'float','weight'=>'integer'];

    /** 신호값이 이 규칙의 조건을 만족하는가 */
    public function matches(mixed $v): bool
    {
        if ($v === null) return false;
        $v = is_bool($v) ? ($v ? 1 : 0) : (float) $v;
        return match ($this->operator) {
            'gte'     => $v >= $this->value_a,
            'lte'     => $v <= $this->value_a,
            'gt'      => $v >  $this->value_a,
            'lt'      => $v <  $this->value_a,
            'eq'      => abs($v - $this->value_a) < 0.001,
            'between' => $v >= $this->value_a && $v <= $this->value_b,
            // 시각 전용: 22시~06시처럼 자정을 넘는 구간
            'wrap'    => $this->value_a > $this->value_b
                            ? ($v >= $this->value_a || $v <= $this->value_b)
                            : ($v >= $this->value_a && $v <= $this->value_b),
            default   => false,
        };
    }
}
