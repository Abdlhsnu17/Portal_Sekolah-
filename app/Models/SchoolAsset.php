<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class SchoolAsset extends Model { public const CONDITIONS=['baik'=>'Baik','perlu_perbaikan'=>'Perlu perbaikan','rusak'=>'Rusak']; public const STATUSES=['tersedia'=>'Tersedia','dipinjam'=>'Dipinjam','perawatan'=>'Perawatan','dihapuskan'=>'Dihapuskan']; protected $fillable=['code','name','category','location','quantity','condition','status','acquired_on','acquisition_value','note']; protected $casts=['acquired_on'=>'date','acquisition_value'=>'decimal:2']; }
