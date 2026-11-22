<?php

namespace App\Services\Admin\Finance;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FinanceDashboardService
{
    public function data(array $filters): array
    {
        $years = AcademicYear::query()->orderByDesc('starts_on')->get();
        $selectedYearId = $filters['academic_year_id'] ?? $years->firstWhere('is_active', true)?->id ?? $years->first()?->id;
        $classes = SchoolClass::query()->with('academicYear')
            ->when($selectedYearId, fn ($query) => $query->where('academic_year_id', $selectedYearId))
            ->orderBy('name')->get();
        $selectedClassId = $filters['school_class_id'] ?? null;

        abort_if($selectedClassId && ! $classes->contains('id', $selectedClassId), 404);

        $students = $this->studentQuery($selectedYearId, $selectedClassId);

        return [
            'invoices' => StudentInvoice::query()->with('student')->withSum('payments', 'amount')
                ->whereIn('student_id', (clone $students)->select('id'))->latest()->paginate(20)->withQueryString(),
            'years' => $years,
            'classes' => $classes,
            'selectedYearId' => $selectedYearId,
            'selectedClassId' => $selectedClassId,
            'filters' => array_filter([
                'academic_year_id' => $selectedYearId,
                'school_class_id' => $selectedClassId,
            ]),
            'categories' => StudentInvoice::CATEGORIES,
        ];
    }

    public function searchStudents(array $filters): LengthAwarePaginator
    {
        // Pembuatan tagihan tidak boleh menyembunyikan siswa aktif hanya karena
        // data penempatan rombel/tahun ajaran mereka belum dilengkapi.
        return Student::query()->where('status', 'aktif')
            ->search($filters['q'] ?? null)
            ->orderBy('nama')
            ->paginate(20, ['id', 'nis', 'nama']);
    }

    private function studentQuery(?int $yearId, ?int $classId)
    {
        return Student::query()->where('status', 'aktif')
            ->when($yearId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                ->where('status', 'aktif')->whereHas('schoolClass', fn ($class) => $class->where('academic_year_id', $yearId))))
            ->when($classId, fn ($query) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment
                ->where('school_class_id', $classId)->where('status', 'aktif')));
    }
}
