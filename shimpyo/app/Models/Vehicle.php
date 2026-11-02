<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Vehicle extends Model {
    protected $fillable = ['name','plate','battery_capacity_kwh'];
    public function sessions(){ return $this->hasMany(ChargingSession::class); }
}
