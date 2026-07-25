<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    /** Sub fitur: Formulir Pendaftaran */
    public function create()
    {
        return view('public.pendaftaran.form', ['profil' => SchoolProfile::current()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nisn' => ['nullable', 'string', 'max:30'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'asal_sekolah' => ['required', 'string', 'max:150'],
            'jurusan_pilihan' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'telepon' => ['required', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:500'],
            'nama_wali' => ['required', 'string', 'max:150'],
            'telepon_wali' => ['required', 'string', 'max:30'],
        ]);

        $data['nomor_pendaftaran'] = Registration::generateNomor();
        $data['status'] = 'menunggu';

        $registration = Registration::create($data);

        return redirect()
            ->route('pendaftaran.dokumen', $registration->nomor_pendaftaran)
            ->with('success', 'Pendaftaran berhasil dikirim. Simpan nomor pendaftaran Anda: '.$registration->nomor_pendaftaran);
    }

    /** Sub fitur: Unggah Dokumen */
    public function dokumen(string $nomor)
    {
        $registration = Registration::where('nomor_pendaftaran', $nomor)->firstOrFail();

        return view('public.pendaftaran.dokumen', [
            'profil' => SchoolProfile::current(),
            'registration' => $registration->load('documents'),
        ]);
    }

    public function unggahDokumen(Request $request, string $nomor)
    {
        $registration = Registration::where('nomor_pendaftaran', $nomor)->firstOrFail();

        $validated = $request->validate([
            'jenis' => ['required', Rule::in(['Kartu Keluarga', 'Akta Kelahiran', 'Ijazah/SKL', 'Rapor', 'Pas Foto', 'Lainnya'])],
            'berkas' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $file = $validated['berkas'];
        $path = $file->store("dokumen/{$registration->nomor_pendaftaran}", 'public');

        $registration->documents()->create([
            'jenis' => $validated['jenis'],
            'nama_file' => $file->getClientOriginalName(),
            'path' => $path,
            'ukuran' => $file->getSize(),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function hapusDokumen(string $nomor, int $dokumen)
    {
        $registration = Registration::where('nomor_pendaftaran', $nomor)->firstOrFail();
        $doc = $registration->documents()->findOrFail($dokumen);

        \Storage::disk('public')->delete($doc->path);
        $doc->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }

    /** Sub fitur: Cek Status Pendaftaran */
    public function cekStatus(Request $request)
    {
        $registration = null;
        $nomor = $request->input('nomor');

        if ($request->filled('nomor')) {
            $request->validate([
                'nomor' => ['required', 'string'],
                'email' => ['required', 'email'],
            ]);

            $registration = Registration::with('documents')
                ->where('nomor_pendaftaran', $nomor)
                ->where('email', $request->input('email'))
                ->first();

            if (! $registration) {
                return back()->withInput()->withErrors([
                    'nomor' => 'Data pendaftaran tidak ditemukan. Periksa kembali nomor pendaftaran dan email.',
                ]);
            }
        }

        return view('public.pendaftaran.status', [
            'profil' => SchoolProfile::current(),
            'registration' => $registration,
        ]);
    }
}
