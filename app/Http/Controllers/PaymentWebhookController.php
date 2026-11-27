<?php

namespace App\Http\Controllers;

use App\Models\InvoicePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.payment_webhook.secret');
        abort_if(blank($secret), 503, 'Webhook pembayaran belum dikonfigurasi.');
        $signature = (string) $request->header('X-Payment-Signature');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        abort_unless(filled($signature) && hash_equals($expected, $signature), 401, 'Signature tidak valid.');

        $data = $request->validate(['external_reference' => ['required', 'string', 'max:120'], 'status' => ['required', 'in:paid,failed,expired'], 'amount' => ['nullable', 'numeric', 'min:0'], 'paid_at' => ['nullable', 'date']]);
        DB::transaction(function () use ($data) {
            $payment = InvoicePaymentRequest::query()->where('external_reference', $data['external_reference'])->lockForUpdate()->firstOrFail();
            if ($payment->status === 'paid') return;
            $payment->update(['status' => $data['status'], 'paid_at' => $data['status'] === 'paid' ? ($data['paid_at'] ?? now()) : null]);
            if ($data['status'] !== 'paid') return;
            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
            $alreadyPaid = (float) $invoice->payments()->sum('amount');
            $amount = min((float) ($data['amount'] ?? $payment->amount), max(0, (float) $invoice->amount - $alreadyPaid));
            if ($amount > 0) $invoice->payments()->create(['amount' => $amount, 'paid_on' => now()->toDateString(), 'method' => $payment->provider, 'reference' => $payment->external_reference]);
            $invoice->refreshStatus();
        });
        return response()->json(['ok' => true]);
    }
}
