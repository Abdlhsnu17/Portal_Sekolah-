<?php

namespace App\Http\Controllers;

use App\Models\GuardianMessage;
use App\Models\Student;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;

class GuardianMessageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isTeacher() || $user->isGuardian(), 403);
        $studentIds = $user->isGuardian() ? $user->students()->pluck('students.id') : $this->teacherStudentIds($user->teacher->id);
        $messages = GuardianMessage::where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))->whereIn('student_id', $studentIds)->with(['student', 'sender', 'recipient'])->latest()->get();
        GuardianMessage::where('recipient_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
        $recipients = $user->isGuardian() ? User::where('role', 'guru')->where('is_active', true)->orderBy('name')->get() : User::where('role', 'wali')->whereHas('students', fn ($q) => $q->whereIn('students.id', $studentIds))->orderBy('name')->get();
        return view('messages.index', ['messages' => $messages, 'students' => Student::whereIn('id', $studentIds)->orderBy('nama')->get(), 'recipients' => $recipients, 'isTeacher' => $user->isTeacher()]);
    }

    public function store(Request $request)
    {
        $user = $request->user(); abort_unless($user->isTeacher() || $user->isGuardian(), 403);
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'recipient_id' => ['required', 'exists:users,id'], 'subject' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:5000']]);
        $student = Student::findOrFail($data['student_id']);
        $allowed = $user->isGuardian() ? $user->students()->whereKey($student->id)->exists() : $this->teacherStudentIds($user->teacher->id)->contains($student->id);
        $recipientAllowed = $user->isGuardian() ? User::whereKey($data['recipient_id'])->where('role', 'guru')->where('is_active', true)->exists() : User::whereKey($data['recipient_id'])->where('role', 'wali')->whereHas('students', fn ($q) => $q->whereKey($student->id))->exists();
        abort_unless($allowed && $recipientAllowed, 403);
        GuardianMessage::create($data + ['sender_id' => $user->id]);
        User::findOrFail($data['recipient_id'])->notify(new ActivityNotification(['title' => 'Pesan baru', 'message' => $user->name.' mengirim pesan tentang '.$student->nama.': '.$data['subject'], 'url' => route('messages.index'), 'type' => 'message']));
        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    private function teacherStudentIds(int $teacherId)
    {
        $assignments = \App\Models\TeachingAssignment::where('teacher_id', $teacherId)->get(['kelas', 'tahun_ajaran']);
        return Student::whereHas('enrollments', function ($q) use ($assignments) { $q->where('status', 'aktif')->whereHas('schoolClass', function ($classes) use ($assignments) { $classes->where(function ($inner) use ($assignments) { foreach ($assignments as $assignment) $inner->orWhere(fn ($c) => $c->where('name', $assignment->kelas)->whereHas('academicYear', fn ($year) => $year->where('name', $assignment->tahun_ajaran))); }); }); })->pluck('id');
    }
}
