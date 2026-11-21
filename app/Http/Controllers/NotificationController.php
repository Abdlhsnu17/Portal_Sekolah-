<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        $url = (string) ($item->data['url'] ?? route('admin.dashboard'));

        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('admin.dashboard'));
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
}
