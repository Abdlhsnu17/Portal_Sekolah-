<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Support\SecurityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceCheckInController extends Controller
{
    public function create(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $attendance = Attendance::query()
            ->where($type === 'teacher' ? 'teacher_id' : 'staff_member_id', $person->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        $upcomingRequests = Attendance::query()
            ->where($type === 'teacher' ? 'teacher_id' : 'staff_member_id', $person->id)
            ->whereNotNull('requested_by')
            ->whereDate('tanggal', '>', now()->toDateString())
            ->whereIn('status', ['izin', 'sakit', 'dinas'])
            ->orderBy('tanggal')
            ->limit(10)
            ->get();

        $start = now()->startOfMonth();
        $records = Attendance::query()
            ->where($type === 'teacher' ? 'teacher_id' : 'staff_member_id', $person->id)
            ->whereBetween('tanggal', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->get()
            ->keyBy(fn (Attendance $record) => $record->tanggal->toDateString());
        $days = collect(range(1, $start->daysInMonth))->map(function (int $day) use ($start, $records) {
            $date = $start->copy()->day($day);

            return ['date' => $date, 'attendance' => $records->get($date->toDateString())];
        })->reverse()->values();

        return view('attendance.check-in', compact('type', 'person', 'attendance', 'start', 'days', 'upcomingRequests'));
    }

    public function history(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $month = $request->query('bulan', now()->format('Y-m'));
        if (! preg_match('/^\\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = now()->format('Y-m');
        }

        $start = Carbon::createFromFormat('!Y-m', $month);
        $attendances = Attendance::query()
            ->where($type === 'teacher' ? 'teacher_id' : 'staff_member_id', $person->id)
            ->whereBetween('tanggal', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()])
            ->orderByDesc('tanggal')
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->tanggal->toDateString());

        $days = collect(range(1, $start->daysInMonth))->map(function (int $day) use ($start, $attendances) {
            $date = $start->copy()->day($day);

            return [
                'date' => $date,
                'attendance' => $attendances->get($date->toDateString()),
            ];
        })->reverse()->values();

        return view('attendance.history', compact('type', 'person', 'month', 'start', 'days'));
    }

    public function store(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $data = $this->validatedFacePhoto($request, 'Ambil foto wajah terlebih dahulu.');

        $foreignKey = $type === 'teacher' ? 'teacher_id' : 'staff_member_id';
        $existing = Attendance::where($foreignKey, $person->id)->whereDate('tanggal', now()->toDateString())->first();
        if ($existing && $existing->face_review_status !== 'rejected') {
            throw ValidationException::withMessages(['face_photo' => 'Absensi Anda hari ini sudah tercatat.']);
        }

        $extension = $data['face_photo']->extension() === 'png' ? 'png' : 'jpg';
        $path = $data['face_photo']->storeAs(
            'attendance-faces/'.now()->format('Y/m'),
            Str::uuid().'.'.$extension,
            'local',
        );

        try {
            $oldPath = null;
            DB::transaction(function () use ($type, $person, $foreignKey, $path, $request, &$oldPath) {
                $person->newQuery()->whereKey($person->id)->lockForUpdate()->firstOrFail();
                $existing = Attendance::where($foreignKey, $person->id)->whereDate('tanggal', now()->toDateString())->first();
                if ($existing && $existing->face_review_status !== 'rejected') {
                    throw ValidationException::withMessages(['face_photo' => 'Absensi Anda hari ini sudah tercatat.']);
                }

                $attendanceData = [
                    'teacher_id' => $type === 'teacher' ? $person->id : null,
                    'staff_member_id' => $type === 'staff' ? $person->id : null,
                    'tanggal' => now()->toDateString(),
                    'jam_masuk' => $existing?->jam_masuk ?? now()->format('H:i'),
                    'status' => 'hadir',
                    'face_photo_path' => $path,
                    'face_review_status' => 'pending',
                    'face_review_note' => null,
                    'face_reviewed_at' => null,
                    'face_reviewed_by' => null,
                    'catatan' => 'Absensi mandiri dengan bukti foto wajah.',
                    'recorded_by' => $request->user()->id,
                ];

                if ($existing) {
                    $oldPath = $existing->face_photo_path;
                    $existing->update($attendanceData);
                } else {
                    Attendance::create($attendanceData);
                }
            });
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->to(route('attendance.check-in').'#riwayat')->with('success', 'Kehadiran Anda berhasil dicatat.');
    }

    public function checkOut(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $data = $this->validatedFacePhoto($request, 'Ambil foto wajah saat absen pulang.');
        $foreignKey = $type === 'teacher' ? 'teacher_id' : 'staff_member_id';

        $attendance = Attendance::query()
            ->where($foreignKey, $person->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        if (! $attendance || $attendance->status !== 'hadir' || ! $attendance->jam_masuk) {
            throw ValidationException::withMessages([
                'check_out' => 'Absensi masuk hari ini belum tersedia untuk dilengkapi.',
            ]);
        }

        if ($attendance->face_review_status === 'rejected') {
            throw ValidationException::withMessages([
                'check_out' => 'Bukti wajah ditolak. Ambil foto masuk ulang sebelum mencatat jam pulang.',
            ]);
        }

        if ($attendance->jam_keluar) {
            throw ValidationException::withMessages([
                'check_out' => 'Jam pulang hari ini sudah tercatat.',
            ]);
        }

        $extension = $data['face_photo']->extension() === 'png' ? 'png' : 'jpg';
        $path = $data['face_photo']->storeAs(
            'attendance-faces/'.now()->format('Y/m'),
            Str::uuid().'-pulang.'.$extension,
            'local',
        );

        try {
            $oldPath = $attendance->face_checkout_photo_path;
            $attendance->update([
                'jam_keluar' => now()->format('H:i'),
                'face_checkout_photo_path' => $path,
            ]);
            if ($oldPath) {
                Storage::disk('local')->delete($oldPath);
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->to(route('attendance.check-in').'#riwayat')->with('success', 'Jam pulang berhasil dicatat.');
    }

    public function correct(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $data = $request->validate([
            'jam_masuk' => ['required', 'date_format:H:i'],
            'alasan_koreksi' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'jam_masuk.required' => 'Jam masuk koreksi wajib diisi.',
            'alasan_koreksi.required' => 'Tuliskan alasan koreksi.',
            'alasan_koreksi.min' => 'Alasan koreksi minimal 5 karakter.',
        ]);

        $foreignKey = $type === 'teacher' ? 'teacher_id' : 'staff_member_id';
        $attendance = Attendance::query()
            ->where($foreignKey, $person->id)
            ->whereDate('tanggal', now()->toDateString())
            ->firstOrFail();

        $oldTime = substr((string) $attendance->jam_masuk, 0, 5);
        $attendance->update(['jam_masuk' => $data['jam_masuk']]);

        SecurityAudit::record('attendance.check_in_corrected', $request->user(), $request, [
            'attendance_id' => $attendance->id,
            'tanggal' => $attendance->tanggal->toDateString(),
            'jam_masuk_sebelumnya' => $oldTime,
            'jam_masuk_baru' => $data['jam_masuk'],
            'alasan' => $data['alasan_koreksi'],
        ]);

        return redirect()->to(route('attendance.check-in').'#absen')->with('success', 'Koreksi jam masuk berhasil disimpan.');
    }

    public function requestAbsence(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'status' => ['required', 'in:izin,sakit,dinas'],
            'catatan' => ['required', 'string', 'min:5', 'max:1000'],
            'bukti' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'catatan.required' => 'Alasan pengajuan wajib diisi.',
            'catatan.min' => 'Alasan pengajuan minimal 5 karakter.',
            'bukti.mimes' => 'Bukti harus berupa PDF atau gambar JPG/PNG.',
        ]);

        $foreignKey = $type === 'teacher' ? 'teacher_id' : 'staff_member_id';
        $existing = Attendance::query()->where($foreignKey, $person->id)->whereDate('tanggal', $data['tanggal'])->first();
        if ($existing && $existing->approval_status !== 'rejected') {
            throw ValidationException::withMessages(['tanggal' => 'Sudah ada catatan absensi atau pengajuan untuk tanggal tersebut.']);
        }

        $path = $data['bukti']?->store('attendance-requests/'.now()->format('Y/m'), 'local');
        try {
            DB::transaction(function () use ($person, $foreignKey, $data, $path, $request, $existing) {
                $person->newQuery()->whereKey($person->id)->lockForUpdate()->firstOrFail();
                $current = Attendance::query()->where($foreignKey, $person->id)->whereDate('tanggal', $data['tanggal'])->lockForUpdate()->first();
                if ($current && $current->approval_status !== 'rejected') {
                    throw ValidationException::withMessages(['tanggal' => 'Sudah ada catatan absensi atau pengajuan untuk tanggal tersebut.']);
                }

                $attributes = [
                    'status' => $data['status'],
                    'approval_status' => 'pending',
                    'approval_note' => null,
                    'request_evidence_path' => $path,
                    'requested_by' => $request->user()->id,
                    'request_reviewed_by' => null,
                    'request_reviewed_at' => null,
                    'jam_masuk' => null,
                    'jam_keluar' => null,
                    'catatan' => $data['catatan'],
                    'recorded_by' => $request->user()->id,
                ];

                if ($current) {
                    $current->update($attributes);
                } else {
                    $attributes[$foreignKey] = $person->id;
                    $attributes['tanggal'] = $data['tanggal'];
                    Attendance::create($attributes);
                }
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        if ($existing?->request_evidence_path && $existing->request_evidence_path !== $path) {
            Storage::disk('local')->delete($existing->request_evidence_path);
        }

        return redirect()->to(route('attendance.check-in').'#pengajuan')->with('success', 'Pengajuan absensi terkirim dan menunggu persetujuan admin.');
    }

    private function personFor(Request $request): array
    {
        $user = $request->user()->loadMissing(['teacher', 'staffMember']);
        if ($user->teacher && $user->teacher->status === 'aktif') {
            return ['teacher', $user->teacher];
        }
        if ($user->staffMember && $user->staffMember->status === 'aktif') {
            return ['staff', $user->staffMember];
        }

        abort(403, 'Akun Anda belum terhubung dengan data guru atau staf aktif.');
    }

    private function validatedFacePhoto(Request $request, string $requiredMessage): array
    {
        return $request->validate([
            'face_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'consent' => ['accepted'],
        ], [
            'face_photo.required' => $requiredMessage,
            'consent.accepted' => 'Persetujuan penyimpanan foto wajah wajib diberikan.',
        ]);
    }
}
