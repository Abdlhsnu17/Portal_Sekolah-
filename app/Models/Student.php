<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'nis', 'nisn', 'nama', 'email', 'jenis_kelamin', 'kelas', 'jurusan',
        'tanggal_lahir', 'alamat', 'nama_wali', 'telepon_wali', 'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($q) => $q->where(function ($q) use ($term) {
            $q->where('nama', 'like', "%{$term}%")
                ->orWhere('nis', 'like', "%{$term}%")
                ->orWhere('nisn', 'like', "%{$term}%");
        }));
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }
}
