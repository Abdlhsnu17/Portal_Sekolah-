<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentNote;
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
        $assignments = $teacher->teachingAssignments()->with('subject')
            ->orderByDesc('tahun_ajaran')->orderBy('kelas')->get();
        $schedules = $teacher->classSchedules()->with(['schoolClass.academicYear', 'subject'])
            ->orderBy('day_of_week')->orderBy('starts_at')->get();
        $students = $this->studentsForTeacher($teacher);

        return view('teaching.index', [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'schedules' => $schedules->take(3),
            'homeroomClasses' => $teacher->homeroomClasses()->with(['academicYear'])->withCount('enrollments')->get(),
            'statistics' => [
                'classes' => $assignments->map(fn ($assignment) => $assignment->tahun_ajaran.'|'.$assignment->kelas)->unique()->count(),
                'subjects' => $assignments->pluck('subject_id')->unique()->count(),
                'students' => $students->count(),
                'schedules' => $schedules->count(),
            ],
        ]);
    }

    public function schedule(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $schedules = $teacher->classSchedules()
            ->with(['schoolClass.academicYear', 'subject'])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        return view('teaching.schedule', compact('schedules'));
    }

    public function notes(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $notes = $teacher->studentNotes()
            ->where('created_by', $request->user()->id)
            ->with('student')
            ->latest()
            ->get();
        $editingNote = $request->filled('edit')
            ? $notes->firstWhere('id', $request->integer('edit'))
            : null;

        abort_if($request->filled('edit') && ! $editingNote, 404);

        return view('teaching.notes', [
            'students' => $this->studentsForTeacher($teacher),
            'notes' => $notes,
            'editingNote' => $editingNote,
        ]);
    }

    public function storeNote(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'publish' => ['nullable', 'boolean'],
        ]);
        $students = $this->studentsForTeacher($teacher);
        abort_unless($students->contains('id', (int) $data['student_id']), 403, 'Siswa tersebut bukan bagian dari kelas penugasan Anda.');

        StudentNote::create([
            'student_id' => $data['student_id'],
            'teacher_id' => $teacher->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'published_at' => $request->boolean('publish') ? now() : null,
        ]);

        return redirect()->route('teaching.notes.index')->with('success', 'Catatan siswa berhasil disimpan.');
    }

    public function updateNote(Request $request, StudentNote $studentNote)
    {
        $teacher = $this->teacherFor($request);
        abort_unless(
            (int) $studentNote->teacher_id === $teacher->id
                && (int) $studentNote->created_by === $request->user()->id,
            404,
        );

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'publish' => ['nullable', 'boolean'],
        ]);

        $studentNote->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'published_at' => $request->boolean('publish') ? ($studentNote->published_at ?? now()) : null,
        ]);

        return redirect()->route('teaching.notes.index')->with('success', 'Catatan siswa berhasil diperbarui.');
    }

    public function destroyNote(Request $request, StudentNote $studentNote)
    {
        $teacher = $this->teacherFor($request);
        abort_unless(
            (int) $studentNote->teacher_id === $teacher->id
                && (int) $studentNote->created_by === $request->user()->id,
            404,
        );

        $studentNote->delete();

        return redirect()->route('teaching.notes.index')->with('success', 'Catatan siswa berhasil dihapus.');
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

    public function assessments(Request $request)
    {
        $teacher = $this->teacherFor($request);
        $assessments = $teacher->assessments()
            ->with(['schoolClass.academicYear', 'subject'])
            ->withCount('scores')
            ->latest('assessment_date')
            ->get();

        $assessmentGroups = [
            'harian' => [
                'title' => 'Penilaian Harian',
                'description' => 'Tugas dan kuis pembelajaran.',
                'assessments' => $assessments->whereIn('type', ['tugas', 'kuis']),
            ],
            'tengah_semester' => [
                'title' => 'Tengah Semester',
                'description' => 'Penilaian Tengah Semester (PTS).',
                'assessments' => $assessments->where('type', 'pts'),
            ],
            'akhir_dan_praktik' => [
                'title' => 'Akhir & Praktik',
                'description' => 'PAS dan penilaian praktik.',
                'assessments' => $assessments->whereIn('type', ['pas', 'praktik']),
            ],
        ];

        return view('teaching.assessments', compact('assessmentGroups'));
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

    private function studentsForTeacher(Teacher $teacher)
    {
        $assignments = $teacher->teachingAssignments()->get(['kelas', 'tahun_ajaran']);
        if ($assignments->isEmpty()) {
            return collect();
        }

        $classIds = SchoolClass::query()
            ->where(function ($query) use ($assignments) {
                foreach ($assignments as $assignment) {
                    $query->orWhere(fn ($query) => $query
                        ->where('name', $assignment->kelas)
                        ->whereHas('academicYear', fn ($year) => $year->where('name', $assignment->tahun_ajaran)));
                }
            })
            ->pluck('id');

        return Student::query()
            ->whereHas('enrollments', fn ($query) => $query->where('status', 'aktif')->whereIn('school_class_id', $classIds))
            ->orderBy('nama')
            ->get();
    }
}
