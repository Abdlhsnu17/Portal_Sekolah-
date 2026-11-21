<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StaffMember;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $this->validateFilters($request);
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $query = $this->filteredQuery($request, $from, $to);

        $summary = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.attendances.index', [
            'items' => $query->orderByDesc('tanggal')->orderByDesc('jam_masuk')->paginate(15)->withQueryString(),
            'summary' => $summary,
            'statuses' => Attendance::STATUSES,
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }

    public function export(Request $request)
    {
        $this->validateFilters($request);
        $from = $request->input('date_from', now()->startOfMonth()->toDateString());
        $to = $request->input('date_to', now()->toDateString());
        $filename = "rekap-absensi-{$from}-sampai-{$to}.csv";

        return response()->streamDownload(function () use ($request, $from, $to) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Tanggal', 'NIP', 'Nama', 'Jenis', 'Jam Masuk', 'Jam Keluar', 'Status', 'Verifikasi Wajah', 'Catatan Verifikasi', 'Diverifikasi Oleh', 'Catatan', 'Dicatat Oleh']);

            $this->filteredQuery($request, $from, $to)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->chunk(500, function ($items) use ($output) {
                    foreach ($items as $item) {
                        $person = $item->teacher ?? $item->staffMember;
                        fputcsv($output, [
                            $item->tanggal->format('Y-m-d'),
                            $this->csvCell($person?->nip),
                            $this->csvCell($item->person_name),
                            $item->person_type,
                            $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '',
                            $item->jam_keluar ? substr($item->jam_keluar, 0, 5) : '',
                            ucfirst($item->status),
                            $item->face_review_status ? ucfirst($item->face_review_status) : '',
                            $this->csvCell($item->face_review_note),
                            $this->csvCell($item->faceReviewer?->name),
                            $this->csvCell($item->catatan),
                            $this->csvCell($item->recorder?->name),
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        return view('admin.attendances.form', [
            'item' => new Attendance(['tanggal' => now(), 'status' => 'hadir']),
            'people' => $this->peopleOptions(),
        ]);
    }

    public function store(Request $request)
    {
        Attendance::create($this->validated($request) + ['recorded_by' => $request->user()->id]);

        return redirect()->route('admin.attendances.index')->with('success', 'Absensi berhasil dicatat.');
    }

    public function edit(Attendance $attendance)
    {
        return view('admin.attendances.form', [
            'item' => $attendance,
            'people' => $this->peopleOptions(),
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $attendance->update($this->validated($request, $attendance) + ['recorded_by' => $request->user()->id]);

        return redirect()->route('admin.attendances.index')->with('success', 'Absensi berhasil diperbarui.');
    }

    public function destroy(Attendance $attendance)
    {
        if ($attendance->face_photo_path) {
            Storage::disk('local')->delete($attendance->face_photo_path);
        }
        $attendance->delete();

        return back()->with('success', 'Catatan absensi dihapus.');
    }

    public function facePhoto(Attendance $attendance)
    {
        abort_unless($attendance->face_photo_path && Storage::disk('local')->exists($attendance->face_photo_path), 404);

        return Storage::disk('local')->response($attendance->face_photo_path);
    }

    public function reviewFace(Request $request, Attendance $attendance)
    {
        abort_unless($attendance->face_photo_path, 422, 'Absensi ini tidak memiliki bukti foto wajah.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'face_review_note' => [Rule::requiredIf($request->input('decision') === 'rejected'), 'nullable', 'string', 'max:500'],
        ]);

        $attendance->update([
            'status' => $data['decision'] === 'verified' ? 'hadir' : 'alpa',
            'face_review_status' => $data['decision'],
            'face_review_note' => $data['face_review_note'] ?? null,
            'face_reviewed_at' => now(),
            'face_reviewed_by' => $request->user()->id,
        ]);

        return back()->with('success', $data['decision'] === 'verified' ? 'Bukti wajah diverifikasi.' : 'Bukti wajah ditolak.');
    }

    private function validated(Request $request, ?Attendance $attendance = null): array
    {
        $data = $request->validate([
            'person' => ['required', 'regex:/^(teacher|staff):[1-9][0-9]*$/'],
            'tanggal' => ['required', 'date'],
            'jam_masuk' => [Rule::requiredIf($request->input('status') === 'hadir'), 'nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i', 'after_or_equal:jam_masuk'],
            'status' => ['required', Rule::in(Attendance::STATUSES)],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        [$type, $id] = explode(':', $data['person']);
        $model = $type === 'teacher' ? Teacher::find($id) : StaffMember::find($id);
        if (! $model) {
            throw ValidationException::withMessages(['person' => 'Pegawai yang dipilih tidak ditemukan.']);
        }

        $foreignKey = $type === 'teacher' ? 'teacher_id' : 'staff_member_id';
        $duplicate = Attendance::where($foreignKey, $id)->whereDate('tanggal', $data['tanggal'])
            ->when($attendance, fn ($query) => $query->where('id', '!=', $attendance->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['person' => 'Absensi pegawai pada tanggal tersebut sudah tercatat.']);
        }

        unset($data['person']);
        if ($data['status'] !== 'hadir') {
            $data['jam_masuk'] = null;
            $data['jam_keluar'] = null;
        }
        $data['teacher_id'] = $type === 'teacher' ? (int) $id : null;
        $data['staff_member_id'] = $type === 'staff' ? (int) $id : null;

        return $data;
    }

    private function peopleOptions(): array
    {
        $teachers = Teacher::where('status', 'aktif')->orderBy('nama')->get()
            ->mapWithKeys(fn (Teacher $teacher) => ["teacher:{$teacher->id}" => "Guru — {$teacher->nama} ({$teacher->nip})"]);
        $staff = StaffMember::where('status', 'aktif')->orderBy('nama')->get()
            ->mapWithKeys(fn (StaffMember $member) => ["staff:{$member->id}" => "Staf — {$member->nama} ({$member->nip})"]);

        return $teachers->merge($staff)->all();
    }

    private function filteredQuery(Request $request, string $from, string $to): Builder
    {
        $status = in_array($request->input('status'), Attendance::STATUSES, true) ? $request->input('status') : null;
        $type = in_array($request->input('type'), ['teacher', 'staff'], true) ? $request->input('type') : null;

        return Attendance::with(['teacher', 'staffMember', 'recorder', 'faceReviewer'])
            ->whereDate('tanggal', '>=', $from)
            ->whereDate('tanggal', '<=', $to)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($type === 'teacher', fn ($query) => $query->whereNotNull('teacher_id'))
            ->when($type === 'staff', fn ($query) => $query->whereNotNull('staff_member_id'))
            ->when($request->input('q'), function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->whereHas('teacher', fn ($teacher) => $teacher->where('nama', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%"))
                        ->orWhereHas('staffMember', fn ($staff) => $staff->where('nama', 'like', "%{$term}%")->orWhere('nip', 'like', "%{$term}%"));
                });
            });
    }

    private function csvCell(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[=+\-@]/', $value) ? "'{$value}" : $value;
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'type' => ['nullable', Rule::in(['teacher', 'staff'])],
            'status' => ['nullable', Rule::in(Attendance::STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
