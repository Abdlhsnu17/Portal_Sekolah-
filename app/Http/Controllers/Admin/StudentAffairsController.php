<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentIncident;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAffairsController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.student-affairs.index', ['students' => Student::where('status', 'aktif')->orderBy('nama')->get(), 'incidents' => StudentIncident::with(['student', 'recorder'])->latest('occurred_on')->latest()->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'type' => ['required', Rule::in(array_keys(StudentIncident::TYPES))], 'title' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string', 'max:5000'], 'points' => ['required', 'integer', 'min:-1000', 'max:1000'], 'occurred_on' => ['required', 'date'], 'follow_up_status' => ['required', Rule::in(['open', 'follow_up', 'closed'])], 'follow_up_note' => ['nullable', 'string', 'max:5000']]);
        StudentIncident::create($data + ['recorded_by' => $request->user()->id]);
        return back()->with('success', 'Catatan kesiswaan disimpan.');
    }
}
