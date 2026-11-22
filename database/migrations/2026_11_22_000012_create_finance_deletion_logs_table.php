<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_title', 150);
            $table->decimal('invoice_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2);
            $table->unsignedInteger('payment_count');
            $table->string('reason', 500);
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_deletion_logs');
    }
};
