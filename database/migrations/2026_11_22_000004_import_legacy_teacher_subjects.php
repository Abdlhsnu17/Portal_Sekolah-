<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('teachers')->whereNotNull('mata_pelajaran')->where('mata_pelajaran', '!=', '')
            ->orderBy('id')->get(['id', 'mata_pelajaran'])->each(function (object $teacher): void {
                DB::table('subjects')->updateOrInsert(
                    ['nama' => trim($teacher->mata_pelajaran)],
                    [
                        // ID guru membuat kode impor tetap unik sekalipun nama mapel
                        // mengandung karakter yang tidak cocok dijadikan kode.
                        'kode' => 'LEGACY-'.$teacher->id,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        DB::table('subjects')->where('kode', 'like', 'LEGACY-%')->delete();
    }
};
