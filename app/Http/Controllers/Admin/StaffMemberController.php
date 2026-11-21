<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\Teacher;
use App\Models\User;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StaffMemberController extends Controller
{
    public function createTeacherAccounts(Request $request)
    {
        $created = [];
        $skipped = 0;

        DB::transaction(function () use (&$created, &$skipped) {
            Teacher::query()->whereNull('user_id')->orderBy('id')->lockForUpdate()->each(function (Teacher $teacher) use (&$created, &$skipped) {
                $email = strtolower(trim((string) $teacher->email));
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $skipped++;
                    return;
                }

                $temporaryPassword = Str::random(20).'aA1!';
                $user = User::create([
                    'name' => $teacher->nama,
                    'email' => $email,
                    'password' => Hash::make($temporaryPassword),
                    'role' => 'guru',
                    'telepon' => $teacher->telepon,
                    'is_active' => $teacher->status === 'aktif',
                    'password_changed_at' => now()->subYears(100),
                ]);
                $teacher->update(['user_id' => $user->id]);
                $created[] = ['name' => $teacher->nama, 'email' => $email, 'password' => $temporaryPassword];
            });
        });

        foreach ($created as $account) {
            SecurityAudit::record('user.created_from_teacher', $request->user(), $request, ['target_email_hash' => hash('sha256', $account['email'])]);
        }

        $request->session()->flash('teacher_account_credentials', $created);

        return redirect()->route('admin.staff.index', ['type' => 'teacher', 'account' => 'unlinked'])
            ->with('success', count($created).' akun guru dibuat; '.$skipped.' data dilewati karena email kosong/tidak valid atau sudah digunakan.');
    }

    public function index(Request $request)
    {
        $sort = in_array($request->input('sort'), ['nip', 'nama', 'jabatan', 'status'], true)
            ? $request->input('sort') : 'nama';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        $type = in_array($request->input('type'), ['teacher', 'staff'], true) ? $request->input('type') : null;
        $account = in_array($request->input('account'), ['linked', 'unlinked'], true) ? $request->input('account') : null;
        $term = trim((string) $request->input('q'));

        $teachers = DB::table('teachers')->select(['id', 'user_id', 'nip', 'nama', 'email', 'jabatan', 'status', DB::raw("'teacher' as type"), DB::raw("COALESCE(mata_pelajaran, '') as detail")]);
        $staff = DB::table('staff_members')->select(['id', 'user_id', 'nip', 'nama', 'email', 'jabatan', 'status', DB::raw("'staff' as type"), DB::raw("'' as detail")]);
        $items = DB::query()->fromSub($teachers->unionAll($staff), 'staff_data')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($account === 'linked', fn ($query) => $query->whereNotNull('user_id'))
            ->when($account === 'unlinked', fn ($query) => $query->whereNull('user_id'))
            ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                $query->where('nama', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%")
                    ->orWhere('jabatan', 'like', "%{$term}%")->orWhere('detail', 'like', "%{$term}%");
            }))
            ->orderBy($sort, $direction)->paginate(10)->withQueryString();

        return view('admin.staff.index', [
            'items' => $items,
            'users' => User::whereIn('id', $items->getCollection()->pluck('user_id')->filter())->get()->keyBy('id'),
            'type' => $type,
            'counts' => [
                'all' => DB::table('teachers')->count() + DB::table('staff_members')->count(),
                'teacher' => DB::table('teachers')->count(),
                'staff' => DB::table('staff_members')->count(),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.staff.form', [
            'item' => new StaffMember(['status' => 'aktif', 'jenis_kelamin' => 'L']),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->validate([
            'employment_type' => ['required', Rule::in(['teacher', 'staff'])],
        ])['employment_type'];

        if ($type === 'teacher') {
            Teacher::create($this->validatedTeacher($request));

            return redirect()->route('admin.staff.index', ['type' => 'teacher'])
                ->with('success', 'Data guru ditambahkan.');
        }

        StaffMember::create($this->validated($request));

        return redirect()->route('admin.staff.index')->with('success', 'Data tenaga kependidikan ditambahkan.');
    }

    public function edit(StaffMember $staff)
    {
        return view('admin.staff.form', ['item' => $staff->load('user')]);
    }

    public function update(Request $request, StaffMember $staff)
    {
        $staff->update($this->validated($request, $staff));

        return redirect()->route('admin.staff.index')->with('success', 'Data tenaga kependidikan diperbarui.');
    }

    public function destroy(StaffMember $staff)
    {
        $staff->attendances()->whereNotNull('face_photo_path')->pluck('face_photo_path')
            ->each(fn (string $path) => Storage::disk('local')->delete($path));
        $staff->delete();

        return back()->with('success', 'Data tenaga kependidikan dihapus.');
    }

    private function validated(Request $request, ?StaffMember $staff = null): array
    {
        return $request->validate([
            'nip' => ['required', 'string', 'max:30', Rule::unique('staff_members', 'nip')->ignore($staff), Rule::unique('teachers', 'nip')],
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'jabatan' => ['nullable', 'string', 'max:100'],
            'tanggal_bergabung' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['aktif', 'cuti', 'nonaktif'])],
        ]);
    }

    private function validatedTeacher(Request $request): array
    {
        return $request->validate([
            'nip' => ['required', 'string', 'max:30', Rule::unique('teachers', 'nip'), Rule::unique('staff_members', 'nip')],
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
