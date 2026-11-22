<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentScore;
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

        return view('admin.student-reports.index', compact('students', 'student', 'scoresBySubject', 'attendance'));
    }
}
