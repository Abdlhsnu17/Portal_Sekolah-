<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('approval_status', 20)->default('approved')->after('status')->index();
            $table->text('approval_note')->nullable()->after('approval_status');
            $table->string('request_evidence_path')->nullable()->after('approval_note');
            $table->foreignId('requested_by')->nullable()->after('request_evidence_path')->constrained('users')->nullOnDelete();
            $table->foreignId('request_reviewed_by')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('request_reviewed_at')->nullable()->after('request_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by');
            $table->dropConstrainedForeignId('request_reviewed_by');
            $table->dropColumn(['approval_status', 'approval_note', 'request_evidence_path', 'request_reviewed_at']);
        });
    }
};
