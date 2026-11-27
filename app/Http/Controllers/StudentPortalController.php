<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassSchedule;
use App\Models\LearningResource;
use App\Models\LibraryBook;
use App\Models\LibraryReservation;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
use App\Models\ReportCard;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->studentFor($request);
        $enrollments = $student->enrollments()
            ->where('status', 'aktif')
            ->with('schoolClass.academicYear')
            ->get();
        $classIds = $enrollments->pluck('school_class_id');
        $studentKey = $student->getKey();

        return view('student.index', [
            'student' => $student,
            'enrollments' => $enrollments,
            'schedules' => ClassSchedule::whereIn('school_class_id', $classIds)
                ->with(['schoolClass.academicYear', 'subject', 'teacher'])
                ->orderBy('day_of_week')->orderBy('starts_at')->limit(3)->get(),
            'assessments' => Assessment::whereIn('school_class_id', $classIds)
                ->with(['schoolClass', 'subject', 'scores' => fn ($query) => $query->where('student_id', $studentKey)])
                ->latest('assessment_date')->limit(20)->get(),
            'attendances' => StudentAttendance::where('student_id', $studentKey)
                ->with('schoolClass')->latest('attendance_date')->limit(20)->get(),
            'scores' => StudentScore::where('student_id', $studentKey)
                ->whereNotNull('score')
                ->with('assessment.subject', 'assessment.schoolClass')
                ->latest()->limit(30)->get(),
            'notes' => $student->notes()->whereNotNull('published_at')->latest('published_at')->limit(1)->get(),
        ]);
    }

    public function schedule(Request $request)
    {
        $student = $this->studentFor($request);
        $classIds = $student->enrollments()->where('status', 'aktif')->pluck('school_class_id');
        $schedules = ClassSchedule::whereIn('school_class_id', $classIds)
            ->with(['schoolClass.academicYear', 'subject', 'teacher'])
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        return view('student.schedule', compact('student', 'schedules'));
    }

    public function notes(Request $request)
    {
        $student = $this->studentFor($request);
        $notes = $student->notes()
            ->whereNotNull('published_at')
            ->with('teacher')
            ->latest('published_at')
            ->get();

        return view('student.notes', compact('student', 'notes'));
    }

    public function learning(Request $request)
    {
        $student = $this->studentFor($request);
        $classIds = $student->enrollments()->where('status', 'aktif')->pluck('school_class_id');
        return view('student.learning', [
            'student' => $student,
            'resources' => LearningResource::whereIn('school_class_id', $classIds)->whereNotNull('published_at')->with(['schoolClass', 'subject', 'teacher'])->latest('published_at')->get(),
            'assignments' => Assignment::whereIn('school_class_id', $classIds)->where('is_published', true)->with(['schoolClass', 'subject', 'teacher', 'submissions' => fn ($q) => $q->where('student_id', $student->id)])->orderBy('due_at')->get(),
        ]);
    }

    public function reportCards(Request $request)
    {
        $student = $this->studentFor($request);
        return view('student.reports', ['student' => $student, 'reports' => ReportCard::where('student_id', $student->id)->whereNotNull('published_at')->with(['academicYear', 'schoolClass', 'items.subject'])->latest('published_at')->get()]);
    }

    public function library(Request $request)
    {
        $student = $this->studentFor($request);
        return view('student.library', ['books' => LibraryBook::withCount(['loans as borrowed_count' => fn ($q) => $q->whereNull('returned_on')])->orderBy('title')->get(), 'reservations' => $student->libraryReservations()->with('book')->latest()->get()]);
    }

    public function reserveBook(Request $request, LibraryBook $book)
    {
        $student = $this->studentFor($request);
        abort_if($book->available_stock > 0, 422, 'Buku masih tersedia; silakan pinjam melalui petugas perpustakaan.');
        LibraryReservation::firstOrCreate(['library_book_id' => $book->id, 'student_id' => $student->id, 'status' => 'waiting']);
        return back()->with('success', 'Reservasi buku berhasil dibuat. Petugas akan menghubungi Anda saat buku tersedia.');
    }

    public function submitAssignment(Request $request, Assignment $assignment)
    {
        $student = $this->studentFor($request);
        abort_unless($student->enrollments()->where('status', 'aktif')->where('school_class_id', $assignment->school_class_id)->exists() && $assignment->is_published, 403);
        $data = $request->validate(['answer' => ['nullable', 'string', 'max:10000']]);
        abort_if(blank($data['answer'] ?? null), 422, 'Isi jawaban tugas terlebih dahulu.');
        AssignmentSubmission::updateOrCreate(['assignment_id' => $assignment->id, 'student_id' => $student->id], ['answer' => $data['answer'], 'submitted_at' => now()]);
        return back()->with('success', 'Tugas berhasil dikumpulkan.');
    }

    private function studentFor(Request $request): Student
    {
        $student = $request->user()->student;
        abort_unless(
            $request->user()->isStudent() && $student && $student->status === 'aktif',
            403,
            'Akun ini belum ditautkan ke data siswa aktif.',
        );

        return $student;
    }
}
