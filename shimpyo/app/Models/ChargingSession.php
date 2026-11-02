<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ChargingSession extends Model {
    protected $fillable = ['vehicle_id','scenario','signals','scores','breakdown','recommended_mode','reason',
        'selected_mode','situation_key','charge_duration_sec','start_soc','end_soc','used_fallback','ended_at'];
    protected $casts = ['signals'=>'array','scores'=>'array','breakdown'=>'array','used_fallback'=>'boolean','ended_at'=>'datetime'];
    public function vehicle(){ return $this->belongsTo(Vehicle::class); }
    public function feedback(){ return $this->hasOne(Feedback::class); }
}
