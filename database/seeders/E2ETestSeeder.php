<?php

namespace Database\Seeders;

use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class E2ETestSeeder extends Seeder
{
    /** Data minimum untuk pengujian browser; sengaja tidak membuat berkas gambar. */
    public function run(): void
    {
        SchoolProfile::current()->update([
            'nama' => 'Portal Sekolah E2E',
            'jenjang' => 'SMA',
            'email' => 'info@sekolah.test',
        ]);

        User::updateOrCreate(
            ['email' => 'admin.e2e@sekolah.test'],
            [
                'name' => 'Admin E2E',
                'password' => 'E2E-Test-2026!',
                'role' => 'admin',
                'is_active' => true,
            ],
        );
    }
}
