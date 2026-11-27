<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoan extends Model
{
    protected $fillable = ['school_asset_id', 'borrower_id', 'borrower_name', 'quantity', 'borrowed_on', 'due_on', 'returned_on', 'note'];
    protected $casts = ['borrowed_on' => 'date', 'due_on' => 'date', 'returned_on' => 'date'];
    public function asset(): BelongsTo { return $this->belongsTo(SchoolAsset::class, 'school_asset_id'); }
    public function borrower(): BelongsTo { return $this->belongsTo(User::class, 'borrower_id'); }
}
