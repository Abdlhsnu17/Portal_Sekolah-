<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->input('sort'), ['nis', 'nama', 'kelas', 'jurusan', 'jenis_kelamin', 'status'], true)
            ? $request->input('sort') : 'nama';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return view('admin.students.index', [
            'items' => Student::with('user')->search($request->input('q'))
                ->when($request->input('kelas'), fn ($query, $kelas) => $query->where('kelas', $kelas))
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->withQueryString(),
            'daftarKelas' => Student::whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas'),
            'filterKelas' => $request->input('kelas'),
        ]);
    }

    public function create()
    {
        $student = new Student;
        $student->fill(['status' => 'aktif', 'jenis_kelamin' => 'L']);

        return view('admin.students.form', ['item' => $student]);
    }

    public function store(Request $request)
    {
        Student::create($this->validated($request));

        return redirect()->route('admin.students.index')->with('success', 'Data siswa ditambahkan.');
    }

    public function edit(Student $student)
    {
        return view('admin.students.form', ['item' => $student]);
    }

    public function update(Request $request, Student $student)
    {
        $student->update($this->validated($request, $student));

        return redirect()->route('admin.students.index')->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('success', 'Data siswa dihapus.');
    }

    protected function validated(Request $request, ?Student $student = null): array
    {
        return $request->validate([
            'nis' => ['required', 'string', 'max:30', Rule::unique('students', 'nis')->ignore($student)],
            'nisn' => ['nullable', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kelas' => ['nullable', 'string', 'max:30'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
            'telepon_wali' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['aktif', 'lulus', 'pindah', 'nonaktif'])],
        ]);
    }
}
