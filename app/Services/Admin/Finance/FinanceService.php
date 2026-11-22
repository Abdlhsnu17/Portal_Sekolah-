<?php

namespace App\Services\Admin\Finance;

use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function createInvoice(array $attributes): StudentInvoice
    {
        return StudentInvoice::create($attributes);
    }

    public function updateInvoice(StudentInvoice $invoice, array $attributes): void
    {
        DB::transaction(function () use ($invoice, $attributes): void {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $paidAmount = (float) $invoice->payments()->sum('amount');

            if ((float) $attributes['amount'] < $paidAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal tagihan tidak boleh lebih kecil daripada total pembayaran yang sudah diterima.',
                ]);
            }

            $invoice->update($attributes);
            $invoice->refreshStatus();
        });
    }

    public function deleteInvoice(StudentInvoice $invoice, User $actor, ?string $reason): void
    {
        DB::transaction(function () use ($invoice, $actor, $reason): void {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payments = $invoice->payments()->get();

            DB::table('finance_deletion_logs')->insert([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'invoice_title' => $invoice->title,
                'invoice_amount' => $invoice->amount,
                'paid_amount' => $payments->sum('amount'),
                'payment_count' => $payments->count(),
                'reason' => $reason ?: 'Dihapus oleh admin.',
                'deleted_by' => $actor->id,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $invoice->delete();
        });
    }

    public function createPayment(StudentInvoice $invoice, array $attributes, User $actor): void
    {
        DB::transaction(function () use ($invoice, $attributes, $actor): void {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $remaining = $this->remainingBalance($invoice);

            if ($remaining <= 0) {
                throw ValidationException::withMessages(['invoice' => 'Tagihan ini sudah lunas sehingga tidak dapat menerima pembayaran baru.']);
            }

            if ((float) $attributes['amount'] > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran melebihi sisa tagihan.']);
            }

            $invoice->payments()->create($attributes + ['recorded_by' => $actor->id]);
            $invoice->refreshStatus();
        });
    }

    public function updatePayment(StudentInvoice $invoice, StudentPayment $payment, array $attributes): void
    {
        $this->ensurePaymentBelongsToInvoice($invoice, $payment);

        DB::transaction(function () use ($invoice, $payment, $attributes): void {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payment = $invoice->payments()->findOrFail($payment->id);
            $maximum = max(0, (float) $invoice->amount - (float) $invoice->payments()
                ->whereKeyNot($payment->id)->sum('amount'));

            if ((float) $attributes['amount'] > $maximum) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran melebihi sisa tagihan setelah memperhitungkan pembayaran lainnya.']);
            }

            $payment->update($attributes);
            $invoice->refreshStatus();
        });
    }

    public function deletePayment(StudentInvoice $invoice, StudentPayment $payment): void
    {
        $this->ensurePaymentBelongsToInvoice($invoice, $payment);

        DB::transaction(function () use ($invoice, $payment): void {
            $invoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $invoice->payments()->findOrFail($payment->id)->delete();
            $invoice->refreshStatus();
        });
    }

    public function remainingBalance(StudentInvoice $invoice): float
    {
        return max(0, (float) $invoice->amount - (float) $invoice->payments()->sum('amount'));
    }

    public function ensurePaymentBelongsToInvoice(StudentInvoice $invoice, StudentPayment $payment): void
    {
        abort_unless($payment->student_invoice_id === $invoice->id, 404);
    }
}
