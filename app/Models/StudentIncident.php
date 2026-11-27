<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIncident extends Model
{
    public const TYPES = ['achievement' => 'Prestasi', 'violation' => 'Pelanggaran', 'counselling' => 'Konseling'];
    protected $fillable = ['student_id', 'recorded_by', 'type', 'title', 'description', 'points', 'occurred_on', 'follow_up_status', 'follow_up_note'];
    protected $casts = ['occurred_on' => 'date'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
