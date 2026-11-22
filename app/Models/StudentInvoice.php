<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class StudentInvoice extends Model {
    public const STATUSES=['belum_lunas'=>'Belum lunas','sebagian'=>'Sebagian','lunas'=>'Lunas'];
    protected $fillable=['student_id','title','amount','due_date','status']; protected $casts=['amount'=>'decimal:2','due_date'=>'date'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function payments(): HasMany { return $this->hasMany(StudentPayment::class); }
    public function refreshStatus(): void { $paid=(float)$this->payments()->sum('amount'); $this->update(['status'=>$paid >= (float)$this->amount ? 'lunas' : ($paid > 0 ? 'sebagian' : 'belum_lunas')]); }
}
