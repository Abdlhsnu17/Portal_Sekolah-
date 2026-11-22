<?php

namespace App\Http\Controllers;

use App\Models\SchoolProfile;
use App\Models\StudentAttendance;
use App\Models\StudentInvoice;
use App\Models\StudentScore;
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
}
