<?php

namespace App\Observers;

use App\Models\StudentAttendance;
use App\Notifications\StudentAttendanceAlertNotification;

class StudentAttendanceObserver
{
    public function saved(StudentAttendance $attendance): void
    {
        if (! in_array($attendance->status, ['izin', 'sakit', 'alpa'], true)) return;
        $attendance->loadMissing('student.guardians');
        foreach ($attendance->student->guardians as $guardian) $guardian->notify(new StudentAttendanceAlertNotification($attendance));
    }
}
