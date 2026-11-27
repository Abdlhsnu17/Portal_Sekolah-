<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    protected $fillable = ['assignment_id', 'student_id', 'answer', 'attachment_path', 'submitted_at', 'score', 'feedback', 'reviewed_by', 'reviewed_at'];
    protected $casts = ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'score' => 'decimal:2'];
    public function assignment(): BelongsTo { return $this->belongsTo(Assignment::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
