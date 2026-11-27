<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    protected $fillable = ['school_asset_id', 'performed_on', 'type', 'cost', 'note'];
    protected $casts = ['performed_on' => 'date', 'cost' => 'decimal:2'];
    public function asset(): BelongsTo { return $this->belongsTo(SchoolAsset::class, 'school_asset_id'); }
}
