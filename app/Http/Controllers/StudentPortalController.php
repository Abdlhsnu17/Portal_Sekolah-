<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ClassSchedule;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
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
