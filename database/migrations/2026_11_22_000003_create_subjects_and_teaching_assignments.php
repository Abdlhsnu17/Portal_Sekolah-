<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            // Kelas saat ini masih tersimpan sebagai teks pada data siswa.
            $table->string('kelas', 30);
            $table->string('tahun_ajaran', 20);
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id', 'kelas', 'tahun_ajaran'], 'teaching_assignment_unique');
            $table->index(['kelas', 'tahun_ajaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
        Schema::dropIfExists('subjects');
    }
};
