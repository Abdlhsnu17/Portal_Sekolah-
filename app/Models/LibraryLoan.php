<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LibraryLoan extends Model { protected $fillable=['library_book_id','student_id','borrowed_on','due_on','returned_on','fine']; protected $casts=['borrowed_on'=>'date','due_on'=>'date','returned_on'=>'date','fine'=>'decimal:2']; public function book():BelongsTo{return $this->belongsTo(LibraryBook::class,'library_book_id');} public function student():BelongsTo{return $this->belongsTo(Student::class);} }
