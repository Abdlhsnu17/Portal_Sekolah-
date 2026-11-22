<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class LibraryBook extends Model { protected $fillable=['code','title','author','publisher','stock']; public function loans():HasMany{return $this->hasMany(LibraryLoan::class);} public function getAvailableStockAttribute():int{return max(0,$this->stock-$this->loans()->whereNull('returned_on')->count());} }
