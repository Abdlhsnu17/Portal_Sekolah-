<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeachingAssignmentController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.teaching-assignments.index', [
            'assignments' => TeachingAssignment::with(['teacher', 'subject'])
                ->orderByDesc('tahun_ajaran')->orderBy('kelas')->paginate(15),
            'subjects' => Subject::orderBy('nama')->get(),
            'teachers' => Teacher::where('status', 'aktif')->orderBy('nama')->get(),
            'classes' => Student::whereNotNull('kelas')->where('kelas', '!=', '')
                ->distinct()->orderBy('kelas')->pluck('kelas'),
            'classSummary' => Student::whereNotNull('kelas')->where('kelas', '!=', '')
                ->selectRaw('kelas, COUNT(*) as student_count')->groupBy('kelas')->orderBy('kelas')->get(),
        ]);
    }

    public function storeSubject(Request $request)
    {
        Subject::create($request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:subjects,kode'],
            'nama' => ['required', 'string', 'max:100', 'unique:subjects,nama'],
        ]) + ['is_active' => true]);

        return back()->with('success', 'Mata pelajaran ditambahkan.');
    }

    public function store(Request $request)
    {
        TeachingAssignment::create($this->validated($request));

        return back()->with('success', 'Penugasan mengajar ditambahkan.');
    }

    public function destroy(TeachingAssignment $teachingAssignment)
    {
        $teachingAssignment->delete();

        return back()->with('success', 'Penugasan mengajar dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'teacher_id' => ['required', Rule::exists('teachers', 'id')->where('status', 'aktif')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('is_active', true)],
            'kelas' => ['required', 'string', 'max:30'],
            'tahun_ajaran' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
        ], [
            'tahun_ajaran.regex' => 'Format tahun ajaran harus YYYY/YYYY, misalnya 2026/2027.',
        ]);
    }
}
