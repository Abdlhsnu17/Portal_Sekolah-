<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Registration;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;

class StaffDashboardController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'staf', 403);

        return view('staff.dashboard', [
            'statistics' => [
                'pendingRegistrations' => Registration::where('status', 'menunggu')->count(),
                'activeStudents' => Student::where('status', 'aktif')->count(),
                'activeTeachers' => Teacher::where('status', 'aktif')->count(),
                'announcements' => Announcement::count(),
            ],
            'recentRegistrations' => Registration::latest()->take(5)->get(),
        ]);
    }
}
