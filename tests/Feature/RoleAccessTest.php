<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_is_available_only_to_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))
            ->assertOk();

        foreach (['staf', 'guru', 'wali', 'siswa'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.dashboard'))
                ->assertForbidden();
        }
    }

    public function test_staff_has_a_separate_dashboard_and_keeps_access_to_allowed_modules(): void
    {
        $staff = User::factory()->create(['role' => 'staf']);

        $this->actingAs($staff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertViewIs('staff.dashboard')
            ->assertSee('Dashboard Staf');

        $this->get(route('admin.registrations.index'))->assertOk();
        $this->get(route('admin.students.index'))->assertOk();
    }

    public function test_internal_user_can_update_a_student_record(): void
    {
        $staff = User::factory()->create(['role' => 'staf']);
        $student = Student::create(['nis' => 'S-EDIT-001', 'nama' => 'Nama Lama']);

        $this->actingAs($staff)
            ->put(route('admin.students.update', $student), [
                'nis' => 'S-EDIT-001',
                'nama' => 'Nama Baru',
                'jenis_kelamin' => 'P',
                'kelas' => 'X-1',
                'status' => 'aktif',
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'nama' => 'Nama Baru',
            'jenis_kelamin' => 'P',
            'kelas' => 'X-1',
        ]);
    }

    public function test_staff_login_redirects_to_the_staff_dashboard(): void
    {
        $staff = User::factory()->create(['role' => 'staf']);

        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('staff.dashboard'));
    }

    public function test_staff_cannot_open_admin_only_academic_modules(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staf']))
            ->get(route('admin.assessments.index'))
            ->assertForbidden();
    }

    public function test_teacher_can_enter_scores_only_for_an_assigned_class_and_subject(): void
    {
        [$user, $teacher] = $this->makeTeacher('Guru A');
        [$schoolClass, , $assessment, $student] = $this->makeAssignedAssessment($teacher);

        $this->actingAs($user)
            ->get(route('teaching.index'))
            ->assertOk()
            ->assertViewIs('teaching.index');

        $this->actingAs($user)
            ->get(route('teaching.assessments.scores', $assessment))
            ->assertOk();

        $this->put(route('teaching.assessments.scores.store', $assessment), [
            'scores' => [$student->id => ['score' => 88, 'note' => 'Baik']],
        ])->assertRedirect();

        $this->assertDatabaseHas('student_scores', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 88,
        ]);

        [, $otherTeacher] = $this->makeTeacher('Guru B');
        [, , $otherAssessment] = $this->makeAssignedAssessment($otherTeacher, 'X-B', 'IPA');

        $this->actingAs($user)
            ->get(route('teaching.assessments.scores', $otherAssessment))
            ->assertForbidden();
    }

    public function test_teacher_can_record_attendance_only_for_an_assigned_class(): void
    {
        [$user, $teacher] = $this->makeTeacher('Guru Absensi');
        [$schoolClass, , , $student] = $this->makeAssignedAssessment($teacher);

        $this->actingAs($user)
            ->get(route('teaching.attendance', ['school_class_id' => $schoolClass->id]))
            ->assertOk();

        $this->post(route('teaching.attendance.store'), [
            'school_class_id' => $schoolClass->id,
            'attendance_date' => now()->toDateString(),
            'attendance' => [$student->id => ['status' => 'hadir']],
        ])->assertRedirect();

        $this->assertDatabaseHas('student_attendances', [
            'school_class_id' => $schoolClass->id,
            'student_id' => $student->id,
            'status' => 'hadir',
        ]);

        $unassignedClass = SchoolClass::create([
            'academic_year_id' => $schoolClass->academic_year_id,
            'name' => 'X-TANPA-TUGAS',
        ]);

        $this->get(route('teaching.attendance', ['school_class_id' => $unassignedClass->id]))
            ->assertForbidden();
    }

    public function test_student_account_only_opens_its_linked_student_portal(): void
    {
        $user = User::factory()->create(['role' => 'siswa']);
        [, $teacher] = $this->makeTeacher('Guru Siswa');
        [, , $assessment, $student] = $this->makeAssignedAssessment($teacher);
        $student->update(['user_id' => $user->id]);
        $otherStudent = Student::create(['nis' => 'S260002', 'nama' => 'Siswa Lain']);
        StudentScore::create(['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 91]);
        StudentScore::create(['assessment_id' => $assessment->id, 'student_id' => $otherStudent->id, 'score' => 42]);

        $this->actingAs($user)
            ->get(route('student.index'))
            ->assertOk()
            ->assertViewHas('student', fn (Student $shownStudent) => $shownStudent->is($student))
            ->assertViewHas('scores', fn ($scores) => $scores->count() === 1 && $scores->first()->student_id === $student->id);

        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_guardian_has_a_separate_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'wali']))
            ->get(route('guardian.index'))
            ->assertOk()
            ->assertViewIs('guardian.index')
            ->assertSee('Dashboard Wali');
    }

    public function test_admin_can_start_a_student_account_from_an_unlinked_student_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['nis' => 'S260003', 'nama' => 'Calon Akun Siswa']);

        $this->actingAs($admin)
            ->get(route('admin.users.create', ['student_id' => $student->id]))
            ->assertOk()
            ->assertSee('Data Siswa Terkait')
            ->assertSee('Calon Akun Siswa');
    }

    public function test_admin_can_create_and_link_a_student_account(): void
    {
        Http::fake(['*' => Http::response('', 200)]);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['nis' => 'S260004', 'nama' => 'Siswa Baru']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Akun Siswa Baru',
                'email' => 'siswa.baru@example.test',
                'role' => 'siswa',
                'student_id' => $student->id,
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $account = User::where('email', 'siswa.baru@example.test')->firstOrFail();
        $this->assertSame('siswa', $account->role);
        $this->assertSame($account->id, $student->fresh()->user_id);
    }

    public function test_admin_can_reset_the_password_for_every_account_role_from_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (User::ROLES as $role) {
            $account = User::factory()->create(['role' => $role, 'password' => 'OldPassword123!']);

            $this->actingAs($admin)
                ->get(route('admin.users.password.edit', $account))
                ->assertOk()
                ->assertSee('Ganti kata sandi');

            $this->put(route('admin.users.password.update', $account), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])->assertRedirect(route('admin.users.index'));

            $this->assertTrue(Hash::check('NewPassword123!', $account->fresh()->password));
            $this->assertNotNull($account->fresh()->password_changed_at);
        }
    }

    public function test_non_admin_cannot_reset_a_user_password(): void
    {
        $account = User::factory()->create(['role' => 'siswa']);

        $this->actingAs(User::factory()->create(['role' => 'guru']))
            ->put(route('admin.users.password.update', $account), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])->assertForbidden();
    }

    private function makeTeacher(string $name): array
    {
        $user = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::create([
            'user_id' => $user->id,
            'nip' => 'NIP-'.str_replace(' ', '-', $name),
            'nama' => $name,
            'status' => 'aktif',
        ]);

        return [$user, $teacher];
    }

    private function makeAssignedAssessment(Teacher $teacher, string $className = 'X-A', string $subjectName = 'Matematika'): array
    {
        $academicYear = AcademicYear::firstOrCreate(
            ['name' => '2026/2027'],
            ['starts_on' => '2026-07-01', 'ends_on' => '2027-06-30', 'is_active' => true],
        );
        $schoolClass = SchoolClass::create([
            'academic_year_id' => $academicYear->id,
            'name' => $className,
        ]);
        $subject = Subject::create([
            'kode' => 'SUB-'.strtoupper(str_replace(' ', '-', $subjectName)),
            'nama' => $subjectName,
            'is_active' => true,
        ]);
        TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'kelas' => $schoolClass->name,
            'tahun_ajaran' => $academicYear->name,
        ]);
        $assessment = Assessment::create([
            'school_class_id' => $schoolClass->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'title' => 'Penilaian '.$className,
            'type' => 'tugas',
            'maximum_score' => 100,
            'assessment_date' => now()->toDateString(),
        ]);
        $student = Student::create([
            'nis' => 'NIS-'.str_replace('-', '', $className),
            'nama' => 'Siswa '.$className,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);

        return [$schoolClass, $subject, $assessment, $student];
    }
}
