<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeachingController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $this->teacherFor($request);

        return view('teaching.index', [
            'teacher' => $teacher,
            'assignments' => $teacher->teachingAssignments()->with('subject')
                ->orderByDesc('tahun_ajaran')->orderBy('kelas')->get(),
            'schedules' => $teacher->classSchedules()->with(['schoolClass.academicYear', 'subject'])
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'recentAssessments' => $teacher->assessments()->with(['schoolClass', 'subject'])->withCount('scores')
                ->latest('assessment_date')->limit(6)->get(),
            'homeroomClasses' => $teacher->homeroomClasses()->with(['academicYear'])->withCount('enrollments')->get(),
        ]);
    }

    public function scores(Request $request, Assessment $assessment)
    {
        $teacher = $this->teacherFor($request);
        $assessment->load(['schoolClass.academicYear', 'subject', 'teacher']);
        abort_unless(
            $assessment->teacher_id === $teacher->id && $this->hasAssignment($teacher, $assessment->schoolClass, $assessment->subject_id),
            403,
        );

        $students = $assessment->schoolClass->enrollments()->where('status', 'aktif')->with('student')
            ->get()->sortBy(fn ($enrollment) => $enrollment->student->nama);
        $scores = $assessment->scores()->get()->keyBy('student_id');

        return view('teaching.scores', compact('assessment', 'students', 'scores'));
    }

    public function storeScores(Request $request, Assessment $assessment)
    {
        $teacher = $this->teacherFor($request);
        $assessment->load(['schoolClass.academicYear']);
        abort_unless(
            $assessment->teacher_id === $teacher->id && $this->hasAssignment($teacher, $assessment->schoolClass, $assessment->subject_id),
            403,
        );

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->maximum_score],
            'scores.*.note' => ['nullable', 'string', 'max:500'],
        ]);
        $studentIds = $assessment->schoolClass->enrollments()->where('status', 'aktif')->pluck('student_id')->all();

        DB::transaction(function () use ($data, $studentIds, $assessment) {
            foreach ($data['scores'] as $studentId => $score) {
                if (in_array((int) $studentId, $studentIds, true)) {
                    StudentScore::updateOrCreate(
                        ['assessment_id' => $assessment->id, 'student_id' => $studentId],
                        ['score' => $score['score'] ?? null, 'note' => $score['note'] ?? null],
                    );
                }
            }
        });

        return back()->with('success', 'Nilai disimpan.');
    }

    public function attendance(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $data = $request->validate([
            'school_class_id' => ['nullable', 'integer', Rule::exists('school_classes', 'id')],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        $assignments = $teacher->teachingAssignments()->get(['kelas', 'tahun_ajaran']);
        $classesQuery = SchoolClass::with(['academicYear', 'enrollments' => fn ($query) => $query->where('status', 'aktif')->with('student')])
            ->orderByDesc('academic_year_id')->orderBy('name');

        if ($assignments->isEmpty()) {
            $classesQuery->whereRaw('1 = 0');
        } else {
            $classesQuery->where(function ($query) use ($assignments) {
                foreach ($assignments as $assignment) {
                    $query->orWhere(fn ($query) => $query
                        ->where('name', $assignment->kelas)
                        ->whereHas('academicYear', fn ($year) => $year->where('name', $assignment->tahun_ajaran)));
                }
            });
        }

        $classes = $classesQuery->get();
        $classId = (int) ($data['school_class_id'] ?? $classes->first()?->id);
        $schoolClass = $classId ? $classes->firstWhere('id', $classId) : null;
        abort_if($classId && ! $schoolClass, 403, 'Kelas tersebut tidak termasuk penugasan Anda.');
        $date = $data['date'] ?? now()->toDateString();
        $attendances = $schoolClass
            ? StudentAttendance::where('school_class_id', $schoolClass->id)->whereDate('attendance_date', $date)->get()->keyBy('student_id')
            : collect();

        return view('teaching.attendance', compact('classes', 'classId', 'date', 'schoolClass', 'attendances'));
    }

    public function storeAttendance(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $data = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', Rule::in(array_keys(StudentAttendance::STATUSES))],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ]);
        $schoolClass = SchoolClass::with('academicYear')->findOrFail($data['school_class_id']);
        abort_unless($this->hasAssignment($teacher, $schoolClass), 403, 'Kelas tersebut tidak termasuk penugasan Anda.');
        $studentIds = $schoolClass->enrollments()->where('status', 'aktif')->pluck('student_id')->all();

        DB::transaction(function () use ($data, $studentIds, $request) {
            foreach ($data['attendance'] as $studentId => $attendance) {
                if (in_array((int) $studentId, $studentIds, true)) {
                    StudentAttendance::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'school_class_id' => $data['school_class_id'],
                            'attendance_date' => $data['attendance_date'],
                        ],
                        [
                            'status' => $attendance['status'],
                            'note' => $attendance['note'] ?? null,
                            'recorded_by' => $request->user()->id,
                        ],
                    );
                }
            }
        });

        return redirect()->route('teaching.attendance', [
            'school_class_id' => $data['school_class_id'],
            'date' => $data['attendance_date'],
        ])->with('success', 'Absensi siswa disimpan.');
    }

    private function teacherFor(Request $request): Teacher
    {
        $user = $request->user();
        $teacher = $user->teacher;
        abort_unless($user->isTeacher() && $teacher?->status === 'aktif', 403, 'Halaman ini hanya untuk guru aktif.');

        return $teacher;
    }

    private function hasAssignment(Teacher $teacher, SchoolClass $schoolClass, ?int $subjectId = null): bool
    {
        $schoolClass->loadMissing('academicYear');

        return $teacher->teachingAssignments()
            ->where('kelas', $schoolClass->name)
            ->where('tahun_ajaran', $schoolClass->academicYear->name)
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->exists();
    }
}
