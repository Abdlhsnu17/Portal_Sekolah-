<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Registration;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentInvoice;
use App\Models\SchoolAsset;
use App\Models\StudentIncident;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function students(): StreamedResponse
    {
        return $this->csv('siswa', ['NIS', 'NISN', 'Nama', 'Kelas', 'Jurusan', 'Email', 'Wali', 'Telepon wali', 'Status'],
            Student::orderBy('nama')->cursor(), fn ($student) => [$student->nis, $student->nisn, $student->nama, $student->kelas, $student->jurusan, $student->email, $student->nama_wali, $student->telepon_wali, $student->status]);
    }

    public function teachers(): StreamedResponse
    {
        return $this->csv('guru', ['NIP', 'Nama', 'Email', 'Telepon', 'Mata pelajaran', 'Jabatan', 'Status'],
            Teacher::orderBy('nama')->cursor(), fn ($teacher) => [$teacher->nip, $teacher->nama, $teacher->email, $teacher->telepon, $teacher->mata_pelajaran, $teacher->jabatan, $teacher->status]);
    }

    public function registrations(): StreamedResponse
    {
        return $this->csv('pendaftar', ['Nomor pendaftaran', 'Nama', 'Email', 'Telepon', 'Asal sekolah', 'Jurusan pilihan', 'Status', 'Tanggal daftar'],
            Registration::latest()->cursor(), fn ($registration) => [$registration->nomor_pendaftaran, $registration->nama_lengkap, $registration->email, $registration->telepon, $registration->asal_sekolah, $registration->jurusan_pilihan, $registration->status, $registration->created_at?->format('Y-m-d H:i')]);
    }

    public function attendances(Request $request): StreamedResponse
    {
        $query = StudentAttendance::with(['student', 'schoolClass.academicYear'])->orderByDesc('attendance_date')->orderBy('student_id');
        if ($request->filled('from')) $query->whereDate('attendance_date', '>=', $request->input('from'));
        if ($request->filled('until')) $query->whereDate('attendance_date', '<=', $request->input('until'));
        return $this->csv('absensi-siswa', ['Tanggal', 'NIS', 'Nama', 'Rombel', 'Tahun ajaran', 'Status', 'Keterangan'], $query->cursor(), fn ($attendance) => [$attendance->attendance_date->format('Y-m-d'), $attendance->student->nis, $attendance->student->nama, $attendance->schoolClass->name, $attendance->schoolClass->academicYear->name, $attendance->status, $attendance->note]);
    }

    public function scores(): StreamedResponse
    {
        $assessments = Assessment::with(['schoolClass.academicYear', 'subject', 'scores.student'])->latest('assessment_date')->get();
        return $this->csv('nilai-siswa', ['Tanggal', 'Rombel', 'Tahun ajaran', 'Mata pelajaran', 'Penilaian', 'NIS', 'Nama', 'Nilai', 'Maksimum', 'Catatan'], $assessments->flatMap(fn ($assessment) => $assessment->scores->map(fn ($score) => compact('assessment', 'score'))), fn ($row) => [$row['assessment']->assessment_date->format('Y-m-d'), $row['assessment']->schoolClass->name, $row['assessment']->schoolClass->academicYear->name, $row['assessment']->subject->nama, $row['assessment']->title, $row['score']->student->nis, $row['score']->student->nama, $row['score']->score, $row['assessment']->maximum_score, $row['score']->note]);
    }

    public function finance(): StreamedResponse
    {
        return $this->csv('keuangan-siswa', ['NIS', 'Nama', 'Kategori', 'Tagihan', 'Nominal', 'Terbayar', 'Sisa', 'Jatuh tempo', 'Status'], StudentInvoice::with('student')->withSum('payments', 'amount')->orderByDesc('due_date')->cursor(), fn ($invoice) => [$invoice->student->nis, $invoice->student->nama, StudentInvoice::CATEGORIES[$invoice->category] ?? $invoice->category, $invoice->title, $invoice->amount, $invoice->payments_sum_amount ?? 0, max(0, (float) $invoice->amount - (float) ($invoice->payments_sum_amount ?? 0)), $invoice->due_date?->format('Y-m-d'), $invoice->status]);
    }

    public function assets(): StreamedResponse
    {
        return $this->csv('inventaris-aset', ['Kode', 'Nama', 'Kategori', 'Lokasi', 'Jumlah', 'Kondisi', 'Status', 'Nilai perolehan'], SchoolAsset::orderBy('name')->cursor(), fn ($asset) => [$asset->code, $asset->name, $asset->category, $asset->location, $asset->quantity, SchoolAsset::CONDITIONS[$asset->condition] ?? $asset->condition, SchoolAsset::STATUSES[$asset->status] ?? $asset->status, $asset->acquisition_value]);
    }

    public function studentRisks(): StreamedResponse
    {
        $students = Student::where('status', 'aktif')->get();
        return $this->csv('indikator-risiko-siswa', ['NIS', 'Nama', 'Alpa', 'Poin pelanggaran', 'Kasus tindak lanjut'], $students, function ($student) { $alpa = StudentAttendance::where('student_id', $student->id)->where('status', 'alpa')->count(); $incidents = StudentIncident::where('student_id', $student->id); return [$student->nis, $student->nama, $alpa, (clone $incidents)->where('type', 'violation')->sum('points'), $incidents->whereIn('follow_up_status', ['open', 'follow_up'])->count()]; });
    }

    private function csv(string $name, array $headings, iterable $rows, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            foreach ($rows as $row) fputcsv($out, $map($row));
            fclose($out);
        }, $name.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
