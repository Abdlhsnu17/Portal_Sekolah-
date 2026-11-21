<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\SecurityEventNotification;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->input('sort'), ['name', 'role', 'is_active', 'last_login_at'], true)
            ? $request->input('sort') : 'name';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        return view('admin.users.index', [
            'items' => User::with(['teacher', 'staffMember'])->search($request->input('q'))->orderBy($sort, $direction)->paginate(10)->withQueryString(),
        ]);
    }

    public function create(Request $request)
    {
        $selectedTeacherId = $request->integer('teacher_id') ?: null;
        $selectedStaffId = $selectedTeacherId ? null : ($request->integer('staff_member_id') ?: null);
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

        return view('admin.users.form', [
            'item' => new User([
                'name' => $selectedTeacher?->nama ?? $selectedStaff?->nama,
                'email' => $selectedTeacher?->email ?? $selectedStaff?->email,
                'telepon' => $selectedTeacher?->telepon ?? $selectedStaff?->telepon,
                'role' => $selectedTeacherId ? 'guru' : 'staf',
                'is_active' => true,
            ]),
            'teachers' => $this->teacherOptions(),
            'selectedTeacherId' => $selectedTeacherId,
            'staffMembers' => $this->staffOptions(),
            'selectedStaffId' => $selectedStaffId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(User::ROLES)],
            'telepon' => ['nullable', 'string', 'max:30'],
            'teacher_id' => ['nullable', 'integer', Rule::prohibitedIf($request->filled('staff_member_id')), 'exists:teachers,id', Rule::unique('teachers', 'id')->whereNotNull('user_id')],
            'staff_member_id' => ['nullable', 'integer', Rule::prohibitedIf($request->filled('teacher_id')), 'exists:staff_members,id', Rule::unique('staff_members', 'id')->whereNotNull('user_id')],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $teacherId = $data['teacher_id'] ?? null;
        $staffId = $data['staff_member_id'] ?? null;
        unset($data['teacher_id'], $data['staff_member_id']);
        $data['is_active'] = $request->boolean('is_active');

        $user = DB::transaction(function () use ($data, $teacherId, $staffId) {
            $user = User::create($data);
            if ($teacherId) {
                Teacher::findOrFail($teacherId)->update(['user_id' => $user->id]);
            }
            if ($staffId) {
                StaffMember::findOrFail($staffId)->update(['user_id' => $user->id]);
            }

            return $user;
        });
        SecurityAudit::record('user.created', $request->user(), $request, ['target_user_id' => $user->id]);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'item' => $user->load(['teacher', 'staffMember']),
            'teachers' => $this->teacherOptions($user),
            'selectedTeacherId' => null,
            'staffMembers' => $this->staffOptions($user),
            'selectedStaffId' => null,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(User::ROLES)],
            'telepon' => ['nullable', 'string', 'max:30'],
            'teacher_id' => [
                'nullable', 'integer', Rule::prohibitedIf($request->filled('staff_member_id')), 'exists:teachers,id',
                Rule::unique('teachers', 'id')->whereNotNull('user_id')->ignore($user->teacher?->id),
            ],
            'staff_member_id' => [
                'nullable', 'integer', Rule::prohibitedIf($request->filled('teacher_id')), 'exists:staff_members,id',
                Rule::unique('staff_members', 'id')->whereNotNull('user_id')->ignore($user->staffMember?->id),
            ],
            'password' => ['nullable', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        $teacherId = $data['teacher_id'] ?? null;
        $staffId = $data['staff_member_id'] ?? null;
        unset($data['teacher_id'], $data['staff_member_id']);
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

        $credentialsChanged = array_key_exists('password', $data) || $data['role'] !== $user->role || ! $data['is_active'];
        if (array_key_exists('password', $data)) {
            $data['password_changed_at'] = now();
            $data['remember_token'] = Str::random(60);
        }
        DB::transaction(function () use ($user, $data, $teacherId, $staffId) {
            $user->update($data);
            Teacher::where('user_id', $user->id)->update(['user_id' => null]);
            StaffMember::where('user_id', $user->id)->update(['user_id' => null]);
            if ($teacherId) {
                Teacher::findOrFail($teacherId)->update(['user_id' => $user->id]);
            }
            if ($staffId) {
                StaffMember::findOrFail($staffId)->update(['user_id' => $user->id]);
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
}
