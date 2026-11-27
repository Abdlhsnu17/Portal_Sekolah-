<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardItem extends Model
{
    protected $fillable = ['report_card_id', 'subject_id', 'score', 'minimum_score', 'predicate', 'description'];
    protected $casts = ['score' => 'decimal:2', 'minimum_score' => 'decimal:2'];
    public function reportCard(): BelongsTo { return $this->belongsTo(ReportCard::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
