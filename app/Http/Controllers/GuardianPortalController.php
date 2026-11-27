<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use App\Models\StudentAttendance;
use App\Models\StudentInvoice;
use App\Models\StudentScore;
use App\Models\ReportCard;
use App\Models\InvoicePaymentRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GuardianPortalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isGuardian(), 403);

        $students = $request->user()->students()->with('enrollments.schoolClass')->get();
        $data = $students->map(fn ($student) => [
            'student' => $student,
            'attendance' => StudentAttendance::where('student_id', $student->id)
                ->selectRaw('status, count(*) total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'invoices' => StudentInvoice::where('student_id', $student->id)
                ->withSum('payments', 'amount')
                ->latest()
                ->get(),
            'notes' => $student->notes()
                ->whereNotNull('published_at')
                ->latest('published_at')
                ->limit(1)
                ->get(),
            'scores' => StudentScore::where('student_id', $student->id)
                ->with('assessment.subject')
                ->get()
                ->groupBy(fn ($score) => $score->assessment->subject->nama)
                ->map(fn ($scores) => round($scores->avg('score'), 2)),
            'reports' => ReportCard::where('student_id', $student->id)->whereNotNull('published_at')->with(['academicYear', 'items.subject'])->latest('published_at')->get(),
        ]);

        return view('guardian.index', [
            'data' => $data,
            'profil' => SchoolProfile::current(),
        ]);
    }

    public function notes(Request $request)
    {
        abort_unless($request->user()->isGuardian(), 403);

        $students = $request->user()->students()->with('enrollments.schoolClass')->get();
        $data = $students->map(fn ($student) => [
            'student' => $student,
            'notes' => $student->notes()
                ->whereNotNull('published_at')
                ->with('teacher')
                ->latest('published_at')
                ->get(),
        ]);

        return view('guardian.notes', [
            'data' => $data,
            'profil' => SchoolProfile::current(),
        ]);
    }

    public function requestPayment(Request $request, StudentInvoice $invoice)
    {
        abort_unless($request->user()->isGuardian() && $request->user()->students()->whereKey($invoice->student_id)->exists(), 403);
        $invoice->loadSum('payments', 'amount');
        $remaining = max(0, (float) $invoice->amount - (float) ($invoice->payments_sum_amount ?? 0));
        abort_if($remaining <= 0, 422, 'Tagihan ini sudah lunas.');
        $payment = InvoicePaymentRequest::create(['student_invoice_id' => $invoice->id, 'provider' => 'manual', 'external_reference' => 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'amount' => $remaining, 'status' => 'pending', 'expires_at' => now()->addDay()]);
        return back()->with('success', 'Permintaan pembayaran dibuat. Referensi: '.$payment->external_reference.'. Silakan hubungi bagian keuangan atau lanjutkan setelah gateway diaktifkan.');
    }
}
