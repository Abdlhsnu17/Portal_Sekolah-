<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Gallery;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Teacher;

class HomeController extends Controller
{
    public function index()
    {
        return view('public.home', [
            'profil' => SchoolProfile::current(),
            'sorotan' => Announcement::published()->take(3)->get(),
            'galeri' => Gallery::latest()->take(6)->get(),
            'statistik' => [
                'siswa' => Student::where('status', 'aktif')->count(),
                'guru' => Teacher::where('status', 'aktif')->count(),
                'kelas' => Student::whereNotNull('kelas')->distinct()->count('kelas'),
                'prestasi' => Gallery::where('kategori', 'prestasi')->count(),
            ],
        ]);
    }

    public function profil()
    {
        return view('public.profil', [
            'profil' => SchoolProfile::current(),
            'guruTeladan' => Teacher::where('status', 'aktif')->take(4)->get(),
        ]);
    }

    public function berita()
    {
        $kategori = request('kategori');

        return view('public.berita', [
            'profil' => SchoolProfile::current(),
            'items' => Announcement::published()
                ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
                ->when(request('q'), fn ($q, $t) => $q->where('judul', 'like', "%{$t}%"))
                ->paginate(9)
                ->withQueryString(),
            'kategoriAktif' => $kategori,
        ]);
    }

    public function beritaDetail(string $slug)
    {
        $item = Announcement::where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('public.berita-detail', [
            'profil' => SchoolProfile::current(),
            'item' => $item,
            'lainnya' => Announcement::published()->whereKeyNot($item->id)->take(4)->get(),
        ]);
    }

    public function galeri()
    {
        $kategori = request('kategori');

        return view('public.galeri', [
            'profil' => SchoolProfile::current(),
            'items' => Gallery::when($kategori, fn ($q) => $q->where('kategori', $kategori))
                ->latest()->paginate(12)->withQueryString(),
            'kategoriAktif' => $kategori,
        ]);
    }
}
