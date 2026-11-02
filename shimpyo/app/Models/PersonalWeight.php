<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PersonalWeight extends Model {
    protected $fillable = ['profile','situation_key','mode','delta'];
    protected $casts = ['delta'=>'integer'];
}
