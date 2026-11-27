<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePaymentRequest extends Model
{
    public const STATUSES = ['pending' => 'Menunggu pembayaran', 'paid' => 'Dibayar', 'expired' => 'Kedaluwarsa', 'failed' => 'Gagal'];
    protected $fillable = ['student_invoice_id', 'provider', 'external_reference', 'amount', 'status', 'payment_url', 'payload', 'expires_at', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'payload' => 'array', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];
    public function invoice(): BelongsTo { return $this->belongsTo(StudentInvoice::class, 'student_invoice_id'); }
}
