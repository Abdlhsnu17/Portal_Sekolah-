<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Gallery;
use App\Models\Registration;
use App\Models\Student;
use App\Models\Teacher;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'statistik' => [
                'siswa' => Student::where('status', 'aktif')->count(),
                'guru' => Teacher::where('status', 'aktif')->count(),
                'pendaftar' => Registration::count(),
                'menunggu' => Registration::where('status', 'menunggu')->count(),
                'berita' => Announcement::count(),
                'galeri' => Gallery::count(),
            ],
            'statusPendaftaran' => collect(Registration::STATUSES)
                ->mapWithKeys(fn ($s) => [$s => Registration::where('status', $s)->count()]),
            'pendaftarTerbaru' => Registration::latest()->take(6)->get(),
            'beritaTerbaru' => Announcement::latest()->take(5)->get(),
        ]);
    }
}
