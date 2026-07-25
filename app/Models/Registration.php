<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    public const STATUSES = ['menunggu', 'diverifikasi', 'diterima', 'ditolak'];

    protected $fillable = [
        'nomor_pendaftaran', 'nama_lengkap', 'nisn', 'jenis_kelamin',
        'tempat_lahir', 'tanggal_lahir', 'asal_sekolah', 'jurusan_pilihan',
        'email', 'telepon', 'alamat', 'nama_wali', 'telepon_wali',
        'status', 'catatan',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(function ($q) use ($term) {
            $q->where('nama_lengkap', 'like', "%{$term}%")
                ->orWhere('nomor_pendaftaran', 'like', "%{$term}%")
                ->orWhere('asal_sekolah', 'like', "%{$term}%");
        }));
    }

    public static function generateNomor(): string
    {
        $prefix = 'PSB-'.date('Y').'-';
        $last = static::where('nomor_pendaftaran', 'like', $prefix.'%')
            ->orderByDesc('id')->value('nomor_pendaftaran');
        $urut = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
