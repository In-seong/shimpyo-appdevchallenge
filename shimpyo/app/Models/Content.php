<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Content extends Model {
    protected $fillable = ['type','title','creator','duration_sec','category','accent','active'];
    protected $casts = ['active'=>'boolean','duration_sec'=>'integer'];
}
