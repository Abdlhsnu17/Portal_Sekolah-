<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
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

        return view('attendance.check-in', compact('type', 'person', 'attendance'));
    }

    public function store(Request $request)
    {
        [$type, $person] = $this->personFor($request);
        $data = $request->validate([
            'face_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'consent' => ['accepted'],
        ], [
            'face_photo.required' => 'Ambil foto wajah terlebih dahulu.',
            'consent.accepted' => 'Persetujuan pengambilan foto wajib diberikan.',
        ]);

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
                    'jam_masuk' => now()->format('H:i'),
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

        return redirect()->route('attendance.check-in')->with('success', 'Kehadiran Anda berhasil dicatat.');
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
}
