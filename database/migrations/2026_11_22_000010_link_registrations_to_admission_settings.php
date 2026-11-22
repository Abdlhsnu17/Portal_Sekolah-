<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('admission_period_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('admission_path_id')->nullable()->after('admission_period_id')->constrained()->nullOnDelete();
            $table->index(['admission_period_id', 'status']);
        });
    }
    public function down(): void {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex(['admission_period_id', 'status']);
            $table->dropConstrainedForeignId('admission_path_id');
            $table->dropConstrainedForeignId('admission_period_id');
        });
    }
};
