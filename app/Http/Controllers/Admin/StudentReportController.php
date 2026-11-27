<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
use App\Models\AcademicYear;
use App\Models\ReportCard;
use App\Models\Subject;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;

class StudentReportController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::where('status', 'aktif')->orderBy('nama')->get();
        $student = $request->integer('student_id') ? Student::with(['enrollments.schoolClass.academicYear'])->findOrFail($request->integer('student_id')) : null;
        $scoresBySubject = collect();
        $attendance = collect();

        if ($student) {
            $scoresBySubject = StudentScore::where('student_id', $student->id)
                ->with('assessment.subject')
                ->get()
                ->groupBy(fn ($score) => $score->assessment->subject->nama)
                ->map(fn ($scores) => [
                    'count' => $scores->whereNotNull('score')->count(),
                    'average' => round((float) $scores->whereNotNull('score')->avg('score'), 2),
                ])->sortKeys();

            $attendance = StudentAttendance::where('student_id', $student->id)
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        }

        return view('admin.student-reports.index', ['students' => $students, 'student' => $student, 'scoresBySubject' => $scoresBySubject, 'attendance' => $attendance, 'years' => AcademicYear::orderByDesc('starts_on')->get(), 'reportCards' => $student ? $student->reportCards()->with(['academicYear', 'items.subject'])->latest()->get() : collect()]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'academic_year_id' => ['required', 'exists:academic_years,id'], 'semester' => ['required', 'in:ganjil,genap'], 'minimum_score' => ['required', 'numeric', 'min:0', 'max:100']]);
        $student = Student::findOrFail($data['student_id']);
        $report = ReportCard::firstOrCreate(['student_id' => $student->id, 'academic_year_id' => $data['academic_year_id'], 'semester' => $data['semester']], ['school_class_id' => $student->enrollments()->whereHas('schoolClass', fn ($q) => $q->where('academic_year_id', $data['academic_year_id']))->value('school_class_id')]);
        abort_if($report->locked_at, 422, 'Rapor sudah dikunci.');
        $scores = StudentScore::where('student_id', $student->id)->whereNotNull('score')->whereHas('assessment', fn ($q) => $q->where(fn ($semester) => $semester->where('semester', $data['semester'])->orWhereNull('semester'))->whereHas('schoolClass', fn ($class) => $class->where('academic_year_id', $data['academic_year_id'])))->with('assessment')->get()->groupBy(fn ($score) => $score->assessment->subject_id);
        foreach ($scores as $subjectId => $items) { $weight = $items->sum(fn ($item) => max(0.01, (float) $item->assessment->weight)); $value = round($items->sum(fn ($item) => (float) $item->score * max(0.01, (float) $item->assessment->weight)) / $weight, 2); $predicate = $value >= 90 ? 'A' : ($value >= 80 ? 'B' : ($value >= 70 ? 'C' : 'D')); $report->items()->updateOrCreate(['subject_id' => $subjectId], ['score' => $value, 'minimum_score' => $data['minimum_score'], 'predicate' => $predicate]); }
        return redirect()->route('admin.student-reports.index', ['student_id' => $student->id])->with('success', 'Draf rapor berhasil dibuat dari nilai yang tersedia.');
    }

    public function publish(Request $request, ReportCard $reportCard)
    {
        $data = $request->validate(['homeroom_note' => ['nullable', 'string', 'max:3000'], 'publish' => ['nullable', 'boolean'], 'lock' => ['nullable', 'boolean']]);
        abort_if($reportCard->locked_at, 422, 'Rapor sudah dikunci.');
        $reportCard->update(['homeroom_note' => $data['homeroom_note'] ?? null, 'published_at' => $request->boolean('publish') ? now() : null, 'locked_at' => $request->boolean('lock') ? now() : null]);
        if ($request->boolean('publish')) {
            $reportCard->loadMissing('student.user', 'student.guardians', 'academicYear');
            $recipients = $reportCard->student->guardians->when($reportCard->student->user, fn ($items) => $items->push($reportCard->student->user));
            foreach ($recipients->unique('id') as $recipient) $recipient->notify(new ActivityNotification(['title' => 'Rapor semester diterbitkan', 'message' => 'Rapor '.$reportCard->academicYear->name.' semester '.ucfirst($reportCard->semester).' untuk '.$reportCard->student->nama.' sudah tersedia.', 'url' => $recipient->isStudent() ? route('student.reports') : route('guardian.index'), 'type' => 'report']));
        }
        return back()->with('success', 'Status rapor diperbarui.');
    }
}
