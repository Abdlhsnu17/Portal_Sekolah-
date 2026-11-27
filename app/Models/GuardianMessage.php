<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianMessage extends Model
{
    protected $fillable = ['student_id', 'sender_id', 'recipient_id', 'subject', 'body', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }
}
