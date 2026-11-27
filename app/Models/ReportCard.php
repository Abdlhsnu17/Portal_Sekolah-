<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    protected $fillable = ['student_id', 'academic_year_id', 'semester', 'school_class_id', 'homeroom_note', 'published_at', 'locked_at'];
    protected $casts = ['published_at' => 'datetime', 'locked_at' => 'datetime'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function items(): HasMany { return $this->hasMany(ReportCardItem::class); }
}
