<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $fillable = ['school_class_id', 'subject_id', 'teacher_id', 'assessment_id', 'title', 'instructions', 'due_at', 'maximum_score', 'is_published'];
    protected $casts = ['due_at' => 'datetime', 'maximum_score' => 'decimal:2', 'is_published' => 'boolean'];
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(Teacher::class); }
    public function assessment(): BelongsTo { return $this->belongsTo(Assessment::class); }
    public function submissions(): HasMany { return $this->hasMany(AssignmentSubmission::class); }
}
