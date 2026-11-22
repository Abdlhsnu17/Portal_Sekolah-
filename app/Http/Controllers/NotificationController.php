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
        $item->markAsRead();

        $fallback = $this->homeRoute($request->user());
        $url = (string) ($item->data['url'] ?? $fallback);

        return redirect()->to(str_starts_with($url, url('/')) ? $url : $fallback);
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua pemberitahuan ditandai sudah dibaca.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        if (! $item->read_at) {
            return back()->with('error', 'Pemberitahuan harus dibaca sebelum dapat dihapus.');
        }

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
            'wali' => route('guardian.index'),
            'guru' => route('teaching.index'),
            'siswa' => route('student.index'),
            default => route('admin.dashboard'),
        };
    }
}
