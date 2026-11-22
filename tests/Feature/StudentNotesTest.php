<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentNote;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_keep_notes_private_then_publish_them_to_the_student_and_guardian(): void
    {
        [$teacherUser, , , $student, $studentUser, $guardianUser] = $this->makeTeachingContext();

        $this->actingAs($teacherUser)
            ->post(route('teaching.notes.store'), [
                'student_id' => $student->id,
                'title' => 'Catatan perkembangan',
                'content' => 'Perlu lebih percaya diri saat presentasi.',
            ])
            ->assertRedirect(route('teaching.notes.index'));

        $note = StudentNote::firstOrFail();
        $this->assertNull($note->published_at);

        $this->actingAs($studentUser)
            ->get(route('student.notes'))
            ->assertOk()
            ->assertDontSee('Catatan perkembangan');

        $this->actingAs($guardianUser)
            ->get(route('guardian.notes'))
            ->assertOk()
            ->assertDontSee('Catatan perkembangan');

        $this->actingAs($teacherUser)
            ->put(route('teaching.notes.update', $note), [
                'title' => 'Catatan perkembangan',
                'content' => 'Perlu lebih percaya diri saat presentasi.',
                'publish' => '1',
            ])
            ->assertRedirect(route('teaching.notes.index'));

        $this->assertNotNull($note->fresh()->published_at);

        $this->actingAs($studentUser)
            ->get(route('student.notes'))
            ->assertOk()
            ->assertSee('Catatan perkembangan')
            ->assertSee('Perlu lebih percaya diri saat presentasi.');

        $this->actingAs($guardianUser)
            ->get(route('guardian.notes'))
            ->assertOk()
            ->assertSee('Catatan perkembangan')
            ->assertSee('Perlu lebih percaya diri saat presentasi.');

        $this->actingAs($teacherUser)
            ->delete(route('teaching.notes.destroy', $note))
            ->assertRedirect(route('teaching.notes.index'));

        $this->assertDatabaseMissing('student_notes', ['id' => $note->id]);
    }

    public function test_teacher_can_only_create_notes_for_students_in_an_assigned_class(): void
    {
        [$teacherUser] = $this->makeTeachingContext();
        $unassignedStudent = Student::create(['nis' => 'NIS-UNASSIGNED', 'nama' => 'Siswa Lain']);

        $this->actingAs($teacherUser)
            ->post(route('teaching.notes.store'), [
                'student_id' => $unassignedStudent->id,
                'title' => 'Catatan tidak sah',
                'content' => 'Tidak boleh disimpan.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('student_notes', 0);
    }

    public function test_teacher_and_student_have_separate_full_schedule_pages_and_short_dashboard_previews(): void
    {
        [$teacherUser, $teacher, $schoolClass, , $studentUser, , $subject] = $this->makeTeachingContext();

        foreach (range(1, 4) as $day) {
            ClassSchedule::create([
                'school_class_id' => $schoolClass->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'day_of_week' => $day,
                'starts_at' => '08:00:00',
                'ends_at' => '09:00:00',
                'room' => 'Ruang '.$day,
            ]);
        }

        $this->actingAs($teacherUser)
            ->get(route('teaching.index'))
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules) => $schedules->count() === 3)
            ->assertSee('Lihat semua');

        $this->get(route('teaching.schedule'))
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules) => $schedules->count() === 4);

        $this->actingAs($studentUser)
            ->get(route('student.index'))
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules) => $schedules->count() === 3)
            ->assertSee('Lihat semua');

        $this->get(route('student.schedule'))
            ->assertOk()
            ->assertViewHas('schedules', fn ($schedules) => $schedules->count() === 4);
    }

    private function makeTeachingContext(): array
    {
        $teacherUser = User::factory()->create(['role' => 'guru']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => 'NIP-'.fake()->unique()->numerify('#####'),
            'nama' => 'Guru Pengampu',
            'status' => 'aktif',
        ]);
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'starts_on' => '2026-07-01',
            'ends_on' => '2027-06-30',
            'is_active' => true,
        ]);
        $schoolClass = SchoolClass::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'X-A',
        ]);
        $subject = Subject::create([
            'kode' => 'MAT-'.fake()->unique()->numerify('###'),
            'nama' => 'Matematika',
            'is_active' => true,
        ]);
        TeachingAssignment::create([
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'kelas' => $schoolClass->name,
            'tahun_ajaran' => $academicYear->name,
        ]);
        $student = Student::create(['nis' => 'NIS-'.fake()->unique()->numerify('#####'), 'nama' => 'Siswa Terdaftar']);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'school_class_id' => $schoolClass->id,
            'status' => 'aktif',
        ]);
        $studentUser = User::factory()->create(['role' => 'siswa']);
        $student->update(['user_id' => $studentUser->id]);
        $guardianUser = User::factory()->create(['role' => 'wali']);
        $guardianUser->students()->attach($student->id);

        return [$teacherUser, $teacher, $schoolClass, $student, $studentUser, $guardianUser, $subject];
    }
}
