<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $fallback = $this->homeRoute($request->user());
        $url = (string) ($item->data['url'] ?? $fallback);
        $item->delete();

        return redirect()->to(str_starts_with($url, url('/')) ? $url : $fallback);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Semua pemberitahuan telah dibaca dan dihapus.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->delete();

        return back()->with('success', 'Pemberitahuan berhasil dihapus.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Semua pemberitahuan berhasil dihapus.');
    }

    private function homeRoute(User $user): string
    {
        return match ($user->role) {
            'staf' => route('staff.dashboard'),
            'wali' => route('guardian.index'),
            'guru' => route('teaching.index'),
            'siswa' => route('student.index'),
            default => route('admin.dashboard'),
        };
    }
}
