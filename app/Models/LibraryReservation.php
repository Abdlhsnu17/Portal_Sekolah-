<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryReservation extends Model
{
    protected $fillable = ['library_book_id', 'student_id', 'status', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
    public function book(): BelongsTo { return $this->belongsTo(LibraryBook::class, 'library_book_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
