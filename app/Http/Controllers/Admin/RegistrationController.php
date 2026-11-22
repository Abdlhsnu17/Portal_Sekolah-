<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Student;
use App\Notifications\RegistrationStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->input('sort'), ['nomor_pendaftaran', 'nama_lengkap', 'asal_sekolah', 'jurusan_pilihan', 'status'], true)
            ? $request->input('sort') : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        return view('admin.registrations.index', [
            'items' => Registration::search($request->input('q'))
                ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
                ->withCount('documents')
                ->orderBy($sort, $direction)
                ->paginate(10)
                ->withQueryString(),
            'filterStatus' => $request->input('status'),
        ]);
    }

    public function show(Registration $registration)
    {
        return view('admin.registrations.show', [
            'item' => $registration->load('documents'),
        ]);
    }

    /** Berkas pendaftar tersimpan di disk privat, jadi hanya bisa diambil lewat sini. */
    public function unduhDokumen(Registration $registration, int $dokumen): StreamedResponse
    {
        $doc = $registration->documents()->findOrFail($dokumen);

        return Storage::disk('local')->download($doc->path, $doc->nama_file);
    }

    /** Pratinjau hanya untuk tipe aman yang didukung browser, tetap dari disk privat. */
    public function previewDokumen(Registration $registration, int $dokumen): BinaryFileResponse
    {
        $doc = $registration->documents()->findOrFail($dokumen);
        $extension = strtolower(pathinfo($doc->nama_file, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'], true), 415, 'Jenis dokumen tidak dapat dipratinjau.');

        return response()->file(Storage::disk('local')->path($doc->path), [
            'Content-Disposition' => 'inline; filename="'.addslashes($doc->nama_file).'"',
        ]);
    }

    public function update(Request $request, Registration $registration)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Registration::STATUSES)],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $changed = $registration->status !== $data['status'] || $registration->catatan !== ($data['catatan'] ?? null);
        $registration->update($data);

        if ($changed && filled($registration->email)) {
            try {
                Notification::route('mail', $registration->email)->notify(new RegistrationStatusNotification($registration));
            } catch (\Throwable $exception) {
                report($exception);

                return back()->with('warning', 'Status diperbarui, tetapi email pemberitahuan belum terkirim. Periksa konfigurasi mail.');
            }
        }

        return back()->with('success', 'Status pendaftaran diperbarui'.($changed ? ' dan email pemberitahuan dikirim.' : '.'));
    }

    /** Promosikan pendaftar yang diterima menjadi siswa aktif. */
    public function terima(Registration $registration)
    {
        if ($registration->status !== 'diterima') {
            return back()->withErrors(['status' => 'Hanya pendaftar berstatus "diterima" yang bisa dijadikan siswa.']);
        }

        if ($registration->nisn && Student::where('nisn', $registration->nisn)->exists()) {
            return back()->withErrors(['status' => 'Siswa dengan NISN tersebut sudah terdaftar.']);
        }

        // Penomoran NIS dan penyimpanan siswa dalam satu transaksi — dua panitia
        // yang mempromosikan pendaftar pada saat yang sama tidak boleh memperoleh
        // NIS yang sama.
        DB::transaction(fn () => Student::create([
            'nis' => Student::generateNis(),
            'nisn' => $registration->nisn,
            'nama' => $registration->nama_lengkap,
            'email' => $registration->email,
            'jenis_kelamin' => $registration->jenis_kelamin,
            'jurusan' => $registration->jurusan_pilihan,
            'tanggal_lahir' => $registration->tanggal_lahir,
            'alamat' => $registration->alamat,
            'nama_wali' => $registration->nama_wali,
            'telepon_wali' => $registration->telepon_wali,
            'status' => 'aktif',
        ]));

        return redirect()->route('admin.students.index')
            ->with('success', $registration->nama_lengkap.' berhasil ditambahkan sebagai siswa.');
    }

    public function destroy(Registration $registration)
    {
        foreach ($registration->documents as $doc) {
            Storage::disk('local')->delete($doc->path);
        }

        $registration->delete();

        return redirect()->route('admin.registrations.index')->with('success', 'Data pendaftaran dihapus.');
    }
}
