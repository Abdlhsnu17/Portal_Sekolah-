<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassScheduleController extends Controller
{
    public function index(Request $request)
    {
        $classId = $request->integer('school_class_id');

        return view('admin.class-schedules.index', [
            'schedules' => ClassSchedule::with(['schoolClass.academicYear', 'teacher', 'subject'])
                ->when($classId, fn ($query) => $query->where('school_class_id', $classId))
                ->orderBy('day_of_week')->orderBy('starts_at')->get(),
            'classes' => SchoolClass::with('academicYear')->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'teachers' => Teacher::where('status', 'aktif')->orderBy('nama')->get(),
            'subjects' => Subject::where('is_active', true)->orderBy('nama')->get(),
            'selectedClassId' => $classId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $this->ensureNoConflict($data);

        ClassSchedule::create($data);

        return back()->with('success', 'Jadwal pelajaran ditambahkan.');
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $data = $this->validated($request);
        $this->ensureNoConflict($data, $classSchedule);
        $classSchedule->update($data);

        return redirect()->route('admin.class-schedules.index', ['school_class_id' => $data['school_class_id'], 'edit' => $classSchedule->id])
            ->with('success', 'Jadwal pelajaran diperbarui.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')],
            'teacher_id' => ['required', Rule::exists('teachers', 'id')->where('status', 'aktif')],
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('is_active', true)],
            'day_of_week' => ['required', Rule::in(array_keys(ClassSchedule::DAYS))],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function ensureNoConflict(array $data, ?ClassSchedule $except = null): void
    {
        $overlap = fn ($query) => $query->where('day_of_week', $data['day_of_week'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        $conflict = ClassSchedule::query()->where($overlap)->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->where(function ($query) use ($data) {
                $query->where('school_class_id', $data['school_class_id'])
                    ->orWhere('teacher_id', $data['teacher_id']);
                if (filled($data['room'] ?? null)) {
                    $query->orWhere('room', $data['room']);
                }
            })->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => 'Jadwal berbenturan dengan rombel, guru, atau ruang pada waktu tersebut.',
            ]);
        }
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        $classSchedule->delete();

        return back()->with('success', 'Jadwal pelajaran dihapus.');
    }
}
