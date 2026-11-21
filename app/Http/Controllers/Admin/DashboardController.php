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
            // Dashboard cukup menampilkan cuplikan; daftar lengkap tersedia lewat
            // halaman modul agar tinggi layar tetap ringkas.
            'pendaftarTerbaru' => Registration::latest()->take(4)->get(),
            'beritaTerbaru' => Announcement::latest()->take(4)->get(),
        ]);
    }
}
