<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')],
        ]);
        $years = AcademicYear::orderByDesc('starts_on')->get();
        $selectedYearId = $filters['academic_year_id'] ?? $years->firstWhere('is_active', true)?->id ?? $years->first()?->id;
        $classes = SchoolClass::with('academicYear')
            ->when($selectedYearId, fn ($query) => $query->where('academic_year_id', $selectedYearId))
            ->orderBy('name')->get();
        $selectedClassId = $filters['school_class_id'] ?? null;
        if ($selectedClassId && ! $classes->contains('id', (int) $selectedClassId)) {
            abort(404);
        }

        $studentScope = Student::where('status', 'aktif')
            ->when($selectedYearId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('status', 'aktif')->whereHas('schoolClass', fn ($class) => $class->where('academic_year_id', $selectedYearId))))
            ->when($selectedClassId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('school_class_id', $selectedClassId)->where('status', 'aktif')));

        return view('admin.finance.index', [
            'invoices' => StudentInvoice::with(['student', 'payments'])->withSum('payments', 'amount')
                ->whereIn('student_id', (clone $studentScope)->select('id'))->latest()->get(),
            'students' => $studentScope->orderBy('nama')->get(),
            'years' => $years,
            'classes' => $classes,
            'selectedYearId' => $selectedYearId,
            'selectedClassId' => $selectedClassId,
        ]);
    }

    public function storeInvoice(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('status', 'aktif')],
            'category' => ['required', Rule::in(array_keys(StudentInvoice::CATEGORIES))],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'due_date' => ['nullable', 'date'],
        ]);

        StudentInvoice::create($data);

        return redirect()->to(route('admin.finance.index', $this->indexFilters($request)).'#invoices')
            ->with('success', 'Tagihan dibuat.');
    }

    public function editInvoice(Request $request, StudentInvoice $invoice)
    {
        $invoice->load(['student', 'payments']);

        return view('admin.finance.invoice-form', [
            'invoice' => $invoice,
            'paidAmount' => $invoice->payments->sum('amount'),
            'filters' => $this->indexFilters($request),
        ]);
    }

    public function updateInvoice(Request $request, StudentInvoice $invoice)
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(StudentInvoice::CATEGORIES))],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'due_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $lockedInvoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $paidAmount = (float) $lockedInvoice->payments()->sum('amount');

            if ((float) $data['amount'] < $paidAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal tagihan tidak boleh lebih kecil daripada total pembayaran yang sudah diterima.',
                ]);
            }

            $lockedInvoice->update($data);
            $lockedInvoice->refreshStatus();
        });

        return redirect()->route('admin.finance.index', $this->indexFilters($request))
            ->with('success', 'Tagihan diperbarui.');
    }

    public function destroyInvoice(Request $request, StudentInvoice $invoice)
    {
        $data = $request->validate([
            'deletion_reason' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($invoice, $request, $data) {
            $lockedInvoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $payments = $lockedInvoice->payments()->get();

            DB::table('finance_deletion_logs')->insert([
                'invoice_id' => $lockedInvoice->id,
                'student_id' => $lockedInvoice->student_id,
                'invoice_title' => $lockedInvoice->title,
                'invoice_amount' => $lockedInvoice->amount,
                'paid_amount' => $payments->sum('amount'),
                'payment_count' => $payments->count(),
                'reason' => $data['deletion_reason'] ?? 'Dihapus oleh admin.',
                'deleted_by' => $request->user()->id,
                'deleted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lockedInvoice->delete();
        });

        return redirect()->to(route('admin.finance.index', $this->indexFilters($request)).'#invoices')
            ->with('success', 'Tagihan dan pembayaran terkait dihapus. Alasan tersimpan pada audit.');
    }

    public function storePayment(Request $request, StudentInvoice $invoice)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($invoice, $data, $request) {
            $lockedInvoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $remaining = max(0, (float) $lockedInvoice->amount - (float) $lockedInvoice->payments()->sum('amount'));

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'invoice' => 'Tagihan ini sudah lunas sehingga tidak dapat menerima pembayaran baru.',
                ]);
            }

            if ((float) $data['amount'] > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa tagihan.',
                ]);
            }

            $lockedInvoice->payments()->create($data + ['recorded_by' => $request->user()->id]);
            $lockedInvoice->refreshStatus();
        });

        return redirect()->route('admin.finance.index', $this->indexFilters($request))
            ->with('success', 'Pembayaran dicatat.');
    }

    public function createPayment(Request $request, StudentInvoice $invoice)
    {
        $invoice->load(['student', 'payments']);
        $remaining = max(0, (float) $invoice->amount - (float) $invoice->payments->sum('amount'));

        if ($remaining <= 0) {
            return redirect()->route('admin.finance.index', $this->indexFilters($request))
                ->with('success', 'Tagihan ini sudah lunas. Tidak perlu mencatat pembayaran lagi.');
        }

        return view('admin.finance.payment-create', [
            'invoice' => $invoice,
            'remaining' => $remaining,
            'filters' => $this->indexFilters($request),
        ]);
    }

    public function editPayment(Request $request, StudentInvoice $invoice, StudentPayment $payment)
    {
        abort_unless((int) $payment->student_invoice_id === (int) $invoice->id, 404);
        $otherPayments = (float) $invoice->payments()->whereKeyNot($payment->id)->sum('amount');

        return view('admin.finance.payment-form', [
            'invoice' => $invoice->load('student'),
            'payment' => $payment,
            'maximumAmount' => max(0, (float) $invoice->amount - $otherPayments),
            'filters' => $this->indexFilters($request),
        ]);
    }

    public function updatePayment(Request $request, StudentInvoice $invoice, StudentPayment $payment)
    {
        abort_unless((int) $payment->student_invoice_id === (int) $invoice->id, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_on' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($invoice, $payment, $data) {
            $lockedInvoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $lockedPayment = $lockedInvoice->payments()->findOrFail($payment->id);
            $otherPayments = (float) $lockedInvoice->payments()
                ->whereKeyNot($lockedPayment->id)->sum('amount');
            $maximum = max(0, (float) $lockedInvoice->amount - $otherPayments);

            if ((float) $data['amount'] > $maximum) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal pembayaran melebihi sisa tagihan setelah memperhitungkan pembayaran lainnya.',
                ]);
            }

            $lockedPayment->update($data);
            $lockedInvoice->refreshStatus();
        });

        return redirect()->route('admin.finance.index', $this->indexFilters($request))
            ->with('success', 'Pembayaran diperbarui.');
    }

    public function destroyPayment(Request $request, StudentInvoice $invoice, StudentPayment $payment)
    {
        abort_unless((int) $payment->student_invoice_id === (int) $invoice->id, 404);

        DB::transaction(function () use ($invoice, $payment) {
            $lockedInvoice = StudentInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $lockedPayment = $lockedInvoice->payments()->findOrFail($payment->id);
            $lockedPayment->delete();
            $lockedInvoice->refreshStatus();
        });

        return redirect()->route('admin.finance.index', $this->indexFilters($request))
            ->with('success', 'Pembayaran dihapus dan status tagihan diperbarui.');
    }

    private function indexFilters(Request $request): array
    {
        return array_filter([
            'academic_year_id' => $request->integer('academic_year_id') ?: null,
            'school_class_id' => $request->integer('school_class_id') ?: null,
        ]);
    }
}
