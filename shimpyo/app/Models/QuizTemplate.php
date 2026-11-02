<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class QuizTemplate extends Model {
    protected $fillable = ['question','data_key','unit','spread','active'];
    protected $casts = ['active'=>'boolean','spread'=>'integer'];
}
