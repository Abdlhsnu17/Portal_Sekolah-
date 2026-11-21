<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()->isAdmin()) {
            $sort = in_array($request->input('sort'), ['nip', 'nama', 'mata_pelajaran', 'jabatan', 'status'], true)
                ? $request->input('sort') : 'nama';
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

            return view('admin.teachers.index', [
                'items' => Teacher::with('user')->search($request->input('q'))
                    ->orderBy($sort, $direction)->paginate(10)->withQueryString(),
                'accountCounts' => [
                    'all' => Teacher::count(),
                    'linked' => Teacher::whereNotNull('user_id')->count(),
                    'unlinked' => Teacher::whereNull('user_id')->count(),
                ],
            ]);
        }

        return redirect()->route('admin.staff.index', array_merge(
            $request->only(['q', 'sort', 'direction', 'account']),
            ['type' => 'teacher'],
        ));
    }

    public function create(Request $request)
    {
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.staff.create', ['type' => 'teacher']);
        }

        return view('admin.teachers.form', ['item' => new Teacher(['status' => 'aktif', 'jenis_kelamin' => 'L'])]);
    }

    public function store(Request $request)
    {
        Teacher::create($this->validated($request));

        $route = $request->user()->isAdmin() ? route('admin.staff.index', ['type' => 'teacher']) : route('admin.teachers.index');

        return redirect($route)->with('success', 'Data guru ditambahkan.');
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.form', ['item' => $teacher->load('user')]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $teacher->update($this->validated($request, $teacher));

        $route = $request->user()->isAdmin() ? route('admin.staff.index', ['type' => 'teacher']) : route('admin.teachers.index');

        return redirect($route)->with('success', 'Data guru diperbarui.');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->attendances()->whereNotNull('face_photo_path')->pluck('face_photo_path')
            ->each(fn (string $path) => Storage::disk('local')->delete($path));
        $teacher->delete();

        return back()->with('success', 'Data guru dihapus.');
    }

    protected function validated(Request $request, ?Teacher $teacher = null): array
    {
        return $request->validate([
            'nip' => ['required', 'string', 'max:30', Rule::unique('teachers', 'nip')->ignore($teacher), Rule::unique('staff_members', 'nip')],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'mata_pelajaran' => ['nullable', 'string', 'max:100'],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'tanggal_bergabung' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['aktif', 'cuti', 'nonaktif'])],
        ]);
    }
}
