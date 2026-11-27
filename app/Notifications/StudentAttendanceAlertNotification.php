<?php

namespace App\Notifications;

use App\Models\StudentAttendance;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentAttendanceAlertNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly StudentAttendance $attendance) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return ['title' => 'Pembaruan absensi siswa', 'message' => $this->attendance->student->nama.' tercatat '.$this->attendance->status.' pada '.$this->attendance->attendance_date->format('d M Y').'.', 'url' => route('guardian.index'), 'type' => 'attendance'];
    }
}
