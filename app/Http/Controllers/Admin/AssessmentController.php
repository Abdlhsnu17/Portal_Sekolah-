<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'academic_year_id' => ['nullable', Rule::exists('academic_years', 'id')],
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')],
        ]);

        $years = \App\Models\AcademicYear::orderByDesc('starts_on')->get();
        $selectedYearId = $filters['academic_year_id'] ?? $years->firstWhere('is_active', true)?->id ?? $years->first()?->id;
        $classes = SchoolClass::with('academicYear')
            ->when($selectedYearId, fn ($query) => $query->where('academic_year_id', $selectedYearId))
            ->orderBy('name')
            ->get();
        $selectedClassId = $filters['school_class_id'] ?? null;

        if ($selectedClassId && ! $classes->contains('id', (int) $selectedClassId)) {
            abort(404);
        }

        return view('admin.assessments.index', [
            'assessments' => Assessment::with(['schoolClass.academicYear', 'teacher', 'subject'])->withCount('scores')
                ->when($selectedYearId, fn ($query) => $query->whereHas('schoolClass', fn ($class) => $class->where('academic_year_id', $selectedYearId)))
                ->when($selectedClassId, fn ($query) => $query->where('school_class_id', $selectedClassId))
                ->latest('assessment_date')->get(),
            'years' => $years,
            'classes' => $classes,
            'selectedYearId' => $selectedYearId,
            'selectedClassId' => $selectedClassId,
            'teachers' => Teacher::where('status', 'aktif')->orderBy('nama')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('nama')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Assessment::create($data);
        return back()->with('success', 'Komponen penilaian ditambahkan.');
    }

    public function update(Request $request, Assessment $assessment)
    {
        $assessment->update($this->validated($request));
        return redirect()->route('admin.assessments.index', ['edit' => $assessment->id])->with('success', 'Komponen penilaian diperbarui.');
    }

    public function destroy(Assessment $assessment)
    {
        $assessment->delete();
        return back()->with('success', 'Komponen penilaian dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')],
            'teacher_id' => ['required', Rule::exists('teachers', 'id')->where('status', 'aktif')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Assessment::TYPES))],
            'semester' => ['nullable', Rule::in(['ganjil', 'genap'])],
            'maximum_score' => ['required', 'numeric', 'min:1', 'max:999.99'],
            'weight' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'is_published' => ['nullable', 'boolean'],
            'assessment_date' => ['required', 'date'],
        ]);
    }

    public function scores(Assessment $assessment)
    {
        $assessment->load(['schoolClass.academicYear', 'subject', 'teacher']);
        $students = $assessment->schoolClass->enrollments()->where('status', 'aktif')->with('student')->get()->sortBy(fn ($enrollment) => $enrollment->student->nama);
        $scores = $assessment->scores()->get()->keyBy('student_id');
        return view('admin.assessments.scores', compact('assessment', 'students', 'scores'));
    }

    public function storeScores(Request $request, Assessment $assessment)
    {
        $data = $request->validate(['scores' => ['required', 'array'], 'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->maximum_score], 'scores.*.note' => ['nullable', 'string', 'max:500']]);
        $studentIds = $assessment->schoolClass->enrollments()->where('status', 'aktif')->pluck('student_id')->all();
        DB::transaction(function () use ($data, $studentIds, $assessment) {
            foreach ($data['scores'] as $studentId => $score) {
                if (in_array((int) $studentId, $studentIds, true)) StudentScore::updateOrCreate(['assessment_id' => $assessment->id, 'student_id' => $studentId], ['score' => $score['score'] ?? null, 'note' => $score['note'] ?? null]);
            }
        });
        return back()->with('success', 'Nilai disimpan.');
    }
}
