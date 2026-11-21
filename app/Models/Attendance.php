<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUSES = ['hadir', 'izin', 'sakit', 'alpa', 'dinas'];

    protected $fillable = [
        'teacher_id', 'staff_member_id', 'tanggal', 'jam_masuk', 'jam_keluar',
        'status', 'catatan', 'face_photo_path', 'face_checkout_photo_path', 'face_review_status',
        'face_review_note', 'face_reviewed_at', 'face_reviewed_by', 'recorded_by',
        'approval_status', 'approval_note', 'request_evidence_path', 'requested_by', 'request_reviewed_by', 'request_reviewed_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'face_reviewed_at' => 'datetime',
        'request_reviewed_at' => 'datetime',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function faceReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'face_reviewed_by');
    }

    public function requestReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_reviewed_by');
    }

    public function getPersonNameAttribute(): string
    {
        return $this->teacher?->nama ?? $this->staffMember?->nama ?? 'Data pegawai dihapus';
    }

    public function getPersonTypeAttribute(): string
    {
        return $this->teacher_id ? 'Guru' : 'Staf';
    }
}
