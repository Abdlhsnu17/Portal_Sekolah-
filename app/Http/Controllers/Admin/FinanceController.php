<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\DeleteInvoiceRequest;
use App\Http\Requests\Admin\Finance\FinanceIndexRequest;
use App\Http\Requests\Admin\Finance\SearchStudentsRequest;
use App\Http\Requests\Admin\Finance\StoreInvoiceRequest;
use App\Http\Requests\Admin\Finance\StorePaymentRequest;
use App\Http\Requests\Admin\Finance\UpdateInvoiceRequest;
use App\Http\Requests\Admin\Finance\UpdatePaymentRequest;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Services\Admin\Finance\FinanceDashboardService;
use App\Services\Admin\Finance\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceService $finance,
        private readonly FinanceDashboardService $dashboard,
    ) {}

    public function index(FinanceIndexRequest $request): View
    {
        return view('admin.finance.index', $this->dashboard->data($request->validated()));
    }

    public function searchStudents(SearchStudentsRequest $request): JsonResponse
    {
        $students = $this->dashboard->searchStudents($request->validated());

        return response()->json([
            'data' => $students->getCollection()->map(fn ($student) => [
                'id' => (string) $student->id,
                'label' => "{$student->nis} — {$student->nama}",
            ])->values(),
            'meta' => ['has_more' => $students->hasMorePages()],
        ]);
    }

    public function storeInvoice(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->finance->createInvoice($request->validated());

        return $this->redirectToIndex($request, 'Tagihan dibuat.', '#invoices');
    }

    public function editInvoice(Request $request, StudentInvoice $invoice): View
    {
        $invoice->load(['student', 'payments']);

        return view('admin.finance.invoice-form', [
            'invoice' => $invoice,
            'paidAmount' => $invoice->payments->sum('amount'),
            'filters' => $this->indexFilters($request),
            'categories' => StudentInvoice::CATEGORIES,
        ]);
    }

    public function updateInvoice(UpdateInvoiceRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        $this->finance->updateInvoice($invoice, $request->validated());

        return $this->redirectToIndex($request, 'Tagihan diperbarui.');
    }

    public function destroyInvoice(DeleteInvoiceRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        $this->finance->deleteInvoice($invoice, $request->user(), $request->validated('deletion_reason'));

        return $this->redirectToIndex($request, 'Tagihan dan pembayaran terkait dihapus. Alasan tersimpan pada audit.', '#invoices');
    }

    public function createPayment(Request $request, StudentInvoice $invoice): View|RedirectResponse
    {
        $invoice->load(['student', 'payments']);
        $remaining = $this->finance->remainingBalance($invoice);

        if ($remaining <= 0) {
            return $this->redirectToIndex($request, 'Tagihan ini sudah lunas. Tidak perlu mencatat pembayaran lagi.');
        }

        return view('admin.finance.payment-create', compact('invoice', 'remaining') + ['filters' => $this->indexFilters($request)]);
    }

    public function storePayment(StorePaymentRequest $request, StudentInvoice $invoice): RedirectResponse
    {
        $this->finance->createPayment($invoice, $request->validated(), $request->user());

        return $this->redirectToIndex($request, 'Pembayaran dicatat.');
    }

    public function editPayment(Request $request, StudentInvoice $invoice, StudentPayment $payment): View
    {
        $this->finance->ensurePaymentBelongsToInvoice($invoice, $payment);
        $otherPayments = (float) $invoice->payments()->whereKeyNot($payment->id)->sum('amount');

        return view('admin.finance.payment-form', [
            'invoice' => $invoice->load('student'),
            'payment' => $payment,
            'maximumAmount' => max(0, (float) $invoice->amount - $otherPayments),
            'filters' => $this->indexFilters($request),
        ]);
    }

    public function updatePayment(UpdatePaymentRequest $request, StudentInvoice $invoice, StudentPayment $payment): RedirectResponse
    {
        $this->finance->updatePayment($invoice, $payment, $request->validated());

        return $this->redirectToIndex($request, 'Pembayaran diperbarui.');
    }

    public function destroyPayment(Request $request, StudentInvoice $invoice, StudentPayment $payment): RedirectResponse
    {
        $this->finance->deletePayment($invoice, $payment);

        return $this->redirectToIndex($request, 'Pembayaran dihapus dan status tagihan diperbarui.');
    }

    private function redirectToIndex(Request $request, string $message, string $fragment = ''): RedirectResponse
    {
        return redirect()->to(route('admin.finance.index', $this->indexFilters($request)).$fragment)->with('success', $message);
    }

    private function indexFilters(Request $request): array
    {
        return array_filter($request->only(['academic_year_id', 'school_class_id']));
    }
}
