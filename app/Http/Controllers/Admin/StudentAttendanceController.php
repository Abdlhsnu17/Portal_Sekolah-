<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get();
        $classId = $request->integer('school_class_id') ?: $classes->first()?->id;
        $date = $request->input('date', now()->toDateString());
        $schoolClass = $classId ? SchoolClass::with(['academicYear', 'enrollments.student'])->findOrFail($classId) : null;
        $attendances = $schoolClass ? StudentAttendance::where('school_class_id', $schoolClass->id)
            ->whereDate('attendance_date', $date)->get()->keyBy('student_id') : collect();

        return view('admin.student-attendances.index', compact('classes', 'classId', 'date', 'schoolClass', 'attendances'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', Rule::in(array_keys(StudentAttendance::STATUSES))],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $studentIds = SchoolClass::findOrFail($data['school_class_id'])->enrollments()
            ->where('status', 'aktif')->pluck('student_id')->all();

        DB::transaction(function () use ($data, $studentIds) {
            foreach ($data['attendance'] as $studentId => $attendance) {
                if (! in_array((int) $studentId, $studentIds, true)) continue;
                StudentAttendance::updateOrCreate(
                    ['student_id' => $studentId, 'school_class_id' => $data['school_class_id'], 'attendance_date' => $data['attendance_date']],
                    ['status' => $attendance['status'], 'note' => $attendance['note'] ?? null, 'recorded_by' => auth()->id()],
                );
            }
        });

        return redirect()->route('admin.student-attendances.index', ['school_class_id' => $data['school_class_id'], 'date' => $data['attendance_date']])->with('success', 'Absensi siswa disimpan.');
    }
}
