<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    public const TYPES = ['tugas' => 'Tugas', 'kuis' => 'Kuis', 'pts' => 'PTS', 'pas' => 'PAS', 'praktik' => 'Praktik'];
    protected $fillable = ['school_class_id', 'teacher_id', 'subject_id', 'title', 'type', 'maximum_score', 'assessment_date'];
    protected $casts = ['assessment_date' => 'date', 'maximum_score' => 'decimal:2'];
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function scores(): HasMany { return $this->hasMany(StudentScore::class); }
}
