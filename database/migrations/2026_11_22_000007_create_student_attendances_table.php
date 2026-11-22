<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some installations received this table before this migration was
        // added to their migration history.  Do not fail their upgrade; the
        // existing table already has the same schema.
        if (Schema::hasTable('student_attendances')) {
            return;
        }

        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status', 20)->default('hadir');
            $table->string('note', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            // MySQL limits identifier names to 64 characters.
            $table->unique(['student_id', 'school_class_id', 'attendance_date'], 'student_attendance_unique');
            $table->index(['school_class_id', 'attendance_date'], 'student_attendance_class_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};
