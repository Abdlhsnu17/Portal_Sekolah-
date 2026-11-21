<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('jenis_kelamin')->default('L');
            $table->string('jabatan')->nullable();
            $table->date('tanggal_bergabung')->nullable();
            $table->text('alamat')->nullable();
            $table->string('status')->default('aktif');
            $table->timestamps();

            $table->index(['status', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
