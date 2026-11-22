<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdmissionPeriod extends Model { protected $fillable = ['name','starts_on','ends_on','quota','is_active']; protected $casts = ['starts_on'=>'date','ends_on'=>'date','is_active'=>'boolean']; }
