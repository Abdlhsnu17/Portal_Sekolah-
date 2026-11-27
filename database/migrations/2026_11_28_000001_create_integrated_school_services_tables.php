<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('semester', 12)->nullable()->after('type');
            $table->decimal('weight', 5, 2)->default(1)->after('maximum_score');
            $table->boolean('is_published')->default(false)->after('assessment_date');
        });

        Schema::create('learning_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->text('content')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['school_class_id', 'subject_id', 'published_at']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('teacher_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->text('instructions')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->decimal('maximum_score', 5, 2)->default(100);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['school_class_id', 'due_at', 'is_published']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->text('answer')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });

        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('semester', 12);
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->text('homeroom_note')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id', 'semester']);
        });

        Schema::create('report_card_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('minimum_score', 5, 2)->nullable();
            $table->string('predicate', 10)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['report_card_id', 'subject_id']);
        });

        Schema::create('invoice_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 40)->default('manual');
            $table->string('external_reference', 120)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('guardian_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 180);
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'read_at']);
        });

        Schema::create('student_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->integer('points')->default(0);
            $table->date('occurred_on');
            $table->string('follow_up_status', 20)->default('open');
            $table->text('follow_up_note')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'type', 'occurred_on']);
        });

        Schema::create('library_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('library_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['library_book_id', 'status']);
        });

        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('borrower_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('borrower_name', 150)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->date('borrowed_on');
            $table->date('due_on')->nullable();
            $table->date('returned_on')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['school_asset_id', 'returned_on']);
        });

        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_asset_id')->constrained()->cascadeOnDelete();
            $table->date('performed_on');
            $table->string('type', 80);
            $table->decimal('cost', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['school_asset_id', 'performed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('library_reservations');
        Schema::dropIfExists('student_incidents');
        Schema::dropIfExists('guardian_messages');
        Schema::dropIfExists('invoice_payment_requests');
        Schema::dropIfExists('report_card_items');
        Schema::dropIfExists('report_cards');
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('learning_resources');
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['semester', 'weight', 'is_published']);
        });
    }
};
