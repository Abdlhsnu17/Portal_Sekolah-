<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150); $table->decimal('amount', 14, 2); $table->date('due_date')->nullable();
            $table->string('status', 20)->default('belum_lunas'); $table->timestamps(); $table->index(['student_id','status']);
        });
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2); $table->date('paid_on'); $table->string('method', 50)->nullable();
            $table->string('reference', 100)->nullable(); $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('student_payments'); Schema::dropIfExists('student_invoices'); }
};
