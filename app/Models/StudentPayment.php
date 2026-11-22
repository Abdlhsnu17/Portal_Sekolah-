<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StudentPayment extends Model { protected $fillable=['student_invoice_id','amount','paid_on','method','reference','recorded_by']; protected $casts=['amount'=>'decimal:2','paid_on'=>'date']; public function invoice(): BelongsTo { return $this->belongsTo(StudentInvoice::class,'student_invoice_id'); } }
