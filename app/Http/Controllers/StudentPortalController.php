<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ClassSchedule;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
use Illuminate\Http\Request;

class StudentPortalController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->student;
        abort_unless(
            $request->user()->isStudent() && $student && $student->status === 'aktif',
            403,
            'Akun ini belum ditautkan ke data siswa aktif.',
        );

        $enrollments = $student->enrollments()
            ->where('status', 'aktif')
            ->with('schoolClass.academicYear')
            ->get();
        $classIds = $enrollments->pluck('school_class_id');

        return view('student.index', [
            'student' => $student,
            'enrollments' => $enrollments,
            'schedules' => ClassSchedule::whereIn('school_class_id', $classIds)
                ->with(['schoolClass.academicYear', 'subject', 'teacher'])
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'assessments' => Assessment::whereIn('school_class_id', $classIds)
                ->with(['schoolClass', 'subject', 'scores' => fn ($query) => $query->where('student_id', $student->id)])
                ->latest('assessment_date')->limit(20)->get(),
            'attendances' => StudentAttendance::where('student_id', $student->id)
                ->with('schoolClass')->latest('attendance_date')->limit(20)->get(),
            'scores' => StudentScore::where('student_id', $student->id)
                ->whereNotNull('score')
                ->with('assessment.subject', 'assessment.schoolClass')
                ->latest()->limit(30)->get(),
        ]);
    }
}
