<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Feedback extends Model {
    protected $table = 'feedbacks';
    protected $fillable = ['charging_session_id','mode','situation_key','rating','via'];
    protected $casts = ['rating'=>'integer'];
    public function session(){ return $this->belongsTo(ChargingSession::class,'charging_session_id'); }
}
