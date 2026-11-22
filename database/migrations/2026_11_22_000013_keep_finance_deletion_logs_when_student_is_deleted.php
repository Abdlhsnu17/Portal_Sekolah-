<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('finance_deletion_logs', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('finance_deletion_logs', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });
    }
};
