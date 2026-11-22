<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AcademicStructureController extends Controller
{
    public function index()
    {
        return view('admin.academic-structure.index', [
            'years' => AcademicYear::withCount('schoolClasses')->latest('starts_on')->get(),
            'classes' => SchoolClass::with(['academicYear', 'homeroomTeacher'])->withCount('enrollments')
                ->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'teachers' => Teacher::where('status', 'aktif')->orderBy('nama')->get(),
            'students' => Student::where('status', 'aktif')->orderBy('nama')->get(),
            'enrollments' => StudentEnrollment::with(['student', 'schoolClass.academicYear'])
                ->latest('enrolled_on')->latest('id')->get(),
        ]);
    }

    public function storeYear(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'unique:academic_years,name'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['is_active'])) {
                AcademicYear::query()->update(['is_active' => false]);
            }
            AcademicYear::create($data);
        });

        return back()->with('success', 'Tahun ajaran ditambahkan.');
    }

    public function activateYear(AcademicYear $academicYear)
    {
        DB::transaction(function () use ($academicYear) {
            AcademicYear::query()->update(['is_active' => false]);
            $academicYear->update(['is_active' => true]);
        });

        return back()->with('success', "{$academicYear->name} menjadi tahun ajaran aktif.");
    }

    public function updateYear(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'name' => ['required', 'regex:/^\d{4}\/\d{4}$/', Rule::unique('academic_years', 'name')->ignore($academicYear)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $academicYear) {
            if (! empty($data['is_active'])) {
                AcademicYear::query()->whereKeyNot($academicYear)->update(['is_active' => false]);
            }
            $academicYear->update($data);
        });

        return redirect()->route('admin.academic-structure.index', ['edit_year' => $academicYear->id])
            ->with('success', 'Tahun ajaran diperbarui.');
    }

    public function destroyYear(AcademicYear $academicYear)
    {
        if ($academicYear->is_active) {
            return back()->withErrors(['academic_year' => 'Tahun ajaran aktif tidak dapat dihapus. Aktifkan tahun ajaran lain terlebih dahulu.']);
        }

        $academicYear->delete();

        return back()->with('success', 'Tahun ajaran beserta rombel terkait dihapus.');
    }

    public function storeClass(Request $request)
    {
        SchoolClass::create($this->classData($request));

        return back()->with('success', 'Rombel ditambahkan.');
    }

    public function updateClass(Request $request, SchoolClass $schoolClass)
    {
        $schoolClass->update($this->classData($request, $schoolClass));

        return redirect()->route('admin.academic-structure.index', ['edit_class' => $schoolClass->id])
            ->with('success', 'Rombel diperbarui.');
    }

    public function destroyClass(SchoolClass $schoolClass)
    {
        $schoolClass->delete();

        return back()->with('success', 'Rombel dan data penempatannya dihapus.');
    }

    public function enroll(Request $request)
    {
        $data = $this->enrollmentData($request);
        $this->ensureClassHasCapacity($data['school_class_id']);

        StudentEnrollment::updateOrCreate(
            ['student_id' => $data['student_id'], 'school_class_id' => $class->id],
            ['status' => 'aktif', 'enrolled_on' => $data['enrolled_on'] ?? now()->toDateString()],
        );

        return back()->with('success', 'Siswa ditempatkan pada rombel.');
    }

    public function updateEnrollment(Request $request, StudentEnrollment $studentEnrollment)
    {
        $data = $this->enrollmentData($request, $studentEnrollment);
        $this->ensureClassHasCapacity($data['school_class_id'], $studentEnrollment);

        $studentEnrollment->update([
            ...$data,
            'enrolled_on' => $data['enrolled_on'] ?? now()->toDateString(),
        ]);

        return redirect()->route('admin.academic-structure.index', ['tab' => 'penempatan', 'edit_enrollment' => $studentEnrollment->id])
            ->with('success', 'Penempatan siswa diperbarui.');
    }

    public function destroyEnrollment(StudentEnrollment $studentEnrollment)
    {
        $studentEnrollment->delete();

        return back()->with('success', 'Penempatan siswa dihapus.');
    }

    private function classData(Request $request, ?SchoolClass $schoolClass = null): array
    {
        return $request->validate([
            'academic_year_id' => ['required', Rule::exists('academic_years', 'id')],
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('school_classes', 'name')
                    ->where(fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id')))
                    ->ignore($schoolClass),
            ],
            'grade_level' => ['nullable', 'string', 'max:20'],
            'major' => ['nullable', 'string', 'max:100'],
            'homeroom_teacher_id' => ['nullable', Rule::exists('teachers', 'id')->where('status', 'aktif')],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function enrollmentData(Request $request, ?StudentEnrollment $studentEnrollment = null): array
    {
        return $request->validate([
            'student_id' => [
                'required', Rule::exists('students', 'id'),
                ...($studentEnrollment ? [
                    Rule::unique('student_enrollments', 'student_id')
                        ->where(fn ($query) => $query->where('school_class_id', $request->input('school_class_id')))
                        ->ignore($studentEnrollment),
                ] : []),
            ],
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')],
            'enrolled_on' => ['nullable', 'date'],
        ]);
    }

    private function ensureClassHasCapacity(int|string $classId, ?StudentEnrollment $except = null): void
    {
        $class = SchoolClass::findOrFail($classId);
        $used = $class->enrollments()->where('status', 'aktif')
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))->count();

        if ($class->capacity && $used >= $class->capacity) {
            throw ValidationException::withMessages([
                'school_class_id' => 'Kapasitas rombel sudah penuh.',
            ]);
        }
    }
}
