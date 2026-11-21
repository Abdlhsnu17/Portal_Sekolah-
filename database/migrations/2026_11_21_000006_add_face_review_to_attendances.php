<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('face_review_status', 20)->nullable()->after('face_photo_path');
            $table->text('face_review_note')->nullable()->after('face_review_status');
            $table->timestamp('face_reviewed_at')->nullable()->after('face_review_note');
            $table->foreignId('face_reviewed_by')->nullable()->after('face_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('face_reviewed_by');
            $table->dropColumn(['face_review_status', 'face_review_note', 'face_reviewed_at']);
        });
    }
};
