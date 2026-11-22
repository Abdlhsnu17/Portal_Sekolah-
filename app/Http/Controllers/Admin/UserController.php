<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\SecurityEventNotification;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->input('sort'), ['name', 'role', 'is_active', 'last_login_at'], true)
            ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return view('admin.users.index', [
            'items' => User::with(['teacher', 'staffMember', 'student'])->search($request->input('q'))->orderBy($sort, $direction)->paginate(10)->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        $selectedTeacherId = $request->integer('teacher_id') ?: null;
        $selectedStaffId = $selectedTeacherId ? null : ($request->integer('staff_member_id') ?: null);
        $selectedStudentId = $request->integer('student_id') ?: null;
        if ($selectedStudentId) {
            $selectedTeacherId = null;
            $selectedStaffId = null;
        }
        $selectedTeacher = $selectedTeacherId
            ? Teacher::whereKey($selectedTeacherId)->whereNull('user_id')->first()
            : null;
        $selectedStaff = $selectedStaffId
            ? StaffMember::whereKey($selectedStaffId)->whereNull('user_id')->first()
            : null;

        if ($selectedTeacherId && ! $selectedTeacher) {
            $selectedTeacherId = null;
        }
        if ($selectedStaffId && ! $selectedStaff) {
            $selectedStaffId = null;
        }
        $selectedStudent = $selectedStudentId
            ? Student::whereKey($selectedStudentId)->whereNull('user_id')->where('status', 'aktif')->first()
            : null;
        if ($selectedStudentId && ! $selectedStudent) {
            $selectedStudentId = null;
        }

        return view('admin.users.form', [
            'item' => new User([
                'name' => $selectedStudent?->nama ?? $selectedTeacher?->nama ?? $selectedStaff?->nama,
                'email' => $selectedStudent?->email ?? $selectedTeacher?->email ?? $selectedStaff?->email,
                'telepon' => $selectedTeacher?->telepon ?? $selectedStaff?->telepon,
                'role' => $selectedStudentId ? 'siswa' : ($selectedTeacherId ? 'guru' : 'staf'),
                'is_active' => true,
            ]),
            'teachers' => $this->teacherOptions(),
            'selectedTeacherId' => $selectedTeacherId,
            'staffMembers' => $this->staffOptions(),
            'selectedStaffId' => $selectedStaffId,
            'students' => $this->studentOptions(),
            'selectedStudentId' => $selectedStudentId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::ROLES)],
            'telepon' => ['nullable', 'string', 'max:30'],
            'teacher_id' => [
                Rule::requiredIf($request->input('role') === 'guru'),
                'nullable', 'integer', Rule::prohibitedIf(in_array($request->input('role'), ['staf', 'wali', 'siswa'], true) || $request->filled('staff_member_id') || $request->filled('student_id')),
                'exists:teachers,id', Rule::unique('teachers', 'id')->whereNotNull('user_id'),
            ],
            'staff_member_id' => [
                'nullable', 'integer', Rule::prohibitedIf($request->filled('teacher_id') || $request->filled('student_id') || in_array($request->input('role'), ['guru', 'wali', 'siswa'], true)),
                'exists:staff_members,id', Rule::unique('staff_members', 'id')->whereNotNull('user_id'),
            ],
            'student_id' => [
                Rule::requiredIf($request->input('role') === 'siswa'),
                'nullable', 'integer', Rule::prohibitedIf($request->input('role') !== 'siswa' || $request->filled('teacher_id') || $request->filled('staff_member_id')),
                Rule::exists('students', 'id')->where('status', 'aktif'),
                Rule::unique('students', 'id')->whereNotNull('user_id'),
            ],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $teacherId = $data['teacher_id'] ?? null;
        $staffId = $data['staff_member_id'] ?? null;
        $studentId = $data['student_id'] ?? null;
        unset($data['teacher_id'], $data['staff_member_id'], $data['student_id']);
        $data['is_active'] = $request->boolean('is_active');

        $user = DB::transaction(function () use ($data, $teacherId, $staffId, $studentId) {
            $user = User::create($data);
            if ($teacherId) {
                Teacher::findOrFail($teacherId)->update(['user_id' => $user->id]);
            }
            if ($staffId) {
                StaffMember::findOrFail($staffId)->update(['user_id' => $user->id]);
            }
            if ($studentId) {
                $this->linkStudent($studentId, $user);
            }

            return $user;
        });
        SecurityAudit::record('user.created', $request->user(), $request, ['target_user_id' => $user->id]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'item' => $user->load(['teacher', 'staffMember', 'student']),
            'teachers' => $this->teacherOptions($user),
            'selectedTeacherId' => null,
            'staffMembers' => $this->staffOptions($user),
            'selectedStaffId' => null,
            'students' => $this->studentOptions($user),
            'selectedStudentId' => null,
        ]);
    }

    public function editPassword(User $user)
    {
        return view('admin.users.password', compact('user'));
    }

    public function updatePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $user->forceFill([
            'password' => $data['password'],
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        SecurityAudit::record('user.password_reset', $request->user(), $request, [
            'target_user_id' => $user->id,
            'sessions_revoked' => true,
        ]);

        if (! app()->environment('testing')) {
            $user->notify(new SecurityEventNotification(
                'Administrator mereset kata sandi akun Anda. Silakan masuk kembali menggunakan kata sandi baru.',
                (string) $request->ip(),
            ));
        }

        return redirect()->route('admin.users.index')->with('success', "Kata sandi {$user->name} berhasil diperbarui. Semua sesi akun tersebut telah dikeluarkan.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(User::ROLES)],
            'telepon' => ['nullable', 'string', 'max:30'],
            'teacher_id' => [
                Rule::requiredIf($request->input('role') === 'guru'),
                'nullable', 'integer', Rule::prohibitedIf(in_array($request->input('role'), ['staf', 'wali', 'siswa'], true) || $request->filled('staff_member_id') || $request->filled('student_id')), 'exists:teachers,id',
                Rule::unique('teachers', 'id')->whereNotNull('user_id')->ignore($user->teacher?->id),
            ],
            'staff_member_id' => [
                'nullable', 'integer', Rule::prohibitedIf($request->filled('teacher_id') || $request->filled('student_id') || in_array($request->input('role'), ['guru', 'wali', 'siswa'], true)), 'exists:staff_members,id',
                Rule::unique('staff_members', 'id')->whereNotNull('user_id')->ignore($user->staffMember?->id),
            ],
            'student_id' => [
                Rule::requiredIf($request->input('role') === 'siswa'),
                'nullable', 'integer', Rule::prohibitedIf($request->input('role') !== 'siswa' || $request->filled('teacher_id') || $request->filled('staff_member_id')),
                Rule::exists('students', 'id')->where('status', 'aktif'),
                Rule::unique('students', 'id')->whereNotNull('user_id')->ignore($user->student?->id),
            ],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $teacherId = $data['teacher_id'] ?? null;
        $staffId = $data['staff_member_id'] ?? null;
        $studentId = $data['student_id'] ?? null;
        unset($data['teacher_id'], $data['staff_member_id'], $data['student_id']);
        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'])) {
            unset($data['password']);
        }

        // Jangan sampai admin terakhir mengunci dirinya sendiri.
        if ($user->is($request->user()) && (! $data['is_active'] || $data['role'] !== 'admin')) {
            return back()->withInput()->withErrors([
                'role' => 'Anda tidak dapat mencabut akses admin milik akun Anda sendiri.',
            ]);
        }

        $studentChanged = (int) ($studentId ?? 0) !== (int) ($user->student?->id ?? 0);
        $credentialsChanged = array_key_exists('password', $data) || $data['role'] !== $user->role || ! $data['is_active'] || $studentChanged;
        if (array_key_exists('password', $data)) {
            $data['password_changed_at'] = now();
            $data['remember_token'] = Str::random(60);
        }
        DB::transaction(function () use ($user, $data, $teacherId, $staffId, $studentId) {
            $user->update($data);
            Teacher::where('user_id', $user->id)->update(['user_id' => null]);
            StaffMember::where('user_id', $user->id)->update(['user_id' => null]);
            Student::where('user_id', $user->id)->update(['user_id' => null]);
            if ($teacherId) {
                Teacher::findOrFail($teacherId)->update(['user_id' => $user->id]);
            }
            if ($staffId) {
                StaffMember::findOrFail($staffId)->update(['user_id' => $user->id]);
            }
            if ($studentId) {
                $this->linkStudent($studentId, $user);
            }
        });
        if ($credentialsChanged && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
        SecurityAudit::record('user.updated', $request->user(), $request, ['target_user_id' => $user->id, 'sessions_revoked' => $credentialsChanged]);
        if ($credentialsChanged && ! app()->environment('testing')) {
            $user->notify(new SecurityEventNotification('Administrator mengubah kata sandi, role, atau status akses akun Anda.', (string) $request->ip()));
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        $target = $user->id;
        $user->delete();
        SecurityAudit::record('user.deleted', $request->user(), $request, ['target_user_id' => $target]);

        return back()->with('success', 'Pengguna dihapus.');
    }

    private function teacherOptions(?User $user = null): array
    {
        return Teacher::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id');
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Teacher $teacher) => [
                $teacher->id => "{$teacher->nama} ({$teacher->nip})",
            ])
            ->all();
    }

    private function staffOptions(?User $user = null): array
    {
        return StaffMember::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id');
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (StaffMember $staff) => [
                $staff->id => "{$staff->nama} ({$staff->nip})",
            ])
            ->all();
    }

    private function studentOptions(?User $user = null): array
    {
        return Student::query()
            ->where('status', 'aktif')
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id');
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Student $student) => [
                $student->id => "{$student->nama} ({$student->nis})",
            ])
            ->all();
    }

    private function linkStudent(int $studentId, User $user): void
    {
        $student = Student::query()->lockForUpdate()->findOrFail($studentId);
        if ($student->status !== 'aktif') {
            throw ValidationException::withMessages([
                'student_id' => 'Akun hanya dapat ditautkan ke data siswa aktif.',
            ]);
        }

        if ($student->user_id && $student->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'student_id' => 'Data siswa tersebut sudah ditautkan ke akun lain.',
            ]);
        }

        $student->update(['user_id' => $user->id]);
    }
}
