<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    protected $fillable = ['school_class_id', 'teacher_id', 'subject_id', 'day_of_week', 'starts_at', 'ends_at', 'room'];

    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }

    public function getDayLabelAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '—';
    }
}
