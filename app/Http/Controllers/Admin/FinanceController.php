<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceController extends Controller {
    public function index(Request $request) {
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
        if ($selectedClassId && ! $classes->contains('id', (int) $selectedClassId)) abort(404);

        $studentScope = Student::where('status', 'aktif')
            ->when($selectedYearId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('status', 'aktif')->whereHas('schoolClass', fn ($class) => $class->where('academic_year_id', $selectedYearId))))
            ->when($selectedClassId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('school_class_id', $selectedClassId)->where('status', 'aktif')));

        return view('admin.finance.index', [
            'invoices' => StudentInvoice::with('student')->withSum('payments', 'amount')
                ->whereIn('student_id', (clone $studentScope)->select('id'))->latest()->get(),
            'students' => $studentScope->orderBy('nama')->get(),
            'years' => $years,
            'classes' => $classes,
            'selectedYearId' => $selectedYearId,
            'selectedClassId' => $selectedClassId,
        ]);
    }
    public function storeInvoice(Request $request) { $data=$request->validate(['student_id'=>['required',Rule::exists('students','id')],'title'=>['required','string','max:150'],'amount'=>['required','numeric','min:1'],'due_date'=>['nullable','date']]); StudentInvoice::create($data); return back()->with('success','Tagihan dibuat.'); }
    public function storePayment(Request $request, StudentInvoice $invoice) { $data=$request->validate(['amount'=>['required','numeric','min:0.01','max:'.max(0, (float)$invoice->amount-(float)$invoice->payments()->sum('amount'))],'paid_on'=>['required','date'],'method'=>['nullable','string','max:50'],'reference'=>['nullable','string','max:100']]); DB::transaction(function() use($invoice,$data) {$invoice->payments()->create([...$data,'recorded_by'=>auth()->id()]); $invoice->refreshStatus();}); return back()->with('success','Pembayaran dicatat.'); }
}
