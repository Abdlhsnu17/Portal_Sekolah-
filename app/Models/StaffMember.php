<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    protected $fillable = [
        'user_id', 'nip', 'nama', 'email', 'telepon', 'jenis_kelamin',
        'jabatan', 'tanggal_bergabung', 'alamat', 'status',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn ($query) => $query->where(function ($query) use ($term) {
            $query->where('nama', 'like', "%{$term}%")
                ->orWhere('nip', 'like', "%{$term}%")
                ->orWhere('jabatan', 'like', "%{$term}%");
        }));
    }
}
