<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdmissionPath extends Model { protected $fillable = ['name','quota','is_active']; protected $casts = ['is_active'=>'boolean']; }
